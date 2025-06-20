<?php
namespace Controller;

use Model\Pack;
use Config\JwtHandler;

class PackController
{
    private $packModel;

    public function __construct()
    {
        $this->pack = new Pack();
    }

    public function create($data)
    {
        $result = $this->packModel->create($data);
        if ($result) {
            http_response_code(201); // Created
            return ['status' => 'success', 'message' => 'Pack created successfully'];
        } else {
            http_response_code(500); // Internal Server Error
            return ['status' => 'error', 'message' => 'Failed to create pack'];
        }
    }

    public function update($data)
    {
        $result = $this->packModel->update($data);
        if ($result) {
            http_response_code(200); // OK
            return ['status' => 'success', 'message' => 'Pack updated successfully'];
        } else {
            http_response_code(404); // Not Found or error
            return ['status' => 'error', 'message' => 'Failed to update pack'];
        }
    }

    public function delete($data)
    {
        $result = $this->packModel->delete($data['id']);
        if ($result) {
            http_response_code(200); // OK
            return ['status' => 'success', 'message' => 'Pack deleted successfully'];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Failed to delete pack'];
        }
    }

    public function getAll($storeId)
    {
        $packs = $this->packModel->getAll($storeId);
        if ($packs) {
            http_response_code(200); // OK
            return ['status' => 'success', 'data' => $packs];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'No packs found'];
        }
    }

    public function getPackById($id)
    {
        $pack = $this->packModel->getPackById($id);
        if ($pack) {
            http_response_code(200); // OK
            return ['status' => 'success', 'data' => $pack];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Pack not found'];
        }
    }
}
