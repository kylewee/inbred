<?php
/**
 * Chilton Library Scraper - TEST VERSION
 * Tests authentication and data extraction
 */

// Your Chilton credentials
$username = 'nclivemdcp';
$password = 'nclive001';
$base_url = 'https://link.gale.com/apps/CHLL';

echo "=== Chilton Library Access Test ===\n\n";
echo "Step 1: Initial connection...\n";

// Initialize cURL session with cookie handling
$cookie_file = tempnam(sys_get_temp_dir(), 'chilton_cookies');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '?u=' . $username);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "HTTP Code: $http_code\n";
echo "Effective URL: $effective_url\n";
echo "Response length: " . strlen($response) . " bytes\n\n";

// Check if we need to submit password
if (stripos($response, 'library card number') !== false || 
    stripos($response, 'password') !== false ||
    stripos($response, 'login') !== false) {
    
    echo "Step 2: Login form detected, submitting credentials...\n";
    
    // Extract form action and any hidden fields
    preg_match('/action="([^"]+)"/', $response, $action_match);
    $form_action = $action_match[1] ?? '/apps/auth';
    
    if (!str_starts_with($form_action, 'http')) {
        $form_action = 'https://link.gale.com' . $form_action;
    }
    
    echo "Form action: $form_action\n";
    
    // Submit login form
    $post_data = [
        'authenticationMethod' => 'LoginPassword',
        'username' => $username,
        'password' => $password,
        'prodId' => 'CHLL'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $form_action);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    echo "After login HTTP Code: $http_code\n";
    echo "After login URL: $effective_url\n";
    echo "After login response length: " . strlen($response) . " bytes\n\n";
}

// Check if we're logged in by looking for Chilton-specific content
$logged_in = false;

if (stripos($response, 'chilton') !== false || 
    stripos($response, 'labor') !== false ||
    stripos($response, 'repair') !== false ||
    stripos($effective_url, 'CHLL') !== false) {
    $logged_in = true;
}

echo "=== Results ===\n";
echo "Logged in: " . ($logged_in ? "YES ✓" : "NO ✗") . "\n";

if ($logged_in) {
    echo "\nStep 3: Testing vehicle search...\n";
    
    // Try to search for a vehicle
    $search_terms = ['2015 Honda Accord', 'Honda Accord 2015'];
    
    foreach ($search_terms as $term) {
        echo "\nSearching for: $term\n";
        
        // Common search URL patterns for Gale/Chilton
        $search_urls = [
            "https://link.gale.com/apps/doc/CHLL/search?u=$username&q=" . urlencode($term),
            "https://link.gale.com/apps/CHLL/search?u=$username&text=" . urlencode($term)
        ];
        
        foreach ($search_urls as $search_url) {
            curl_setopt($ch, CURLOPT_URL, $search_url);
            curl_setopt($ch, CURLOPT_POST, false);
            
            $search_response = curl_exec($ch);
            $search_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            echo "  Search URL: $search_url\n";
            echo "  HTTP Code: $search_code\n";
            echo "  Response length: " . strlen($search_response) . " bytes\n";
            
            // Look for vehicle-specific results
            if (stripos($search_response, 'starter') !== false ||
                stripos($search_response, 'alternator') !== false ||
                stripos($search_response, 'labor') !== false) {
                echo "  ✓ Found repair-related content!\n";
                
                // Save sample for analysis
                file_put_contents('/tmp/chilton_search_sample.html', $search_response);
                echo "  Sample saved to: /tmp/chilton_search_sample.html\n";
                break 2;
            }
        }
    }
} else {
    echo "\nLogin failed. Response snippet:\n";
    echo substr($response, 0, 500) . "...\n";
}

curl_close($ch);
unlink($cookie_file);

echo "\n=== Test Complete ===\n";
