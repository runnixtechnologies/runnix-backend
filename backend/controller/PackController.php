<?php
namespace Controller;

use Model\Pack;
use Model\Store;
use Config\JwtHandler;

class PackController
{
    private $packModel;
    private $storeModel;

    public function __construct()
    {
        $this->packModel = new Pack();
        $this->storeModel = new Store();
    }

    public function create($data)
    {
        // Validate discount (optional)
        if (isset($data['discount'])) {
            if (!is_numeric($data['discount']) || $data['discount'] < 0) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Discount must be a non-negative number'];
            }
        }

        // Validate percentage (optional)
        if (isset($data['percentage'])) {
            if (!is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Percentage must be between 0 and 100'];
            }
        }

        $result = $this->packModel->create($data);
        if ($result) {
            http_response_code(201); // Created
            return ['status' => 'success', 'message' => 'Pack created successfully'];
        } else {
            http_response_code(500); // Internal Server Error
            return ['status' => 'error', 'message' => 'Failed to create pack'];
        }
    }

    public function deactivatePack($data)
{
    if (empty($data['id']) || empty($data['store_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack ID and store ID are required'];
    }

    $result = $this->packModel->deactivate($data['id'], $data['store_id']);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Pack deactivated successfully'];
    } else {
        http_response_code(404); // Not found or not owned by this store
        return ['status' => 'error', 'message' => 'Failed to deactivate pack or unauthorized'];
    }
}

public function activatePack($data)
{
    if (empty($data['id']) || empty($data['store_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack ID and store ID are required'];
    }

    $result = $this->packModel->activate($data['id'], $data['store_id']);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Pack activated successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Failed to activate pack or unauthorized'];
    }
}


public function activatePacksBulk($data)
{
    if (empty($data['ids']) || empty($data['store_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs and store ID are required'];
    }

    $result = $this->packModel->activateBulk($data['ids'], $data['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs activated']
        : ['status' => 'error', 'message' => 'Failed to activate packs'];
}

public function deactivatePacksBulk($data)
{
    if (empty($data['ids']) || empty($data['store_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs and store ID are required'];
    }

    $result = $this->packModel->deactivateBulk($data['ids'], $data['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs deactivated']
        : ['status' => 'error', 'message' => 'Failed to deactivate packs'];
}

public function deletePacksBulk($data)
{
    if (empty($data['ids']) || empty($data['store_id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs and store ID are required'];
    }

    $result = $this->packModel->deleteBulk($data['ids'], $data['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs deleted']
        : ['status' => 'error', 'message' => 'Failed to delete packs'];
}



    public function update($data)
    {
        // Validate discount (optional)
        if (isset($data['discount'])) {
            if (!is_numeric($data['discount']) || $data['discount'] < 0) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Discount must be a non-negative number'];
            }
        }

        // Validate percentage (optional)
        if (isset($data['percentage'])) {
            if (!is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Percentage must be between 0 and 100'];
            }
        }

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

    public function getAll($storeId, $page = 1, $limit = 10)
{
    $offset = ($page - 1) * $limit;

    $packs = $this->packModel->getAll($storeId, $limit, $offset);
    $total = $this->packModel->countByStore($storeId);

    http_response_code(200);
    return [
        'status' => 'success',
        'data' => $packs ?: [],  // Return empty array if no packs
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => ($page * $limit) < $total,
            'has_prev' => $page > 1
        ]
    ];
}


    public function getPackById($id, $user)
    {
        // Get store_id from database if not in JWT token
        $storeId = $user['store_id'] ?? null;
        if (!$storeId) {
            // Fetch store from database using user_id
            $store = $this->storeModel->getStoreByUserId($user['user_id']);
            if (!$store) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Store not found for user'];
            }
            $storeId = $store['id'];
        }
        
        $pack = $this->packModel->getPackById($id);
        
        if ($pack) {
            // Check if the pack belongs to the user's store
            if ($pack['store_id'] != $storeId) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to access this pack'];
            }
            
            http_response_code(200); // OK
            return ['status' => 'success', 'data' => $pack];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Pack not found'];
        }
    }
}
