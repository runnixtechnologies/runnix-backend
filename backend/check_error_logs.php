<?php
/**
 * Script to check PHP error logs for debugging the update food item issue
 */

echo "=== PHP ERROR LOG CHECKER ===\n\n";

// Check if php-error.log exists
$errorLogPath = __DIR__ . '/php-error.log';
if (file_exists($errorLogPath)) {
    echo "Found php-error.log file\n";
    echo "File size: " . filesize($errorLogPath) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($errorLogPath)) . "\n\n";
    
    // Get the last 50 lines of the error log
    $lines = file($errorLogPath);
    $lastLines = array_slice($lines, -50);
    
    echo "=== LAST 50 LINES OF ERROR LOG ===\n";
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "php-error.log file not found at: " . $errorLogPath . "\n";
    echo "Checking alternative locations...\n\n";
    
    // Check common PHP error log locations
    $possiblePaths = [
        '/var/log/php_errors.log',
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        ini_get('error_log'),
        '/tmp/php_errors.log'
    ];
    
    foreach ($possiblePaths as $path) {
        if ($path && file_exists($path)) {
            echo "Found error log at: " . $path . "\n";
            echo "File size: " . filesize($path) . " bytes\n";
            echo "Last modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n\n";
            
            // Get the last 20 lines
            $lines = file($path);
            $lastLines = array_slice($lines, -20);
            
            echo "=== LAST 20 LINES FROM " . $path . " ===\n";
            foreach ($lastLines as $line) {
                echo $line;
            }
            break;
        }
    }
}

echo "\n=== INSTRUCTIONS ===\n";
echo "1. Try updating a food item through your mobile app\n";
echo "2. Check the error logs above for detailed error information\n";
echo "3. Look for lines starting with '=== FOOD ITEM UPDATE ERROR ==='\n";
echo "4. Look for lines starting with '=== FOOD ITEM MODEL UPDATE ERROR ==='\n";
echo "5. Look for lines starting with '=== UPDATE FOOD ITEM ENDPOINT ERROR ==='\n";
echo "6. The error details will help identify the exact issue\n\n";

echo "=== ERROR LOG LOCATIONS TO CHECK ===\n";
echo "1. " . __DIR__ . "/php-error.log\n";
echo "2. " . ini_get('error_log') . "\n";
echo "3. Check your web server error logs (Apache/Nginx)\n";
echo "4. Check PHP-FPM error logs if using PHP-FPM\n\n";

echo "=== NEXT STEPS ===\n";
echo "After you get the detailed error information from the logs:\n";
echo "1. Share the specific error message and stack trace\n";
echo "2. Include the 'Update Data' and 'Original Data' from the logs\n";
echo "3. This will help identify the exact cause of the issue\n";
?>
