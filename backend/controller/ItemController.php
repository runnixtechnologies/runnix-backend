<?php

namespace Controller;

use Model\Item;

class ItemController
{
    private $itemModel;

    public function __construct()
    {
        $this->itemModel = new Item();
    }

    public function addItemsBulk($data)
    {
        if (!isset($data['store_id'], $data['category_id'], $data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid input: store_id, category_id, and items[] required."];
        }

        return $this->itemModel->bulkCreateItems($data['store_id'], $data['category_id'], $data['items']);
    }

    public function createSingleItem($data)
    {
        if (!isset($data['store_id'], $data['category_id'], $data['name'], $data['price'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required fields: store_id, category_id, name, price."];
        }

        $photo = $data['photo'] ?? null;
        return $this->itemModel->createSingleItem(
            $data['store_id'],
            $data['category_id'],
            $data['name'],
            $data['price'],
            $photo
        );
    }

    public function updateItem($data)
    {
        if (!isset($data['id'], $data['name'], $data['price'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required fields: id, name, price."];
        }

        return $this->itemModel->updateItem($data['id'], $data);
    }

    public function deleteItem($data)
    {
        if (!isset($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required field: id."];
        }

        return $this->itemModel->deleteItem($data['id']);
    }

    public function deactivateItem($data)
    {
        if (!isset($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required field: id."];
        }

        return $this->itemModel->setItemStatus($data['id'], 'inactive');
    }

    public function activateItem($data)
    {
        if (!isset($data['id'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Missing required field: id."];
        }

        return $this->itemModel->setItemStatus($data['id'], 'active');
    }
}

