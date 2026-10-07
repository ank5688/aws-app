<?php
/**
 * Server 2: Application Tier - JSON Search Endpoint
 * 
 * Returns the same data as search.php but in JSON format
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/db.php';
require_once '../includes/mortgage.php';
require_once '../includes/fred.php';

// Get and validate input parameters
$zip = isset($_GET['zip']) ? trim($_GET['zip']) : '';
$income = isset($_GET['income']) ? floatval($_GET['income']) : 0;
$beds = isset($_GET['beds']) ? intval($_GET['beds']) : 0;
$baths = isset($_GET['baths']) ? floatval($_GET['baths']) : 0;

// Response structure
$response = [
    'success' => false,
    'data' => null,
    'error' => null,
    'metadata' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'query' => [
            'zipcode' => $zip,
            'annual_income' => $income,
            'bedrooms' => $beds,
            'bathrooms' => $baths
        ]
    ]
];

// Validation
if (empty($zip) || !preg_match('/^\d{5}$/', $zip)) {
    $response['error'] = 'Invalid zip code. Please provide a 5-digit zip code.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

if ($income <= 0) {
    $response['error'] = 'Invalid income. Please provide a positive annual income.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

if ($beds < 1 || $beds > 5) {
    $response['error'] = 'Invalid number of bedrooms. Please select 1-5 bedrooms.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

if ($baths < 1 || $baths > 4) {
    $response['error'] = 'Invalid number of bathrooms. Please select 1-4 bathrooms.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Get database connection
    $db = getDatabase();
    
    // Query listings
    $listings = queryListings($db, $zip, $beds, $baths);
    
    // Get mortgage rate
    $mortgageRate = getCurrentMortgageRate();
    
    // Calculate payments
    $results = [];
    $affordableCount = 0;
    
    foreach ($listings as $listing) {
        $payment = calculateMonthlyPayment(
            $listing['price'],
            $mortgageRate,
            $income
        );
        
        $result = array_merge($listing, $payment);
        $results[] = $result;
        
        if ($result['is_affordable']) {
            $affordableCount++;
        }
    }
    
    // Build successful response
    $response['success'] = true;
    $response['data'] = [
        'mortgage_rate' => $mortgageRate,
        'total_listings' => count($results),
        'affordable_count' => $affordableCount,
        'listings' => $results
    ];
    
} catch (Exception $e) {
    error_log('JSON search error: ' . $e->getMessage());
    $response['error'] = 'An error occurred while processing your request.';
    $response['error_details'] = $e->getMessage(); // Only for development
}

echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * Query listings from database
 */
function queryListings($db, $zip, $beds, $baths) {
    $stmt = $db->prepare("
        SELECT 
            id,
            address,
            city,
            state,
            zipcode,
            price,
            bedrooms,
            bathrooms,
            sqft
        FROM listings
        WHERE zipcode = ?
        AND bedrooms >= ?
        AND bathrooms >= ?
        ORDER BY price ASC
    ");
    
    $stmt->bind_param('sid', $zip, $beds, $baths);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $listings = [];
    
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }
    
    $stmt->close();
    
    return $listings;
}
?>
