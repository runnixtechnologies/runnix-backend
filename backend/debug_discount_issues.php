<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once 'config/cors.php';

use Model\Discount;
use Model\FoodItem;

echo "<h2>Discount Debug Information</h2>";

// Test database connection
try {
    $discountModel = new Discount();
    $foodItemModel = new FoodItem();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check discounts table structure
echo "<h3>1. Checking Discounts Table</h3>";
try {
    $pdo = (new \Config\Database())->getConnection();
    $stmt = $pdo->query("DESCRIBE discounts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Discounts table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Error checking discounts table: " . $e->getMessage() . "</p>";
}

// Check discount_items table structure
echo "<h3>2. Checking Discount Items Table</h3>";
try {
    $stmt = $pdo->query("DESCRIBE discount_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Discount items table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Error checking discount_items table: " . $e->getMessage() . "</p>";
}

// Check existing discounts
echo "<h3>3. Checking Existing Discounts</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM discounts LIMIT 5");
    $discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($discounts)) {
        echo "<p>No discounts found in database</p>";
    } else {
        echo "<p>Found " . count($discounts) . " discounts:</p>";
        foreach ($discounts as $discount) {
            echo "<pre>" . json_encode($discount, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking existing discounts: " . $e->getMessage() . "</p>";
}

// Check discount items
echo "<h3>4. Checking Discount Items</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM discount_items LIMIT 5");
    $discountItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($discountItems)) {
        echo "<p>No discount items found in database</p>";
    } else {
        echo "<p>Found " . count($discountItems) . " discount items:</p>";
        foreach ($discountItems as $item) {
            echo "<pre>" . json_encode($item, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking discount items: " . $e->getMessage() . "</p>";
}

// Test sides query
echo "<h3>5. Testing Sides Query</h3>";
try {
    $sides = $foodItemModel->getAllFoodSidesByStoreId(12, 5, 0); // Assuming store_id 12
    if (empty($sides)) {
        echo "<p>No sides found for store_id 12</p>";
    } else {
        echo "<p>Found " . count($sides) . " sides:</p>";
        foreach ($sides as $side) {
            echo "<pre>" . json_encode($side, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error testing sides query: " . $e->getMessage() . "</p>";
}

echo "<h3>6. Test Discount Creation</h3>";
echo "<p>To test discount creation, use this JSON:</p>";
echo "<pre>{
  \"percentage\": 10,
  \"start_date\": \"2025-01-01\",
  \"end_date\": \"2025-12-31\",
  \"items\": [
    {
      \"item_id\": 1,
      \"item_type\": \"food_side\"
    }
  ]
}</pre>";

echo "<h3>7. Test Discount Update</h3>";
echo "<p>To test discount update, use this JSON:</p>";
echo "<pre>{
  \"id\": 1,
  \"percentage\": 15,
  \"start_date\": \"2025-01-01\",
  \"end_date\": \"2025-12-31\",
  \"items\": [
    {
      \"item_id\": 1,
      \"item_type\": \"food_side\"
    }
  ]
}</pre>";

echo "<h3>8. Test Discount Deletion</h3>";
echo "<p>To test discount deletion, send DELETE request with:</p>";
echo "<pre>{
  \"id\": 1
}</pre>";
?>
