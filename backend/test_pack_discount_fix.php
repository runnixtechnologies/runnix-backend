<?php
/**
 * Test script to verify pack endpoints only return discount data when appropriate
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\Pack;
use Config\Database;

echo "=== Testing Pack Discount Data Fix ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Get a store to test with
    $query = "SELECT id, user_id, store_name FROM stores LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "❌ No stores found in database\n";
        exit;
    }
    
    echo "Using store: ID=" . $store['id'] . ", Name=" . $store['store_name'] . "\n";
    
    // Test the Pack model directly
    echo "\n1. Testing Pack model directly...\n";
    $packModel = new Pack();
    
    // Test getAll method
    echo "Testing getAll method...\n";
    $packs = $packModel->getAll($store['id'], 10, 0);
    echo "Result: " . count($packs) . " packs found\n";
    
    if (!empty($packs)) {
        echo "\nFirst pack data:\n";
        echo json_encode($packs[0], JSON_PRETTY_PRINT) . "\n";
        
        // Check if discount fields are present only when appropriate
        $firstPack = $packs[0];
        if (isset($firstPack['discount_id']) && $firstPack['discount_id']) {
            echo "✅ Pack has discount - discount fields should be present\n";
            $requiredDiscountFields = ['discount_id', 'percentage', 'discount_price', 'discount_start_date', 'discount_end_date'];
            foreach ($requiredDiscountFields as $field) {
                if (isset($firstPack[$field])) {
                    echo "  ✅ $field: " . $firstPack[$field] . "\n";
                } else {
                    echo "  ❌ $field: missing\n";
                }
            }
        } else {
            echo "✅ Pack has no discount - discount fields should be absent\n";
            $discountFields = ['discount_id', 'percentage', 'discount_price', 'discount_start_date', 'discount_end_date'];
            $hasDiscountFields = false;
            foreach ($discountFields as $field) {
                if (isset($firstPack[$field])) {
                    echo "  ❌ $field: present (should be absent)\n";
                    $hasDiscountFields = true;
                }
            }
            if (!$hasDiscountFields) {
                echo "  ✅ All discount fields properly absent\n";
            }
        }
        
        // Test getPackById method
        echo "\nTesting getPackById method...\n";
        $packId = $packs[0]['id'];
        $pack = $packModel->getPackById($packId);
        
        if ($pack) {
            echo "Pack found: " . json_encode($pack, JSON_PRETTY_PRINT) . "\n";
            
            // Check discount fields
            if (isset($pack['discount_id']) && $pack['discount_id']) {
                echo "✅ Pack has discount - discount fields should be present\n";
            } else {
                echo "✅ Pack has no discount - discount fields should be absent\n";
                $discountFields = ['discount_id', 'percentage', 'discount_price', 'discount_start_date', 'discount_end_date'];
                $hasDiscountFields = false;
                foreach ($discountFields as $field) {
                    if (isset($pack[$field])) {
                        echo "  ❌ $field: present (should be absent)\n";
                        $hasDiscountFields = true;
                    }
                }
                if (!$hasDiscountFields) {
                    echo "  ✅ All discount fields properly absent\n";
                }
            }
        } else {
            echo "❌ Pack not found\n";
        }
    } else {
        echo "No packs found for this store\n";
        
        // Create a test pack without discount
        echo "Creating test pack without discount...\n";
        $testPackData = [
            'store_id' => $store['id'],
            'name' => 'Test Pack No Discount',
            'price' => 10.00,
            'discount' => 0,
            'percentage' => 0,
            'status' => 'active'
        ];
        
        $created = $packModel->create($testPackData);
        if ($created) {
            echo "✅ Test pack created\n";
            
            // Test the created pack
            $packs = $packModel->getAll($store['id'], 10, 0);
            if (!empty($packs)) {
                $testPack = $packs[0];
                echo "Test pack data:\n";
                echo json_encode($testPack, JSON_PRETTY_PRINT) . "\n";
                
                // Check that discount fields are absent
                $discountFields = ['discount_id', 'percentage', 'discount_price', 'discount_start_date', 'discount_end_date'];
                $hasDiscountFields = false;
                foreach ($discountFields as $field) {
                    if (isset($testPack[$field])) {
                        echo "  ❌ $field: present (should be absent)\n";
                        $hasDiscountFields = true;
                    }
                }
                if (!$hasDiscountFields) {
                    echo "  ✅ All discount fields properly absent for pack without discount\n";
                }
            }
        } else {
            echo "❌ Failed to create test pack\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
