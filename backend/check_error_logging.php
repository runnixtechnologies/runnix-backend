<?php
/**
 * Check PHP error logging configuration
 */

echo "=== PHP ERROR LOGGING CONFIGURATION ===\n\n";

echo "Current error log file: " . ini_get('error_log') . "\n";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "Error reporting level: " . error_reporting() . "\n";
echo "Log errors to syslog: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n\n";

// Check common error log locations
$commonLogPaths = [
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/logs/error.log',
    ini_get('error_log'),
    __DIR__ . '/php-error.log',
    __DIR__ . '/../php-error.log'
];

echo "Checking common error log locations:\n";
foreach ($commonLogPaths as $path) {
    if ($path && file_exists($path)) {
        echo "✓ Found: $path (Size: " . filesize($path) . " bytes)\n";
        
        // Show last few lines
        $lines = file($path);
        $lastLines = array_slice($lines, -5);
        echo "  Last 5 lines:\n";
        foreach ($lastLines as $line) {
            echo "    " . trim($line) . "\n";
        }
        echo "\n";
    } else {
        echo "✗ Not found: $path\n";
    }
}

// Test writing to error log
echo "Testing error log writing...\n";
error_log("TEST ERROR LOG MESSAGE: " . date('Y-m-d H:i:s') . " - This is a test message");

// Check if our custom log file exists and is writable
$customLogFile = __DIR__ . '/php-error.log';
echo "\nCustom log file: $customLogFile\n";
echo "Exists: " . (file_exists($customLogFile) ? 'YES' : 'NO') . "\n";
echo "Writable: " . (is_writable($customLogFile) ? 'YES' : 'NO') . "\n";

if (file_exists($customLogFile)) {
    echo "Size: " . filesize($customLogFile) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($customLogFile)) . "\n";
}

echo "\n=== CONFIGURATION CHECK COMPLETE ===\n";
?>
