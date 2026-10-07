<?php
/**
 * Server 2: System Test Script
 * 
 * Tests all components and connections
 * Access via: http://SERVER2_IP/test.php
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server 2 System Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0074e4;
            padding-bottom: 10px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #0074e4;
            margin-top: 0;
        }
        .success {
            color: #2a9d8f;
            font-weight: bold;
        }
        .error {
            color: #e63946;
            font-weight: bold;
        }
        .info {
            color: #666;
        }
        pre {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .status-icon {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>🔧 Server 2 System Test</h1>
    <p class="info">This page tests all Server 2 components and connections.</p>

    <!-- PHP Configuration Test -->
    <div class="test-section">
        <h2>1. PHP Configuration</h2>
        <?php
        $phpVersion = phpversion();
        $requiredExtensions = ['mysqli', 'curl', 'json'];
        $loadedExtensions = get_loaded_extensions();
        
        echo "<p><strong>PHP Version:</strong> $phpVersion</p>";
        
        foreach ($requiredExtensions as $ext) {
            if (in_array($ext, $loadedExtensions)) {
                echo "<p><span class='status-icon'>✅</span><span class='success'>$ext extension loaded</span></p>";
            } else {
                echo "<p><span class='status-icon'>❌</span><span class='error'>$ext extension NOT loaded</span></p>";
            }
        }
        ?>
    </div>

    <!-- Database Connection Test -->
    <div class="test-section">
        <h2>2. Database Connection (Server 3)</h2>
        <?php
        require_once 'includes/db.php';
        
        try {
            $result = testDatabaseConnection();
            
            if ($result['success']) {
                echo "<p><span class='status-icon'>✅</span><span class='success'>Database connection successful</span></p>";
                echo "<p><strong>Server Info:</strong> " . $result['server_info'] . "</p>";
                
                // Try to query listings
                $db = getDatabase();
                $countResult = $db->query("SELECT COUNT(*) as count FROM listings");
                $row = $countResult->fetch_assoc();
                echo "<p><strong>Total Listings:</strong> " . $row['count'] . "</p>";
                
                // Test query by zip code
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM listings WHERE zipcode = ?");
                $testZip = '90210';
                $stmt->bind_param('s', $testZip);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                echo "<p><strong>Listings in 90210:</strong> " . $row['count'] . "</p>";
                
            } else {
                echo "<p><span class='status-icon'>❌</span><span class='error'>Database connection failed</span></p>";
                echo "<p class='error'>" . $result['error'] . "</p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status-icon'>❌</span><span class='error'>Database error: " . $e->getMessage() . "</span></p>";
        }
        ?>
    </div>

    <!-- FRED API Test -->
    <div class="test-section">
        <h2>3. FRED API Integration</h2>
        <?php
        require_once 'includes/fred.php';
        
        try {
            $result = testFredAPI();
            
            if ($result['success']) {
                echo "<p><span class='status-icon'>✅</span><span class='success'>FRED API connection successful</span></p>";
                echo "<p><strong>Current Mortgage Rate:</strong> " . $result['current_rate'] . "</p>";
                echo "<p><strong>Series:</strong> " . $result['series'] . "</p>";
                
                // Check if using cache
                if (file_exists(FRED_CACHE_FILE)) {
                    $cacheData = json_decode(file_get_contents(FRED_CACHE_FILE), true);
                    $cacheAge = time() - $cacheData['timestamp'];
                    echo "<p><strong>Cache Status:</strong> Cached " . round($cacheAge / 60) . " minutes ago</p>";
                } else {
                    echo "<p><strong>Cache Status:</strong> No cache (fresh API call)</p>";
                }
                
            } else {
                echo "<p><span class='status-icon'>⚠️</span><span class='error'>FRED API connection failed (using fallback)</span></p>";
                echo "<p class='error'>" . $result['error'] . "</p>";
                echo "<p class='info'>System will use fallback rate: 6.75%</p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status-icon'>❌</span><span class='error'>FRED API error: " . $e->getMessage() . "</span></p>";
        }
        ?>
    </div>

    <!-- Mortgage Calculation Test -->
    <div class="test-section">
        <h2>4. Mortgage Calculation Engine</h2>
        <?php
        require_once 'includes/mortgage.php';
        
        $testPrice = 500000;
        $testRate = 6.5;
        $testIncome = 100000;
        
        $payment = calculateMonthlyPayment($testPrice, $testRate, $testIncome);
        
        echo "<p><strong>Test Case:</strong></p>";
        echo "<ul>";
        echo "<li>Home Price: $" . number_format($testPrice) . "</li>";
        echo "<li>Mortgage Rate: $testRate%</li>";
        echo "<li>Annual Income: $" . number_format($testIncome) . "</li>";
        echo "</ul>";
        
        echo "<p><strong>Calculated Payment:</strong></p>";
        echo "<ul>";
        echo "<li>Down Payment (20%): $" . number_format($payment['down_payment'], 2) . "</li>";
        echo "<li>Loan Amount: $" . number_format($payment['loan_amount'], 2) . "</li>";
        echo "<li>Monthly P&I: $" . number_format($payment['monthly_pi'], 2) . "</li>";
        echo "<li>Monthly Tax: $" . number_format($payment['monthly_tax'], 2) . "</li>";
        echo "<li>Monthly Insurance: $" . number_format($payment['monthly_insurance'], 2) . "</li>";
        echo "<li><strong>Total Monthly Payment: $" . number_format($payment['monthly_payment'], 2) . "</strong></li>";
        echo "<li>Max Affordable: $" . number_format($payment['max_affordable'], 2) . "</li>";
        echo "<li><strong>Affordable: </strong>" . ($payment['is_affordable'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . "</li>";
        echo "</ul>";
        ?>
    </div>

    <!-- API Endpoint Test -->
    <div class="test-section">
        <h2>5. API Endpoints</h2>
        <?php
        $testZip = '90210';
        $testIncome = 100000;
        $testBeds = 3;
        $testBaths = 2;
        
        echo "<p><strong>Test Query:</strong></p>";
        echo "<ul>";
        echo "<li>Zip Code: $testZip</li>";
        echo "<li>Annual Income: $" . number_format($testIncome) . "</li>";
        echo "<li>Bedrooms: $testBeds</li>";
        echo "<li>Bathrooms: $testBaths</li>";
        echo "</ul>";
        
        $htmlUrl = "/api/search.php?zip=$testZip&income=$testIncome&beds=$testBeds&baths=$testBaths";
        $jsonUrl = "/api/search-json.php?zip=$testZip&income=$testIncome&beds=$testBeds&baths=$testBaths";
        
        echo "<p><span class='status-icon'>🔗</span><strong>HTML Endpoint:</strong> <a href='$htmlUrl' target='_blank'>$htmlUrl</a></p>";
        echo "<p><span class='status-icon'>🔗</span><strong>JSON Endpoint:</strong> <a href='$jsonUrl' target='_blank'>$jsonUrl</a></p>";
        
        // Quick test of JSON endpoint
        try {
            $_GET['zip'] = $testZip;
            $_GET['income'] = $testIncome;
            $_GET['beds'] = $testBeds;
            $_GET['baths'] = $testBaths;
            
            ob_start();
            include 'api/search-json.php';
            $jsonOutput = ob_get_clean();
            
            $jsonData = json_decode($jsonOutput, true);
            
            if ($jsonData && $jsonData['success']) {
                echo "<p><span class='status-icon'>✅</span><span class='success'>API working correctly</span></p>";
                echo "<p><strong>Results:</strong> " . $jsonData['data']['total_listings'] . " homes found, " . 
                     $jsonData['data']['affordable_count'] . " affordable</p>";
            } else {
                echo "<p><span class='status-icon'>❌</span><span class='error'>API returned error</span></p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status-icon'>❌</span><span class='error'>API test failed: " . $e->getMessage() . "</span></p>";
        }
        ?>
    </div>

    <!-- Server Information -->
    <div class="test-section">
        <h2>6. Server Information</h2>
        <?php
        echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
        echo "<p><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";
        echo "<p><strong>Server Address:</strong> " . $_SERVER['SERVER_ADDR'] . "</p>";
        echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
        echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
        ?>
    </div>

    <!-- Test Summary -->
    <div class="test-section">
        <h2>📋 Test Summary</h2>
        <p>If all tests show ✅, your Server 2 is properly configured and ready for production.</p>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Verify Server 1 can connect to Server 2</li>
            <li>Test complete workflow from user interface</li>
            <li>Configure Security Groups to restrict access</li>
            <li><strong>Delete this test.php file before production deployment!</strong></li>
        </ul>
    </div>

    <footer style="text-align: center; margin-top: 40px; color: #999; font-size: 14px;">
        <p>Home Affordability Application - Server 2 Test Suite</p>
    </footer>
</body>
</html>
