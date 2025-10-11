<?php
/**
 * Test script to verify sections update fix
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config/Database.php';
require_once 'controller/FoodItemController.php';

echo "=== TESTING SECTIONS UPDATE FIX ===\n\n";

try {
    // Test data - same as the failing request
    $testData = [
        'id' => '52',
        'category_id' => '1',
        'name' => 'Food 4',
        'price' => '4000.0',
        'short_description' => 'Four!',
        'sides' => '{"required":true,"max_quantity":1,"items":[10]}',
        'packs' => '{"required":true,"max_quantity":1,"items":[11]}',
        'sections' => '[{"section_id":12,"required":false,"max_quantity":1,"item_ids":[82]},{"section_id":13,"required":false,"max_quantity":1,"item_ids":[93]}]'
    ];

    $userData = [
        'user_id' => 28,
        'role' => 'merchant',
        'store_id' => 15,
        'store_type_id' => 1
    ];

    echo "1. Testing FoodItemController creation...\n";
    $controller = new \Controller\FoodItemController();
    echo "✓ Controller created successfully\n\n";

    echo "2. Testing JSON parsing...\n";
    $parsedSides = json_decode($testData['sides'], true);
    $parsedPacks = json_decode($testData['packs'], true);
    $parsedSections = json_decode($testData['sections'], true);
    
    echo "✓ Sides parsed: " . json_encode($parsedSides) . "\n";
    echo "✓ Packs parsed: " . json_encode($parsedPacks) . "\n";
    echo "✓ Sections parsed: " . json_encode($parsedSections) . "\n\n";

    echo "3. Testing update with parsed data...\n";
    $updateData = [
        'id' => $testData['id'],
        'category_id' => $testData['category_id'],
        'name' => $testData['name'],
        'price' => $testData['price'],
        'short_description' => $testData['short_description'],
        'sides' => $parsedSides,
        'packs' => $parsedPacks,
        'sections' => $parsedSections
    ];

    $result = $controller->update($updateData, $userData);
    
    if ($result['status'] === 'success') {
        echo "✓ Update successful!\n";
        echo "Response: " . json_encode($result) . "\n";
    } else {
        echo "✗ Update failed!\n";
        echo "Response: " . json_encode($result) . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
