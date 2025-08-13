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

    public function createDiscount($data)
    {
        if (empty($data['store_id']) || empty($data['store_type_id']) || empty($data['percentage']) || empty($data['items'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Required fields are missing'];
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

    public function updateDiscount($discountId, $data)
{
    if (
        empty($discountId) || 
        empty($data['store_id']) || 
        empty($data['store_type_id']) || 
        empty($data['percentage']) || 
        empty($data['items'])
    ) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Required fields are missing'];
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

public function deleteDiscount($id, $storeId)
{
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


    public function getAllDiscountsByStore($storeId)
{
    if (empty($storeId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Store ID is required'];
    }

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

public function getAllDiscountsByStoreWithDetails($storeId)
{
    if (empty($storeId)) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Store ID is required'];
    }

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
