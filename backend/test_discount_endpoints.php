<?php
/**
 * Test script for discount endpoints
 * Tests create, update, and delete discount functionality
 */

// Test data for creating a discount
$testDiscountData = [
    'percentage' => 15.0,
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'items' => [
        [
            'item_id' => 1,
            'item_type' => 'item'
        ],
        [
            'item_id' => 2,
            'item_type' => 'food_item'
        ],
        [
            'item_id' => 3,
            'item_type' => 'side'
        ],
        [
            'item_id' => 4,
            'item_type' => 'pack'
        ],
        [
            'item_id' => 5,
            'item_type' => 'food_section_item'
        ]
    ]
];

echo "=== DISCOUNT ENDPOINTS TEST ===\n\n";

echo "1. CREATE DISCOUNT TEST\n";
echo "Expected JSON for create_discount.php:\n";
echo json_encode($testDiscountData, JSON_PRETTY_PRINT) . "\n\n";

echo "Expected Response:\n";
echo json_encode([
    'status' => 'success',
    'message' => 'Discount created',
    'discount_id' => 123
], JSON_PRETTY_PRINT) . "\n\n";

echo "2. UPDATE DISCOUNT TEST\n";
$updateData = $testDiscountData;
$updateData['id'] = 123;
$updateData['percentage'] = 20.0;
$updateData['end_date'] = '2024-02-15';

echo "Expected JSON for edit_discount.php:\n";
echo json_encode($updateData, JSON_PRETTY_PRINT) . "\n\n";

echo "Expected Response:\n";
echo json_encode([
    'status' => 'success',
    'message' => 'Discount updated successfully'
], JSON_PRETTY_PRINT) . "\n\n";

echo "3. DELETE DISCOUNT TEST\n";
echo "Expected JSON for delete_discount.php:\n";
echo json_encode(['id' => 123], JSON_PRETTY_PRINT) . "\n\n";

echo "Expected Response:\n";
echo json_encode([
    'status' => 'success',
    'message' => 'Discount deleted'
], JSON_PRETTY_PRINT) . "\n\n";

echo "4. VALIDATION TESTS\n";
echo "Invalid percentage (should fail):\n";
$invalidData = $testDiscountData;
$invalidData['percentage'] = 150; // Invalid: > 100
echo json_encode($invalidData, JSON_PRETTY_PRINT) . "\n";
echo "Expected: 400 Bad Request - Percentage must be between 0 and 100\n\n";

echo "Invalid item_type (should fail):\n";
$invalidData = $testDiscountData;
$invalidData['items'][0]['item_type'] = 'invalid_type';
echo json_encode($invalidData, JSON_PRETTY_PRINT) . "\n";
echo "Expected: 400 Bad Request - Each item must have a valid item_type\n\n";

echo "Invalid date range (should fail):\n";
$invalidData = $testDiscountData;
$invalidData['start_date'] = '2024-01-31';
$invalidData['end_date'] = '2024-01-01'; // End before start
echo json_encode($invalidData, JSON_PRETTY_PRINT) . "\n";
echo "Expected: 400 Bad Request - End date must be after start date\n\n";

echo "=== ENDPOINT URLS ===\n";
echo "POST /backend/api/create_discount.php\n";
echo "PUT /backend/api/edit_discount.php\n";
echo "DELETE /backend/api/delete_discount.php\n";
echo "GET /backend/api/get_all_discounts_by_store.php\n";
echo "GET /backend/api/get_discounts_by_item.php\n\n";

echo "=== DISCOUNT TABLE STRUCTURE ===\n";
echo "discounts table:\n";
echo "- id (primary key)\n";
echo "- store_id\n";
echo "- store_type_id\n";
echo "- percentage\n";
echo "- start_date\n";
echo "- end_date\n";
echo "- status\n";
echo "- created_at\n";
echo "- updated_at\n\n";

echo "discount_items table:\n";
echo "- discount_id (foreign key to discounts.id)\n";
echo "- item_id (ID of the item being discounted)\n";
echo "- item_type (item, food_item, side, pack, food_section_item)\n";
echo "- created_at\n\n";

echo "=== SUPPORTED ITEM TYPES ===\n";
echo "- 'item': Regular items (from items table)\n";
echo "- 'food_item': Food items (from food_items table)\n";
echo "- 'side': Food sides (from food_sides table)\n";
echo "- 'pack': Packs (from packages table)\n";
echo "- 'food_section_item': Food section items (from food_section_items table)\n\n";

echo "=== GET ENDPOINTS WITH DISCOUNT SUPPORT ===\n";
echo "All these endpoints now return discount information when available:\n";
echo "- GET /backend/api/get_store_items.php (regular items)\n";
echo "- GET /backend/api/get_all_fooditems_by_store.php (food items)\n";
echo "- GET /backend/api/get_all_sides.php (food sides)\n";
echo "- GET /backend/api/get_all_packs.php (packs)\n";
echo "- GET /backend/api/get_all_section_items_in_store.php (food section items)\n";
echo "- GET /backend/api/get_food_by_id.php (single food item)\n";
echo "- GET /backend/api/get_foodside_by_id.php (single food side)\n";
echo "- GET /backend/api/get_packby_id.php (single pack)\n\n";

echo "Test completed!\n";
?>
