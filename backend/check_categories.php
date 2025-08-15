<?php
/**
 * Simple script to check categories in the database
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== CATEGORIES IN DATABASE ===\n\n";
    
    // Check all categories
    $query = "SELECT id, name, status, store_type_id FROM categories ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($categories) {
        echo "Found " . count($categories) . " categories:\n\n";
        foreach ($categories as $cat) {
            $status = $cat['status'] == 'active' ? '✅' : '❌';
            echo "{$status} ID: {$cat['id']}, Name: '{$cat['name']}', Status: '{$cat['status']}', Store Type: {$cat['store_type_id']}\n";
        }
    } else {
        echo "❌ No categories found in database\n";
    }
    
    echo "\n=== STORES IN DATABASE ===\n\n";
    
    // Check all stores
    $storeQuery = "SELECT id, store_name, store_type_id, status FROM stores ORDER BY id";
    $storeStmt = $conn->prepare($storeQuery);
    $storeStmt->execute();
    $stores = $storeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($stores) {
        echo "Found " . count($stores) . " stores:\n\n";
        foreach ($stores as $store) {
            $status = $store['status'] == 'active' ? '✅' : '❌';
            echo "{$status} ID: {$store['id']}, Name: '{$store['store_name']}', Type: {$store['store_type_id']}, Status: '{$store['status']}'\n";
        }
    } else {
        echo "❌ No stores found in database\n";
    }
    
    echo "\n=== STORE TYPES IN DATABASE ===\n\n";
    
    // Check all store types
    $typeQuery = "SELECT id, name, status FROM store_types ORDER BY id";
    $typeStmt = $conn->prepare($typeQuery);
    $typeStmt->execute();
    $types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($types) {
        echo "Found " . count($types) . " store types:\n\n";
        foreach ($types as $type) {
            $status = $type['status'] == '1' ? '✅' : '❌';
            echo "{$status} ID: {$type['id']}, Name: '{$type['name']}', Status: '{$type['status']}'\n";
        }
    } else {
        echo "❌ No store types found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
