<?php
namespace Controller;

use Model\Discount;

class DiscountController
{
    private $discountModel;

    public function __construct()
    {
        $this->discountModel = new Discount();
    }

    public function createDiscount($data, $user)
    {
        // Extract store_id and store_type_id from authenticated user
        if (!isset($user['store_id'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        $data['store_id'] = $user['store_id'];
        $data['store_type_id'] = $user['store_type_id'] ?? null;
        

        
        if (!isset($data['percentage']) || !is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Percentage must be a number between 0 and 100'];
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Items array is required and must be an array'];
        }
        
        // Validate start_date and end_date
        if (empty($data['start_date']) || empty($data['end_date'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Start date and end date are required'];
        }
        
        // Validate date format and logic
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        
        if (!$startDate || !$endDate) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD format'];
        }
        
        if ($startDate >= $endDate) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'End date must be after start date'];
        }
        
        // Validate each item in the items array
        foreach ($data['items'] as $item) {
            if (!isset($item['item_id']) || !is_numeric($item['item_id'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Each item must have a valid item_id'];
            }
            
            if (!isset($item['item_type']) || !in_array($item['item_type'], ['item', 'food_item', 'side', 'pack', 'food_section_item'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Each item must have a valid item_type (item, food_item, side, pack, or food_section_item)'];
            }
        }

        $discountId = $this->discountModel->create($data);
        if ($discountId) {
            http_response_code(201);
            return ['status' => 'success', 'message' => 'Discount created', 'discount_id' => $discountId];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to create discount'];
        }
    }

    public function updateDiscount($discountId, $data, $user)
{
    // Extract store_id and store_type_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $data['store_id'] = $user['store_id'];
    $data['store_type_id'] = $user['store_type_id'] ?? null;
    
    if (empty($discountId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Discount ID is required'];
    }
    
    if (!isset($data['percentage']) || !is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Percentage must be a number between 0 and 100'];
    }
    
    if (empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Items array is required and must be an array'];
    }
    
    // Validate start_date and end_date
    if (empty($data['start_date']) || empty($data['end_date'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Start date and end date are required'];
    }
    
    // Validate date format and logic
    $startDate = strtotime($data['start_date']);
    $endDate = strtotime($data['end_date']);
    
    if (!$startDate || !$endDate) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD format'];
    }
    
    if ($startDate >= $endDate) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'End date must be after start date'];
    }
    
    // Validate each item in the items array
    foreach ($data['items'] as $item) {
        if (!isset($item['item_id']) || !is_numeric($item['item_id'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Each item must have a valid item_id'];
        }
        
        if (!isset($item['item_type']) || !in_array($item['item_type'], ['item', 'food_item', 'side', 'pack', 'food_section_item'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Each item must have a valid item_type (item, food_item, side, pack, or food_section_item)'];
        }
    }

    $updated = $this->discountModel->update($discountId, $data);
    if ($updated) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Discount updated successfully'];
    } else {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Update failed. Check discount ID and store ownership.'];
    }
}

public function deleteDiscount($id, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $storeId = $user['store_id'];
    
    // Fetch discount and validate ownership
    $discount = $this->discountModel->getById($id);

    if (!$discount || $discount['store_id'] != $storeId) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to delete this discount'];
    }

    // Proceed with deletion
    if ($this->discountModel->delete($id)) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Discount deleted'];
    } else {
        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to delete discount'];
    }
}


    public function getAllDiscountsByStore($user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $storeId = $user['store_id'];

    $discounts = $this->discountModel->getAllByStoreId($storeId);

    if ($discounts) {
        http_response_code(200);
        return ['status' => 'success', 'data' => $discounts];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'No discounts found for this store'];
    }
}




    public function getDiscountsByItemId($itemId)
{
    if (empty($itemId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Item ID is required'];
    }

    $discounts = $this->discountModel->getByItemId($itemId);

    if ($discounts) {
        http_response_code(200);
        return ['status' => 'success', 'data' => $discounts];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'No discounts found for this item'];
    }
}

public function getDiscountsBySideId($sideId)
{
    if (empty($sideId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Side ID is required'];
    }

    $discounts = $this->discountModel->getBySideId($sideId);

    if ($discounts) {
        http_response_code(200);
        return ['status' => 'success', 'data' => $discounts];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'No discounts found for this side'];
    }
}

public function getDiscountsByPackId($packId)
{
    if (empty($packId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack ID is required'];
    }

    $discounts = $this->discountModel->getByPackId($packId);

    if ($discounts) {
        http_response_code(200);
        return ['status' => 'success', 'data' => $discounts];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'No discounts found for this pack'];
    }
}

public function getAllDiscountsByStoreWithDetails($user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $storeId = $user['store_id'];

    $discounts = $this->discountModel->getAllByStoreIdWithDetails($storeId);

    if ($discounts) {
        http_response_code(200);
        return ['status' => 'success', 'data' => $discounts];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'No discounts found for this store'];
    }
}

}
