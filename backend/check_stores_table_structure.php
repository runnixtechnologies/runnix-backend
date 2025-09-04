<?php
/**
 * Check stores table structure to see what fields are available
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Config\Database;

echo "=== Checking Stores Table Structure ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check stores table structure
    echo "\n1. Checking stores table structure...\n";
    $query = "DESCRIBE stores";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Stores table columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if business_url or biz_url exists
    $hasBusinessUrl = false;
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['business_url', 'biz_url', 'website_url', 'url'])) {
            $hasBusinessUrl = true;
            echo "\n✅ Found business URL field: " . $column['Field'] . "\n";
            break;
        }
    }
    
    if (!$hasBusinessUrl) {
        echo "\n❌ No business URL field found in stores table\n";
        echo "Available fields: " . implode(', ', array_column($columns, 'Field')) . "\n";
    }
    
    // Check if there are any stores to see sample data
    echo "\n2. Checking sample store data...\n";
    $query = "SELECT * FROM stores LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($store) {
        echo "Sample store data:\n";
        foreach ($store as $key => $value) {
            echo "  - $key: " . ($value ?: 'NULL') . "\n";
        }
    } else {
        echo "No stores found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
