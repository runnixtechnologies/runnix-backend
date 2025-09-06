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
        // Log the create request
        error_log("=== DISCOUNT CONTROLLER CREATE ===");
        error_log("Create data: " . json_encode($data));
        error_log("User data: " . json_encode($user));
        
        // Extract store_id and store_type_id from authenticated user
        if (!isset($user['store_id'])) {
            error_log("=== ERROR: STORE ID NOT FOUND ===");
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        $data['store_id'] = $user['store_id'];
        $data['store_type_id'] = $user['store_type_id'] ?? null;
        
        error_log("Store ID: " . $data['store_id']);
        error_log("Store Type ID: " . $data['store_type_id']);
        

        
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

        error_log("=== CALLING MODEL CREATE ===");
        error_log("About to call discountModel->create with data: " . json_encode($data));
        
        $discountId = $this->discountModel->create($data);
        
        error_log("=== MODEL CREATE RESULT ===");
        error_log("Create result: " . ($discountId ? 'SUCCESS - ID: ' . $discountId : 'FAILED'));
        
        if ($discountId) {
            error_log("=== CREATE SUCCESS ===");
            http_response_code(201);
            return ['status' => 'success', 'message' => 'Discount created', 'discount_id' => $discountId];
        } else {
            error_log("=== CREATE FAILED ===");
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to create discount'];
        }
    }

    public function updateDiscount($discountId, $data, $user)
{
    // Log the update request
    error_log("=== DISCOUNT CONTROLLER UPDATE ===");
    error_log("Discount ID: " . $discountId);
    error_log("Update data: " . json_encode($data));
    error_log("User data: " . json_encode($user));
    
    // Extract store_id and store_type_id from authenticated user
    if (!isset($user['store_id'])) {
        error_log("=== ERROR: STORE ID NOT FOUND ===");
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $data['store_id'] = $user['store_id'];
    $data['store_type_id'] = $user['store_type_id'] ?? null;
    
    // Validate that the discount ID in the data matches the parameter
    if (isset($data['id']) && $data['id'] != $discountId) {
        error_log("=== ERROR: ID MISMATCH ===");
        error_log("Parameter discountId: " . $discountId);
        error_log("Data ID: " . $data['id']);
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Discount ID mismatch. The ID in the request body must match the URL parameter.'];
    }
    
    // Ensure the data contains the correct ID
    $data['id'] = $discountId;
    
    error_log("Store ID: " . $data['store_id']);
    error_log("Store Type ID: " . $data['store_type_id']);
    
    if (empty($discountId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Discount ID is required'];
    }
    
    // Validate that the discount exists and belongs to the user
    error_log("=== VALIDATING DISCOUNT OWNERSHIP ===");
    $existingDiscount = $this->discountModel->getById($discountId);
    error_log("Existing discount: " . json_encode($existingDiscount));
    
    if (!$existingDiscount) {
        error_log("=== ERROR: DISCOUNT NOT FOUND ===");
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Discount not found'];
    }
    
    if ($existingDiscount['store_id'] != $data['store_id']) {
        error_log("=== ERROR: UNAUTHORIZED UPDATE ===");
        error_log("Existing discount store_id: " . $existingDiscount['store_id']);
        error_log("User store_id: " . $data['store_id']);
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to update this discount'];
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

    error_log("=== CALLING MODEL UPDATE ===");
    error_log("About to call discountModel->update with ID: " . $discountId);
    error_log("Data being passed to model: " . json_encode($data));
    
    $updated = $this->discountModel->update($discountId, $data);
    
    error_log("=== MODEL UPDATE RESULT ===");
    error_log("Update result: " . ($updated ? 'SUCCESS' : 'FAILED'));
    
    if ($updated) {
        error_log("=== UPDATE SUCCESS ===");
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Discount updated successfully'];
    } else {
        error_log("=== UPDATE FAILED ===");
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Update failed. Check discount ID and store ownership.'];
    }
}

public function deleteDiscount($id, $user)
{
    // Log the delete request
    error_log("=== DISCOUNT CONTROLLER DELETE ===");
    error_log("Discount ID: " . $id);
    error_log("User data: " . json_encode($user));
    
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        error_log("=== ERROR: STORE ID NOT FOUND ===");
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    $storeId = $user['store_id'];
    error_log("Store ID: " . $storeId);
    
    // Fetch discount and validate ownership
    error_log("=== FETCHING DISCOUNT FOR VALIDATION ===");
    $discount = $this->discountModel->getById($id);
    error_log("Fetched discount: " . json_encode($discount));

    if (!$discount) {
        error_log("=== ERROR: DISCOUNT NOT FOUND ===");
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Discount not found'];
    }
    
    if ($discount['store_id'] != $storeId) {
        error_log("=== ERROR: UNAUTHORIZED ACCESS ===");
        error_log("Discount store_id: " . $discount['store_id']);
        error_log("User store_id: " . $storeId);
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Unauthorized to delete this discount'];
    }

    // Proceed with deletion
    error_log("=== PROCEEDING WITH DELETION ===");
    $deleteResult = $this->discountModel->delete($id);
    error_log("Delete result: " . ($deleteResult ? 'SUCCESS' : 'FAILED'));
    
    if ($deleteResult) {
        error_log("=== DELETE SUCCESS ===");
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Discount deleted'];
    } else {
        error_log("=== DELETE FAILED ===");
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
