<?php
echo "<h2>API Path Testing</h2>";

// Test relative paths
echo "<h3>Current working directory: " . getcwd() . "</h3>";
echo "<h3>Script path: " . __FILE__ . "</h3>";

// Test if api folder exists
if (file_exists('./api/customers.php')) {
    echo "<p style='color: green;'>✓ ./api/customers.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ ./api/customers.php NOT found</p>";
}

if (file_exists('../api/customers.php')) {
    echo "<p style='color: green;'>✓ ../api/customers.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ ../api/customers.php NOT found</p>";
}

// Test actual API call
echo "<h3>Testing API call:</h3>";
echo "<pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, './api/customers.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
echo "</pre>";
?>
