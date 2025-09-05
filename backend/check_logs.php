<?php
// Script to check log file locations and permissions

echo "=== LOG FILE DEBUGGING ===\n";

// Check current directory
echo "Current directory: " . __DIR__ . "\n";

// Check if php-error.log exists in backend folder
$backendLog = __DIR__ . '/php-error.log';
echo "Backend log file: $backendLog\n";
echo "Exists: " . (file_exists($backendLog) ? 'YES' : 'NO') . "\n";
if (file_exists($backendLog)) {
    echo "Size: " . filesize($backendLog) . " bytes\n";
    echo "Readable: " . (is_readable($backendLog) ? 'YES' : 'NO') . "\n";
    echo "Writable: " . (is_writable($backendLog) ? 'YES' : 'NO') . "\n";
}

// Check if php-error.log exists in parent folder
$parentLog = __DIR__ . '/../php-error.log';
echo "\nParent log file: $parentLog\n";
echo "Exists: " . (file_exists($parentLog) ? 'YES' : 'NO') . "\n";
if (file_exists($parentLog)) {
    echo "Size: " . filesize($parentLog) . " bytes\n";
    echo "Readable: " . (is_readable($parentLog) ? 'YES' : 'NO') . "\n";
    echo "Writable: " . (is_writable($parentLog) ? 'YES' : 'NO') . "\n";
}

// Check PHP error log setting
echo "\nPHP error_log setting: " . ini_get('error_log') . "\n";
echo "PHP log_errors setting: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";

// Check system error log
$systemLog = ini_get('error_log');
if ($systemLog && file_exists($systemLog)) {
    echo "System error log: $systemLog\n";
    echo "Size: " . filesize($systemLog) . " bytes\n";
}

// Test writing to both locations
echo "\n=== TESTING LOG WRITING ===\n";

// Test backend log
error_log("TEST LOG ENTRY - Backend: " . date('Y-m-d H:i:s'));
echo "Written test log to backend location\n";

// Test parent log
ini_set('error_log', $parentLog);
error_log("TEST LOG ENTRY - Parent: " . date('Y-m-d H:i:s'));
echo "Written test log to parent location\n";

echo "\n=== RECENT LOG ENTRIES ===\n";
if (file_exists($parentLog)) {
    $lines = file($parentLog);
    $recentLines = array_slice($lines, -10);
    foreach ($recentLines as $line) {
        echo trim($line) . "\n";
    }
}
?>
