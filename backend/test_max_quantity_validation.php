<?php
// Test script to debug max_quantity validation
echo "=== Testing max_quantity validation ===\n\n";

// Test case 1: What mobile app is sending (1 as max_quantity)
$testData1 = [
    'sides' => [
        'required' => true,
        'max_quantity' => 1,
        'items' => [1, 2, 3]
    ]
];

echo "Test Case 1 - Mobile app data:\n";
echo "max_quantity value: " . $testData1['sides']['max_quantity'] . "\n";
echo "Type: " . gettype($testData1['sides']['max_quantity']) . "\n";
echo "is_numeric(): " . (is_numeric($testData1['sides']['max_quantity']) ? 'true' : 'false') . "\n";
echo "Value >= 0: " . ($testData1['sides']['max_quantity'] >= 0 ? 'true' : 'false') . "\n";
echo "Validation result: " . (!is_numeric($testData1['sides']['max_quantity']) || $testData1['sides']['max_quantity'] < 0 ? 'FAIL' : 'PASS') . "\n\n";

// Test case 2: String "1" (what might be happening)
$testData2 = [
    'sides' => [
        'required' => true,
        'max_quantity' => "1",
        'items' => [1, 2, 3]
    ]
];

echo "Test Case 2 - String '1':\n";
echo "max_quantity value: " . $testData2['sides']['max_quantity'] . "\n";
echo "Type: " . gettype($testData2['sides']['max_quantity']) . "\n";
echo "is_numeric(): " . (is_numeric($testData2['sides']['max_quantity']) ? 'true' : 'false') . "\n";
echo "Value >= 0: " . ($testData2['sides']['max_quantity'] >= 0 ? 'true' : 'false') . "\n";
echo "Validation result: " . (!is_numeric($testData2['sides']['max_quantity']) || $testData2['sides']['max_quantity'] < 0 ? 'FAIL' : 'PASS') . "\n\n";

// Test case 3: Different data types
$testCases = [
    'integer 1' => 1,
    'string "1"' => "1",
    'string "0"' => "0",
    'string "-1"' => "-1",
    'float 1.0' => 1.0,
    'string "abc"' => "abc",
    'null' => null,
    'empty string' => "",
    'boolean true' => true,
    'boolean false' => false
];

echo "Test Case 3 - Various data types:\n";
foreach ($testCases as $description => $value) {
    $isValid = is_numeric($value) && $value >= 0;
    echo sprintf("%-15s: Type=%s, Value=%s, is_numeric=%s, >=0=%s, Valid=%s\n", 
        $description, 
        gettype($value), 
        json_encode($value), 
        is_numeric($value) ? 'true' : 'false',
        $value >= 0 ? 'true' : 'false',
        $isValid ? 'PASS' : 'FAIL'
    );
}

echo "\n=== Analysis ===\n";
echo "The validation logic is: !is_numeric(max_quantity) || max_quantity < 0\n";
echo "This should work for both integer 1 and string '1' since is_numeric('1') returns true.\n";
echo "The issue might be in how the data is being received or processed before validation.\n";
?>
