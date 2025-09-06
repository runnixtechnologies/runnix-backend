<?php
/**
 * Debug script to help identify discount deletion and editing issues
 * Run this script to check for common problems
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once 'config/cors.php';

use Model\Discount;
use Config\Database;

echo "<h2>Discount Debug Report</h2>\n";
echo "<p>Generated at: " . date('Y-m-d H:i:s') . "</p>\n";

try {
    $discountModel = new Discount();
    $conn = (new Database())->getConnection();
    
    echo "<h3>1. Database Connection Test</h3>\n";
    echo "✓ Database connection successful<br>\n";
    
    echo "<h3>2. Check Discount Tables Structure</h3>\n";
    
    // Check discounts table
    $stmt = $conn->query("DESCRIBE discounts");
    $discountColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<strong>discounts table columns:</strong><br>\n";
    foreach ($discountColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>\n";
    }
    
    // Check discount_items table
    $stmt = $conn->query("DESCRIBE discount_items");
    $discountItemsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<br><strong>discount_items table columns:</strong><br>\n";
    foreach ($discountItemsColumns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>\n";
    }
    
    echo "<h3>3. Recent Discount Activity</h3>\n";
    
    // Get recent discounts
    $stmt = $conn->query("
        SELECT d.*, 
               COUNT(di.id) as item_count,
               GROUP_CONCAT(CONCAT(di.item_type, ':', di.item_id)) as items
        FROM discounts d 
        LEFT JOIN discount_items di ON d.id = di.discount_id 
        GROUP BY d.id 
        ORDER BY d.created_at DESC 
        LIMIT 10
    ");
    $recentDiscounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentDiscounts)) {
        echo "No discounts found in the database.<br>\n";
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Store ID</th><th>Percentage</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Item Count</th><th>Items</th><th>Created</th></tr>\n";
        
        foreach ($recentDiscounts as $discount) {
            echo "<tr>";
            echo "<td>{$discount['id']}</td>";
            echo "<td>{$discount['store_id']}</td>";
            echo "<td>{$discount['percentage']}%</td>";
            echo "<td>{$discount['start_date']}</td>";
            echo "<td>{$discount['end_date']}</td>";
            echo "<td>{$discount['status']}</td>";
            echo "<td>{$discount['item_count']}</td>";
            echo "<td>{$discount['items']}</td>";
            echo "<td>{$discount['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>4. Store Analysis</h3>\n";
    
    // Get store statistics
    $stmt = $conn->query("
        SELECT s.id, s.store_name, s.biz_email,
               COUNT(d.id) as discount_count,
               MAX(d.created_at) as last_discount_created
        FROM stores s 
        LEFT JOIN discounts d ON s.id = d.store_id 
        GROUP BY s.id 
        ORDER BY discount_count DESC
    ");
    $storeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Store ID</th><th>Store Name</th><th>Email</th><th>Discount Count</th><th>Last Discount</th></tr>\n";
    
    foreach ($storeStats as $store) {
        echo "<tr>";
        echo "<td>{$store['id']}</td>";
        echo "<td>{$store['store_name']}</td>";
        echo "<td>{$store['biz_email']}</td>";
        echo "<td>{$store['discount_count']}</td>";
        echo "<td>{$store['last_discount_created']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>5. Potential Issues Check</h3>\n";
    
    // Check for orphaned discount_items
    $stmt = $conn->query("
        SELECT COUNT(*) as orphaned_count 
        FROM discount_items di 
        LEFT JOIN discounts d ON di.discount_id = d.id 
        WHERE d.id IS NULL
    ");
    $orphaned = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orphaned['orphaned_count'] > 0) {
        echo "⚠️ Found {$orphaned['orphaned_count']} orphaned discount_items (items without parent discount)<br>\n";
    } else {
        echo "✓ No orphaned discount_items found<br>\n";
    }
    
    // Check for discounts without items
    $stmt = $conn->query("
        SELECT COUNT(*) as empty_discounts 
        FROM discounts d 
        LEFT JOIN discount_items di ON d.id = di.discount_id 
        WHERE di.id IS NULL
    ");
    $empty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($empty['empty_discounts'] > 0) {
        echo "⚠️ Found {$empty['empty_discounts']} discounts without items<br>\n";
    } else {
        echo "✓ All discounts have associated items<br>\n";
    }
    
    // Check for duplicate discount IDs (should never happen with auto-increment)
    $stmt = $conn->query("
        SELECT id, COUNT(*) as count 
        FROM discounts 
        GROUP BY id 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicates)) {
        echo "⚠️ Found duplicate discount IDs:<br>\n";
        foreach ($duplicates as $dup) {
            echo "- ID {$dup['id']} appears {$dup['count']} times<br>\n";
        }
    } else {
        echo "✓ No duplicate discount IDs found<br>\n";
    }
    
    echo "<h3>6. Error Log Check</h3>\n";
    $errorLogPath = __DIR__ . '/php-error.log';
    if (file_exists($errorLogPath)) {
        $logSize = filesize($errorLogPath);
        echo "Error log file exists: {$errorLogPath} (Size: " . number_format($logSize) . " bytes)<br>\n";
        
        if ($logSize > 0) {
            echo "<strong>Last 10 lines of error log:</strong><br>\n";
            $lines = file($errorLogPath);
            $lastLines = array_slice($lines, -10);
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
echo "<p><em>Debug report completed. Check the results above for any issues.</em></p>\n";
?>