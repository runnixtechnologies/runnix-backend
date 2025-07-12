<?php
namespace Controller;

use Model\Pack;
use Config\JwtHandler;

class PackController
{
    private $packModel;

    public function __construct()
    {
        $this->packModel = new Pack();
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
        'pagination' => [
            'current_page' => $page,
            'limit'        => $limit,
            'total'        => (int) $total,
            'total_pages'  => ceil($total / $limit),
        ]
    ];
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
