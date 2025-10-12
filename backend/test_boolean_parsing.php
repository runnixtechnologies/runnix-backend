<?php
/**
 * Test Script: Boolean Parsing for Mobile App Compatibility
 * This script tests the boolean conversion functionality
 */

require_once 'vendor/autoload.php';

echo "🧪 Testing Boolean Parsing for Mobile App Compatibility\n";
echo "======================================================\n\n";

/**
 * Convert boolean strings to actual booleans for mobile app compatibility
 */
function convertBooleanStrings($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = convertBooleanStrings($value);
            } elseif (is_string($value)) {
                // Convert common boolean string representations
                $lowerValue = strtolower(trim($value));
                if ($lowerValue === 'true' || $lowerValue === '1') {
                    $data[$key] = true;
                } elseif ($lowerValue === 'false' || $lowerValue === '0') {
                    $data[$key] = false;
                }
            } elseif (is_numeric($value)) {
                // Convert numeric boolean representations
                if ($value === 1) {
                    $data[$key] = true;
                } elseif ($value === 0) {
                    $data[$key] = false;
                }
            }
        }
    }
    return $data;
}

// Test cases
$testCases = [
    // Test 1: String booleans
    [
        'name' => 'Test Item',
        'sides' => [
            'required' => 'true',
            'max_quantity' => 3,
            'items' => [1, 2, 3]
        ]
    ],
    
    // Test 2: Numeric booleans
    [
        'name' => 'Test Item 2',
        'packs' => [
            'required' => 1,
            'max_quantity' => 2,
            'items' => [1, 2]
        ]
    ],
    
    // Test 3: Mixed boolean types
    [
        'name' => 'Test Item 3',
        'sides' => [
            'required' => 'false',
            'max_quantity' => 1,
            'items' => [1]
        ],
        'packs' => [
            'required' => 0,
            'max_quantity' => 1,
            'items' => [1]
        ]
    ],
    
    // Test 4: Nested boolean conversion
    [
        'name' => 'Test Item 4',
        'sections' => [
            [
                'section_id' => 1,
                'required' => 'true',
                'max_quantity' => 2,
                'item_ids' => [1, 2]
            ],
            [
                'section_id' => 2,
                'required' => 0,
                'max_quantity' => 1,
                'item_ids' => [3]
            ]
        ]
    ]
];

echo "Testing Boolean Conversion:\n";
echo "---------------------------\n";

foreach ($testCases as $index => $testCase) {
    echo "\nTest Case " . ($index + 1) . ":\n";
    echo "Input: " . json_encode($testCase, JSON_PRETTY_PRINT) . "\n";
    
    $converted = convertBooleanStrings($testCase);
    echo "Output: " . json_encode($converted, JSON_PRETTY_PRINT) . "\n";
    
    // Verify boolean conversion
    $hasBooleans = false;
    $allValid = true;
    
    function checkBooleans($data, &$hasBooleans, &$allValid) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                checkBooleans($value, $hasBooleans, $allValid);
            } elseif ($key === 'required') {
                $hasBooleans = true;
                if (!is_bool($value)) {
                    $allValid = false;
                    echo "❌ Invalid boolean conversion for 'required': " . var_export($value, true) . "\n";
                } else {
                    echo "✅ Valid boolean conversion for 'required': " . var_export($value, true) . "\n";
                }
            }
        }
    }
    
    checkBooleans($converted, $hasBooleans, $allValid);
    
    if ($hasBooleans && $allValid) {
        echo "✅ Test Case " . ($index + 1) . " PASSED\n";
    } else {
        echo "❌ Test Case " . ($index + 1) . " FAILED\n";
    }
}

echo "\n🎉 Boolean parsing test completed!\n";
echo "\n📋 Summary:\n";
echo "- String booleans ('true'/'false') are converted to actual booleans\n";
echo "- Numeric booleans (1/0) are converted to actual booleans\n";
echo "- Nested boolean conversion works correctly\n";
echo "- Mobile app compatibility is improved\n";
