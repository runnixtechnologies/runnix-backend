<?php
/**
 * Test script to debug food item creation data formats
 * This helps identify what the mobile app might be sending
 */

echo "=== Food Item Creation Data Format Test ===\n\n";

// Test case 1: Expected structured format
echo "Test Case 1: Expected Structured Format\n";
$testData1 = [
    'store_id' => 1,
    'name' => 'Test Food Item',
    'price' => 100,
    'description' => 'Test description',
    'category_id' => 1,
    'sides' => [
        'required' => true,
        'max_quantity' => 1,
        'items' => [1, 2, 3]
    ]
];
echo "Data: " . json_encode($testData1, JSON_PRETTY_PRINT) . "\n";
echo "Sides type: " . gettype($testData1['sides']) . "\n";
echo "Sides keys: " . implode(', ', array_keys($testData1['sides'])) . "\n";
echo "Items type: " . gettype($testData1['sides']['items']) . "\n";
echo "Items: " . json_encode($testData1['sides']['items']) . "\n\n";

// Test case 2: Simple array format
echo "Test Case 2: Simple Array Format\n";
$testData2 = [
    'store_id' => 1,
    'name' => 'Test Food Item',
    'price' => 100,
    'description' => 'Test description',
    'category_id' => 1,
    'sides' => [1, 2, 3] // Just array of IDs
];
echo "Data: " . json_encode($testData2, JSON_PRETTY_PRINT) . "\n";
echo "Sides type: " . gettype($testData2['sides']) . "\n";
echo "Sides: " . json_encode($testData2['sides']) . "\n\n";

// Test case 3: What might be going wrong - missing items key
echo "Test Case 3: Missing Items Key (This would cause the error)\n";
$testData3 = [
    'store_id' => 1,
    'name' => 'Test Food Item',
    'price' => 100,
    'description' => 'Test description',
    'category_id' => 1,
    'sides' => [
        'required' => true,
        'max_quantity' => 1
        // Missing 'items' key - this would cause "Sides items must be an array of side IDs"
    ]
];
echo "Data: " . json_encode($testData3, JSON_PRETTY_PRINT) . "\n";
echo "Sides type: " . gettype($testData3['sides']) . "\n";
echo "Sides keys: " . implode(', ', array_keys($testData3['sides'])) . "\n";
echo "Has items: " . (isset($testData3['sides']['items']) ? 'yes' : 'no') . "\n\n";

// Test case 4: Items as string instead of array
echo "Test Case 4: Items as String (This would also cause the error)\n";
$testData4 = [
    'store_id' => 1,
    'name' => 'Test Food Item',
    'price' => 100,
    'description' => 'Test description',
    'category_id' => 1,
    'sides' => [
        'required' => true,
        'max_quantity' => 1,
        'items' => "1,2,3" // String instead of array - this would cause the error
    ]
];
echo "Data: " . json_encode($testData4, JSON_PRETTY_PRINT) . "\n";
echo "Sides type: " . gettype($testData4['sides']) . "\n";
echo "Items type: " . gettype($testData4['sides']['items']) . "\n";
echo "Items: " . json_encode($testData4['sides']['items']) . "\n";
echo "Is items array: " . (is_array($testData4['sides']['items']) ? 'yes' : 'no') . "\n\n";

// Test case 5: Items as null
echo "Test Case 5: Items as Null (This would also cause the error)\n";
$testData5 = [
    'store_id' => 1,
    'name' => 'Test Food Item',
    'price' => 100,
    'description' => 'Test description',
    'category_id' => 1,
    'sides' => [
        'required' => true,
        'max_quantity' => 1,
        'items' => null // Null instead of array - this would cause the error
    ]
];
echo "Data: " . json_encode($testData5, JSON_PRETTY_PRINT) . "\n";
echo "Sides type: " . gettype($testData5['sides']) . "\n";
echo "Items type: " . gettype($testData5['sides']['items']) . "\n";
echo "Items: " . json_encode($testData5['sides']['items']) . "\n";
echo "Is items array: " . (is_array($testData5['sides']['items']) ? 'yes' : 'no') . "\n\n";

echo "=== Analysis ===\n";
echo "The error 'Sides items must be an array of side IDs' occurs when:\n";
echo "1. The 'items' key is missing from the sides object\n";
echo "2. The 'items' value is not an array (e.g., string, null, number)\n";
echo "3. The data structure is malformed during transmission\n\n";

echo "=== Next Steps ===\n";
echo "1. Check the error logs after the mobile app tries to create a food item\n";
echo "2. The debug logging will show exactly what data structure is received\n";
echo "3. Compare it with the expected formats above\n";
echo "4. Fix the mobile app to send data in the correct format\n";
?>
