<?php
require_once '../vendor/autoload.php';
require_once 'config/Database.php';

use Controller\FoodItemController;

// Test script to debug food item validation
echo "=== Testing Food Item Validation ===\n\n";

// Test case 1: What mobile app might be sending
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
    ],
    'packs' => [
        'required' => false,
        'max_quantity' => 2,
        'items' => [1, 2]
    ],
    'sections' => [
        'required' => true,
        'max_quantity' => 1,
        'items' => [1],
        'item_ids' => [1, 2, 3]
    ]
];

echo "Test Case 1 - Mobile app data structure:\n";
echo "Sides: " . json_encode($testData1['sides']) . "\n";
echo "Packs: " . json_encode($testData1['packs']) . "\n";
echo "Sections: " . json_encode($testData1['sections']) . "\n\n";

// Test case 2: Different max_quantity values
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

echo "Test Case 2 - max_quantity validation:\n";
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

echo "\n=== Expected Behavior ===\n";
echo "The validation should:\n";
echo "1. Detect structured format when sides/packs/sections have required, max_quantity, items keys\n";
echo "2. Validate max_quantity is numeric and >= 0\n";
echo "3. Accept both integer 1 and string '1' as valid max_quantity values\n";
echo "4. Log detailed information about what's being validated\n\n";

echo "=== Next Steps ===\n";
echo "1. Try creating a food item with this data structure\n";
echo "2. Check the error logs for the debug information\n";
echo "3. The logs will show exactly what data structure is detected and validated\n";
echo "4. This will help identify why the mobile app is getting the validation error\n";
?>
