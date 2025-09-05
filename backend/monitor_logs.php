<?php
// Simple script to monitor error logs for food item creation requests
// Run this in terminal: php monitor_logs.php

$logFile = __DIR__ . '/../php-error.log';

if (!file_exists($logFile)) {
    echo "Error log file not found at: $logFile\n";
    echo "Please check the path and try again.\n";
    exit(1);
}

echo "Monitoring error logs for food item creation requests...\n";
echo "Log file: $logFile\n";
echo "Press Ctrl+C to stop monitoring\n\n";

// Clear the screen
system('clear');

$lastSize = filesize($logFile);

while (true) {
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastSize) {
        // New content added to log file
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastSize);
        $newContent = fread($handle, $currentSize - $lastSize);
        fclose($handle);
        
        // Only show lines related to food item creation
        $lines = explode("\n", $newContent);
        foreach ($lines as $line) {
            if (strpos($line, 'FOOD ITEM') !== false || 
                strpos($line, 'SIDES') !== false || 
                strpos($line, 'create_food_item') !== false) {
                echo "[" . date('Y-m-d H:i:s') . "] " . trim($line) . "\n";
            }
        }
        
        $lastSize = $currentSize;
    }
    
    sleep(1); // Check every second
}
?>
