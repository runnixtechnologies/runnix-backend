<?php

/**
 * Script to help identify and fix malformed .env file
 * Run this script to diagnose .env file issues
 */

echo "=== .env File Diagnostic Tool ===\n\n";

// Check for .env files in different locations
$possiblePaths = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env'
];

foreach ($possiblePaths as $path) {
    echo "Checking: $path\n";
    if (file_exists($path)) {
        echo "   ✅ File exists\n";
        
        // Read the file content
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        
        echo "   📄 File size: " . strlen($content) . " bytes\n";
        echo "   📄 Number of lines: " . count($lines) . "\n\n";
        
        // Check for problematic lines
        echo "   🔍 Checking for problematic lines...\n";
        foreach ($lines as $lineNum => $line) {
            $lineNum++; // 1-based line numbers
            
            // Check for lines with brackets and comments that might cause issues
            if (preg_match('/\[.*\/\/.*\]/', $line)) {
                echo "   ❌ Line $lineNum: Contains problematic format: " . trim($line) . "\n";
                echo "      This line should be removed or reformatted\n";
            }
            
            // Check for lines that start with spaces (invalid in .env)
            if (preg_match('/^\s+/', $line) && !empty(trim($line))) {
                echo "   ⚠️  Line $lineNum: Starts with whitespace: " . trim($line) . "\n";
            }
            
            // Check for lines without = sign (invalid format)
            if (!empty(trim($line)) && !preg_match('/^#/', $line) && !strpos($line, '=')) {
                echo "   ❌ Line $lineNum: Missing = sign: " . trim($line) . "\n";
            }
        }
        
        echo "\n   💡 Suggested fix:\n";
        echo "   1. Remove or comment out lines with brackets and comments\n";
        echo "   2. Ensure all environment variables follow KEY=VALUE format\n";
        echo "   3. Remove leading/trailing whitespace\n";
        echo "   4. Use # for comments, not //\n\n";
        
        // Show first few lines as example
        echo "   📋 First 10 lines of the file:\n";
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            echo "   " . ($i + 1) . ": " . $lines[$i] . "\n";
        }
        
    } else {
        echo "   ❌ File does not exist\n";
    }
    echo "\n";
}

echo "=== End of Diagnostic ===\n";
echo "\nTo fix the .env file:\n";
echo "1. Open the .env file in a text editor\n";
echo "2. Remove any lines with brackets like [Runnix // Optional: ...]\n";
echo "3. Ensure all lines follow KEY=VALUE format\n";
echo "4. Use # for comments, not //\n";
echo "5. Save the file and test again\n";

?>
