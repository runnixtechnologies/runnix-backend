<?php
require_once '../vendor/autoload.php';
require_once 'config/Database.php';

use Model\FoodItem;
use Model\Pack;

// Test database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test Food Sides
echo "=== Testing Food Sides ===\n";
$foodItemModel = new FoodItem($conn);

// Test get all sides
echo "Testing getAllFoodSidesByStoreId...\n";
$sides = $foodItemModel->getAllFoodSidesByStoreId(1, 5, 0); // Assuming store_id 1
if (!empty($sides)) {
    echo "✅ Found " . count($sides) . " sides\n";
    $firstSide = $sides[0];
    echo "First side fields: " . implode(', ', array_keys($firstSide)) . "\n";
    
    // Check required discount fields
    $requiredFields = ['percentage', 'discount_price', 'discount_id', 'discount_start_date', 'discount_end_date', 'total_orders'];
    foreach ($requiredFields as $field) {
        if (array_key_exists($field, $firstSide)) {
            echo "✅ Field '$field' exists with value: " . json_encode($firstSide[$field]) . "\n";
        } else {
            echo "❌ Field '$field' missing\n";
        }
    }
} else {
    echo "⚠️  No sides found for store_id 1\n";
}

// Test get side by ID
echo "\nTesting getFoodSideById...\n";
if (!empty($sides)) {
    $sideId = $sides[0]['id'];
    $side = $foodItemModel->getFoodSideById($sideId);
    if ($side) {
        echo "✅ Found side with ID: $sideId\n";
        echo "Side fields: " . implode(', ', array_keys($side)) . "\n";
        
        // Check required discount fields
        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $side)) {
                echo "✅ Field '$field' exists with value: " . json_encode($side[$field]) . "\n";
            } else {
                echo "❌ Field '$field' missing\n";
            }
        }
    } else {
        echo "❌ Could not retrieve side by ID\n";
    }
}

// Test Packs
echo "\n=== Testing Packs ===\n";
$packModel = new Pack($conn);

// Test get all packs
echo "Testing getAll...\n";
$packs = $packModel->getAll(1, 5, 0); // Assuming store_id 1
if (!empty($packs)) {
    echo "✅ Found " . count($packs) . " packs\n";
    $firstPack = $packs[0];
    echo "First pack fields: " . implode(', ', array_keys($firstPack)) . "\n";
    
    // Check required discount fields
    foreach ($requiredFields as $field) {
        if (array_key_exists($field, $firstPack)) {
            echo "✅ Field '$field' exists with value: " . json_encode($firstPack[$field]) . "\n";
        } else {
            echo "❌ Field '$field' missing\n";
        }
    }
} else {
    echo "⚠️  No packs found for store_id 1\n";
}

// Test get pack by ID
echo "\nTesting getPackById...\n";
if (!empty($packs)) {
    $packId = $packs[0]['id'];
    $pack = $packModel->getPackById($packId);
    if ($pack) {
        echo "✅ Found pack with ID: $packId\n";
        echo "Pack fields: " . implode(', ', array_keys($pack)) . "\n";
        
        // Check required discount fields
        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $pack)) {
                echo "✅ Field '$field' exists with value: " . json_encode($pack[$field]) . "\n";
            } else {
                echo "❌ Field '$field' missing\n";
            }
        }
    } else {
        echo "❌ Could not retrieve pack by ID\n";
    }
}

// Test Food Sections
echo "\n=== Testing Food Sections ===\n";

// Test get all sections
echo "Testing getAllFoodSectionsByStoreId...\n";
$sections = $foodItemModel->getAllFoodSectionsByStoreId(1, 5, 0); // Assuming store_id 1
if (!empty($sections)) {
    echo "✅ Found " . count($sections) . " sections\n";
    $firstSection = $sections[0];
    echo "First section fields: " . implode(', ', array_keys($firstSection)) . "\n";
    
    // Check required discount fields
    foreach ($requiredFields as $field) {
        if (array_key_exists($field, $firstSection)) {
            echo "✅ Field '$field' exists with value: " . json_encode($firstSection[$field]) . "\n";
        } else {
            echo "❌ Field '$field' missing\n";
        }
    }
} else {
    echo "⚠️  No sections found for store_id 1\n";
}

// Test get section by ID
echo "\nTesting getFoodSectionById...\n";
if (!empty($sections)) {
    $sectionId = $sections[0]['id'];
    $section = $foodItemModel->getFoodSectionById($sectionId);
    if ($section) {
        echo "✅ Found section with ID: $sectionId\n";
        echo "Section fields: " . implode(', ', array_keys($section)) . "\n";
        
        // Check required discount fields
        foreach ($requiredFields as $field) {
            if (array_key_exists($field, $section)) {
                echo "✅ Field '$field' exists with value: " . json_encode($section[$field]) . "\n";
            } else {
                echo "❌ Field '$field' missing\n";
            }
        }
    } else {
        echo "❌ Could not retrieve section by ID\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "All discount fields should now be consistently returned for sides, packs, and sections.\n";
echo "Fields: percentage, discount_price, discount_id, discount_start_date, discount_end_date, total_orders\n";
?>
