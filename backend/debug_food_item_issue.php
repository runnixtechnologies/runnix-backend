<?php
/**
 * Debug script to identify the food item creation/retrieval inconsistency
 * This will help identify why duplicate name check fails but get all food items returns empty
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Model\Store;
use Config\Database;

echo "<h2>Food Item Debug Report</h2>\n";
echo "<p>Generated at: " . date('Y-m-d H:i:s') . "</p>\n";

try {
    $foodItemModel = new FoodItem();
    $storeModel = new Store();
    $conn = (new Database())->getConnection();
    
    echo "<h3>1. Database Connection Test</h3>\n";
    echo "✓ Database connection successful<br>\n";
    
    echo "<h3>2. Check Food Items Table Structure</h3>\n";
    
    // Check food_items table
    $stmt = $conn->query("DESCRIBE food_items");
    $foodItemColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<strong>food_items table columns:</strong><br>\n";
    foreach ($foodItemColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>\n";
    }
    
    echo "<h3>3. Store Analysis</h3>\n";
    
    // Get all stores
    $stmt = $conn->query("SELECT id, store_name, user_id, created_at FROM stores ORDER BY created_at DESC LIMIT 10");
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stores)) {
        echo "No stores found in the database.<br>\n";
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Store ID</th><th>Store Name</th><th>User ID</th><th>Created</th><th>Food Items Count</th></tr>\n";
        
        foreach ($stores as $store) {
            // Count food items for this store
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM food_items WHERE store_id = ? AND deleted = 0");
            $countStmt->execute([$store['id']]);
            $foodItemCount = $countStmt->fetchColumn();
            
            echo "<tr>";
            echo "<td>{$store['id']}</td>";
            echo "<td>{$store['store_name']}</td>";
            echo "<td>{$store['user_id']}</td>";
            echo "<td>{$store['created_at']}</td>";
            echo "<td>{$foodItemCount}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>4. Food Items Analysis</h3>\n";
    
    // Get recent food items
    $stmt = $conn->query("
        SELECT fi.*, s.store_name, s.user_id as store_user_id
        FROM food_items fi 
        LEFT JOIN stores s ON fi.store_id = s.id
        ORDER BY fi.created_at DESC 
        LIMIT 20
    ");
    $recentFoodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentFoodItems)) {
        echo "No food items found in the database.<br>\n";
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Store ID</th><th>Store Name</th><th>Name</th><th>Price</th><th>Status</th><th>Deleted</th><th>Created</th></tr>\n";
        
        foreach ($recentFoodItems as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['store_id']}</td>";
            echo "<td>{$item['store_name']}</td>";
            echo "<td>{$item['name']}</td>";
            echo "<td>{$item['price']}</td>";
            echo "<td>{$item['status']}</td>";
            echo "<td>{$item['deleted']}</td>";
            echo "<td>{$item['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>5. Duplicate Name Check Analysis</h3>\n";
    
    // Check for potential duplicate names
    $stmt = $conn->query("
        SELECT store_id, name, COUNT(*) as count 
        FROM food_items 
        WHERE deleted = 0 
        GROUP BY store_id, name 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicates)) {
        echo "⚠️ Found duplicate food item names:<br>\n";
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Store ID</th><th>Name</th><th>Count</th></tr>\n";
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>{$dup['store_id']}</td>";
            echo "<td>{$dup['name']}</td>";
            echo "<td>{$dup['count']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "✓ No duplicate food item names found<br>\n";
    }
    
    echo "<h3>6. Test Specific Store Food Items</h3>\n";
    
    // Test the getAllByStoreId method for each store
    foreach ($stores as $store) {
        echo "<h4>Store: {$store['store_name']} (ID: {$store['id']})</h4>\n";
        
        // Test direct SQL query
        $stmt = $conn->prepare("SELECT COUNT(*) FROM food_items WHERE store_id = ? AND deleted = 0");
        $stmt->execute([$store['id']]);
        $directCount = $stmt->fetchColumn();
        echo "Direct SQL count: {$directCount}<br>\n";
        
        // Test model method
        $modelItems = $foodItemModel->getAllByStoreId($store['id'], 10, 0, false);
        $modelCount = is_array($modelItems) ? count($modelItems) : 0;
        echo "Model method count: {$modelCount}<br>\n";
        
        if ($directCount != $modelCount) {
            echo "⚠️ <strong>INCONSISTENCY FOUND!</strong> Direct SQL count ({$directCount}) != Model count ({$modelCount})<br>\n";
            
            // Debug the SQL query
            echo "<strong>Debugging SQL query:</strong><br>\n";
            $debugSql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                               fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                               d.id as discount_id,
                               d.percentage as discount_percentage,
                               d.start_date as discount_start_date,
                               d.end_date as discount_end_date,
                               ROUND((fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)), 2) as calculated_discount_price,
                               COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                        FROM food_items fi
                        LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
                        LEFT JOIN discounts d ON di.discount_id = d.id AND d.status = 'active'
                        LEFT JOIN order_items oi ON fi.id = oi.item_id
                        WHERE fi.store_id = ? AND fi.deleted = 0 
                          AND (d.store_id IS NULL OR d.store_id = ?)
                        GROUP BY fi.id
                        ORDER BY fi.created_at DESC
                        LIMIT 10";
            
            $debugStmt = $conn->prepare($debugSql);
            $debugStmt->execute([$store['id'], $store['id']]);
            $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Debug query results: " . count($debugResults) . " items<br>\n";
            if (!empty($debugResults)) {
                echo "<pre>" . htmlspecialchars(json_encode($debugResults, JSON_PRETTY_PRINT)) . "</pre>\n";
            }
        } else {
            echo "✓ Counts match<br>\n";
        }
        echo "<br>\n";
    }
    
    echo "<h3>7. Potential Issues Check</h3>\n";
    
    // Check for food items with deleted = 1
    $stmt = $conn->query("SELECT COUNT(*) as deleted_count FROM food_items WHERE deleted = 1");
    $deletedCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deletedCount['deleted_count'] > 0) {
        echo "Found {$deletedCount['deleted_count']} deleted food items<br>\n";
    } else {
        echo "✓ No deleted food items found<br>\n";
    }
    
    // Check for food items with NULL store_id
    $stmt = $conn->query("SELECT COUNT(*) as null_store_count FROM food_items WHERE store_id IS NULL");
    $nullStoreCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($nullStoreCount['null_store_count'] > 0) {
        echo "⚠️ Found {$nullStoreCount['null_store_count']} food items with NULL store_id<br>\n";
    } else {
        echo "✓ No food items with NULL store_id found<br>\n";
    }
    
    // Check for orphaned food items (store_id doesn't exist in stores table)
    $stmt = $conn->query("
        SELECT COUNT(*) as orphaned_count 
        FROM food_items fi 
        LEFT JOIN stores s ON fi.store_id = s.id 
        WHERE s.id IS NULL AND fi.store_id IS NOT NULL
    ");
    $orphaned = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orphaned['orphaned_count'] > 0) {
        echo "⚠️ Found {$orphaned['orphaned_count']} orphaned food items (store_id doesn't exist)<br>\n";
    } else {
        echo "✓ No orphaned food items found<br>\n";
    }
    
    echo "<h3>8. Error Log Check</h3>\n";
    $errorLogPath = __DIR__ . '/php-error.log';
    if (file_exists($errorLogPath)) {
        $logSize = filesize($errorLogPath);
        echo "Error log file exists: {$errorLogPath} (Size: " . number_format($logSize) . " bytes)<br>\n";
        
        if ($logSize > 0) {
            echo "<strong>Last 20 lines of error log:</strong><br>\n";
            $lines = file($errorLogPath);
            $lastLines = array_slice($lines, -20);
            echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>\n";
        }
    } else {
        echo "No error log file found at: {$errorLogPath}<br>\n";
    }
    
} catch (Exception $e) {
    echo "<h3>Error</h3>\n";
    echo "Error: " . $e->getMessage() . "<br>\n";
    echo "File: " . $e->getFile() . "<br>\n";
    echo "Line: " . $e->getLine() . "<br>\n";
}

echo "<hr>\n";
echo "<p><em>Food item debug report completed. Check the results above for any inconsistencies.</em></p>\n";
?>
