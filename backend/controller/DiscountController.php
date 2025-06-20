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


    public function deleteDiscount($id)
    {
        if ($this->discountModel->delete($id)) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Discount deleted'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete discount'];
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

}
