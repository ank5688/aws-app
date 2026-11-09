<?php
/**
 * Server 2: Application Tier - School District Lookup Endpoint (JSON)
 *
 * Given a 5-digit zip code, returns the public school districts that have
 * schools located in that zip code.
 *
 * Data sources (both free, no API key required):
 *   1. Zippopotam.us          -> maps a zip code to its U.S. state
 *   2. Urban Institute         -> Common Core of Data (CCD) school directory
 *      Education Data Portal      https://educationdata.urban.org
 *
 * Why two calls?
 *   The Urban Institute CCD directory endpoint filters reliably by state
 *   FIPS code (?fips=NN) but NOT by zip_location. So we first resolve the
 *   zip -> state (to get the FIPS code), fetch that state's school
 *   directory, then filter down to the requested zip in PHP and collapse
 *   the matching schools into their distinct districts (leaid / lea_name).
 *
 * Request:  /api/schools.php?zip=90210
 * Response: JSON  { success, data: { zip, state, districts: [...] }, error }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests from Server 1

// Year of CCD directory data to query. Latest complete year available.
define('CCD_YEAR', 2021);

// Map of U.S. state abbreviation -> FIPS code (what the Urban API filters on).
$STATE_FIPS = [
    'AL' => 1,  'AK' => 2,  'AZ' => 4,  'AR' => 5,  'CA' => 6,  'CO' => 8,
    'CT' => 9,  'DE' => 10, 'DC' => 11, 'FL' => 12, 'GA' => 13, 'HI' => 15,
    'ID' => 16, 'IL' => 17, 'IN' => 18, 'IA' => 19, 'KS' => 20, 'KY' => 21,
    'LA' => 22, 'ME' => 23, 'MD' => 24, 'MA' => 25, 'MI' => 26, 'MN' => 27,
    'MS' => 28, 'MO' => 29, 'MT' => 30, 'NE' => 31, 'NV' => 32, 'NH' => 33,
    'NJ' => 34, 'NM' => 35, 'NY' => 36, 'NC' => 37, 'ND' => 38, 'OH' => 39,
    'OK' => 40, 'OR' => 41, 'PA' => 42, 'RI' => 44, 'SC' => 45, 'SD' => 46,
    'TN' => 47, 'TX' => 48, 'UT' => 49, 'VT' => 50, 'VA' => 51, 'WA' => 53,
    'WV' => 54, 'WI' => 55, 'WY' => 56,
];

// Standard response envelope (mirrors search-json.php style).
$response = [
    'success'  => false,
    'data'     => null,
    'error'    => null,
    'metadata' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'query'     => ['zipcode' => ''],
        'source'    => 'Urban Institute Education Data Portal (CCD ' . CCD_YEAR . ')',
    ],
];

// ---------------------------------------------------------------------------
// Input validation
// ---------------------------------------------------------------------------
$zip = isset($_GET['zip']) ? trim($_GET['zip']) : '';
$response['metadata']['query']['zipcode'] = $zip;

if (empty($zip) || !preg_match('/^\d{5}$/', $zip)) {
    $response['error'] = 'Invalid zip code. Please provide a 5-digit zip code.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Step 1: zip -> state abbreviation (Zippopotam.us)
    $state = getStateForZip($zip);
    if ($state === null) {
        $response['error'] = 'Could not find a U.S. state for that zip code.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    if (!isset($STATE_FIPS[$state])) {
        $response['error'] = 'Unsupported state (' . $state . ') for this lookup.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    $fips = $STATE_FIPS[$state];

    // Step 2: fetch the state's school directory and keep only schools in $zip
    $schools = getSchoolsInZip($fips, $zip);

    // Step 3: collapse schools into distinct districts
    $districts = [];
    foreach ($schools as $school) {
        $leaid = $school['leaid'] ?? null;
        if ($leaid === null) {
            continue;
        }
        if (!isset($districts[$leaid])) {
            $districts[$leaid] = [
                'district_id'   => $leaid,
                'district_name' => $school['lea_name'] ?? 'Unknown district',
                'school_count'  => 0,
            ];
        }
        $districts[$leaid]['school_count']++;
    }

    // Sort districts by number of schools (largest first), then name.
    $districts = array_values($districts);
    usort($districts, function ($a, $b) {
        if ($a['school_count'] === $b['school_count']) {
            return strcmp($a['district_name'], $b['district_name']);
        }
        return $b['school_count'] - $a['school_count'];
    });

    $response['success'] = true;
    $response['data'] = [
        'zip'            => $zip,
        'state'          => $state,
        'district_count' => count($districts),
        'school_count'   => count($schools),
        'districts'      => $districts,
    ];

} catch (Exception $e) {
    error_log('Schools lookup error: ' . $e->getMessage());
    $response['error'] = 'An error occurred while looking up school districts. Please try again later.';
}

echo json_encode($response, JSON_PRETTY_PRINT);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Perform a GET request and return the decoded JSON body (or null on failure).
 */
function httpGetJson($url, $timeout = 15) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'HomeFind/1.0 (school-district-lookup)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false || $code < 200 || $code >= 300) {
        error_log("httpGetJson failed ($code) for $url: $err");
        return null;
    }

    $decoded = json_decode($body, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
}

/**
 * Resolve a 5-digit zip code to a U.S. state abbreviation via Zippopotam.us.
 * Returns e.g. "CA", or null if not found.
 */
function getStateForZip($zip) {
    $data = httpGetJson('https://api.zippopotam.us/us/' . urlencode($zip), 12);
    if (!$data || empty($data['places'][0]['state abbreviation'])) {
        return null;
    }
    return strtoupper($data['places'][0]['state abbreviation']);
}

/**
 * Fetch the CCD school directory for a state (by FIPS code) and return only
 * the schools whose location zip matches $zip.
 *
 * The Urban API returns up to 10,000 rows per page, so even the largest
 * states need only a couple of pages. We page through until exhausted or a
 * safety cap is reached.
 */
function getSchoolsInZip($fips, $zip) {
    $matches  = [];
    $url      = 'https://educationdata.urban.org/api/v1/schools/ccd/directory/'
              . CCD_YEAR . '/?fips=' . intval($fips);
    $pageGuard = 0;

    while ($url !== null && $pageGuard < 5) {
        $pageGuard++;
        $data = httpGetJson($url, 25);
        if (!$data || !isset($data['results'])) {
            break;
        }

        foreach ($data['results'] as $school) {
            // zip_location is the physical location of the school.
            if (isset($school['zip_location']) && (string)$school['zip_location'] === (string)$zip) {
                $matches[] = $school;
            }
        }

        // Follow the API's own "next" page link if present.
        $url = !empty($data['next']) ? $data['next'] : null;
    }

    return $matches;
}
?>
