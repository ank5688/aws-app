<?php
/**
 * Server 2: Application Tier - Main Search Endpoint (HTML Response)
 * 
 * This script:
 * 1. Receives search parameters from Server 1
 * 2. Queries the database on Server 3
 * 3. Calls FRED API for current mortgage rate
 * 4. Calculates monthly payments and affordability
 * 5. Returns HTML response
 */

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests from Server 1

// Include required files
require_once '../includes/db.php';
require_once '../includes/mortgage.php';
require_once '../includes/fred.php';

// Get and validate input parameters
$zip = isset($_GET['zip']) ? trim($_GET['zip']) : '';
$income = isset($_GET['income']) ? floatval($_GET['income']) : 0;
$beds = isset($_GET['beds']) ? intval($_GET['beds']) : 0;
$baths = isset($_GET['baths']) ? floatval($_GET['baths']) : 0;

// Validation
if (empty($zip) || !preg_match('/^\d{5}$/', $zip)) {
    renderError('Invalid zip code. Please provide a 5-digit zip code.');
    exit;
}

if ($income <= 0) {
    renderError('Invalid income. Please provide a positive annual income.');
    exit;
}

if ($beds < 1 || $beds > 5) {
    renderError('Invalid number of bedrooms. Please select 1-5 bedrooms.');
    exit;
}

if ($baths < 1 || $baths > 4) {
    renderError('Invalid number of bathrooms. Please select 1-4 bathrooms.');
    exit;
}

try {
    // Step 1: Get database connection to Server 3
    $db = getDatabase();
    
    // Step 2: Query listings from database
    $listings = queryListings($db, $zip, $beds, $baths);
    
    // Step 3: Get current mortgage rate from FRED API
    $mortgageRate = getCurrentMortgageRate();
    
    // Step 4: Calculate monthly payments and affordability for each listing
    $results = [];
    foreach ($listings as $listing) {
        $payment = calculateMonthlyPayment(
            $listing['price'],
            $mortgageRate,
            $income
        );
        
        $results[] = array_merge($listing, $payment);
    }
    
    // Step 5: Render HTML response
    renderResults($results, $zip, $income, $beds, $baths, $mortgageRate);
    
} catch (Exception $e) {
    error_log('Search error: ' . $e->getMessage());
    renderError('An error occurred while processing your request. Please try again later.');
}

/**
 * Query listings from database on Server 3
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

/**
 * Render HTML results
 */
function renderResults($results, $zip, $income, $beds, $baths, $mortgageRate) {
    $count = count($results);
    $affordableCount = 0;
    
    foreach ($results as $result) {
        if ($result['is_affordable']) {
            $affordableCount++;
        }
    }
    
    echo '<div class="results-summary">';
    echo '<p><strong>Search Criteria:</strong> ' . htmlspecialchars($zip) . ' | ';
    echo '$' . number_format($income) . '/year | ';
    echo $beds . ' bed | ' . $baths . ' bath</p>';
    echo '<p><strong>Current Mortgage Rate:</strong> ' . number_format($mortgageRate, 2) . '%</p>';
    echo '<p><strong>Found:</strong> ' . $count . ' homes (' . $affordableCount . ' affordable)</p>';
    echo '</div>';
    
    if ($count === 0) {
        echo '<div class="no-results">';
        echo '<h3>No homes found</h3>';
        echo '<p>Try adjusting your search criteria or searching a different zip code.</p>';
        echo '</div>';
        return;
    }
    
    foreach ($results as $listing) {
        renderListingCard($listing);
    }
    
    // Add JSON view button
    echo '<div style="margin-top: 30px; text-align: center;">';
    echo '<button onclick="viewJSON()" class="btn-secondary">View as JSON</button>';
    echo '</div>';
}

/**
 * Render individual listing card
 */
function renderListingCard($listing) {
    $affordableClass = $listing['is_affordable'] ? 'affordable' : 'not-affordable';
    $badgeText = $listing['is_affordable'] ? '✓ Affordable' : '✗ Not Affordable';
    $badgeClass = $listing['is_affordable'] ? 'affordable' : 'not-affordable';
    
    echo '<div class="listing-card ' . $affordableClass . '">';
    
    // Header with price and affordability badge
    echo '<div class="listing-header">';
    echo '<div class="listing-price">$' . number_format($listing['price']) . '</div>';
    echo '<div class="affordability-badge ' . $badgeClass . '">' . $badgeText . '</div>';
    echo '</div>';
    
    // Address
    echo '<div class="listing-address">';
    echo '📍 ' . htmlspecialchars($listing['address']) . ', ';
    echo htmlspecialchars($listing['city']) . ', ' . htmlspecialchars($listing['state']) . ' ';
    echo htmlspecialchars($listing['zipcode']);
    echo '</div>';
    
    // Details
    echo '<div class="listing-details">';
    echo '<span>🛏️ ' . $listing['bedrooms'] . ' beds</span>';
    echo '<span>🚿 ' . $listing['bathrooms'] . ' baths</span>';
    echo '<span>📏 ' . number_format($listing['sqft']) . ' sqft</span>';
    echo '</div>';
    
    // Payment breakdown
    echo '<div class="payment-details">';
    
    echo '<div class="payment-item">';
    echo '<span>Down Payment (20%):</span>';
    echo '<span>$' . number_format($listing['down_payment']) . '</span>';
    echo '</div>';
    
    echo '<div class="payment-item">';
    echo '<span>Loan Amount:</span>';
    echo '<span>$' . number_format($listing['loan_amount']) . '</span>';
    echo '</div>';
    
    echo '<div class="payment-item">';
    echo '<span>Principal & Interest:</span>';
    echo '<span>$' . number_format($listing['monthly_pi'], 2) . '</span>';
    echo '</div>';
    
    echo '<div class="payment-item">';
    echo '<span>Property Tax:</span>';
    echo '<span>$' . number_format($listing['monthly_tax'], 2) . '</span>';
    echo '</div>';
    
    echo '<div class="payment-item">';
    echo '<span>Insurance:</span>';
    echo '<span>$' . number_format($listing['monthly_insurance'], 2) . '</span>';
    echo '</div>';
    
    echo '<div class="payment-item payment-total">';
    echo '<span><strong>Total Monthly Payment:</strong></span>';
    echo '<span><strong>$' . number_format($listing['monthly_payment'], 2) . '</strong></span>';
    echo '</div>';
    
    echo '<div class="payment-item">';
    echo '<span>Max Affordable (28% income):</span>';
    echo '<span>$' . number_format($listing['max_affordable'], 2) . '</span>';
    echo '</div>';
    
    echo '</div>'; // payment-details
    
    echo '</div>'; // listing-card
}

/**
 * Render error message
 */
function renderError($message) {
    echo '<div class="no-results">';
    echo '<h3>Error</h3>';
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '</div>';
}
?>
