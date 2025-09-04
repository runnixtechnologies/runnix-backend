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

    public function create($data, $user)
    {
        // Extract store_id from authenticated user
        if (!isset($user['store_id'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        $data['store_id'] = $user['store_id'];
        
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

        // Validate discount start date (optional)
        if (isset($data['discount_start_date'])) {
            if (!empty($data['discount_start_date'])) {
                $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_start_date']);
                if (!$startDate) {
                    $startDate = \DateTime::createFromFormat('Y-m-d', $data['discount_start_date']);
                }
                if (!$startDate) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Invalid discount start date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'];
                }
            }
        }

        // Validate discount end date (optional)
        if (isset($data['discount_end_date'])) {
            if (!empty($data['discount_end_date'])) {
                $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_end_date']);
                if (!$endDate) {
                    $endDate = \DateTime::createFromFormat('Y-m-d', $data['discount_end_date']);
                }
                if (!$endDate) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Invalid discount end date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'];
                }
            }
        }

        // Validate that end date is after start date if both are provided
        if (isset($data['discount_start_date']) && isset($data['discount_end_date']) && 
            !empty($data['discount_start_date']) && !empty($data['discount_end_date'])) {
            $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_start_date']) ?: 
                         \DateTime::createFromFormat('Y-m-d', $data['discount_start_date']);
            $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_end_date']) ?: 
                       \DateTime::createFromFormat('Y-m-d', $data['discount_end_date']);
            
            if ($startDate && $endDate && $endDate <= $startDate) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Discount end date must be after start date'];
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

    public function deactivatePack($data, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    if (empty($data['id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack ID is required'];
    }

    $result = $this->packModel->deactivate($data['id'], $user['store_id']);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Pack deactivated successfully'];
    } else {
        http_response_code(404); // Not found or not owned by this store
        return ['status' => 'error', 'message' => 'Failed to deactivate pack or unauthorized'];
    }
}

public function activatePack($data, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    if (empty($data['id'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack ID is required'];
    }

    $result = $this->packModel->activate($data['id'], $user['store_id']);
    if ($result) {
        http_response_code(200);
        return ['status' => 'success', 'message' => 'Pack activated successfully'];
    } else {
        http_response_code(404);
        return ['status' => 'error', 'message' => 'Failed to activate pack or unauthorized'];
    }
}


public function activatePacksBulk($data, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    if (empty($data['ids'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs are required'];
    }

    $result = $this->packModel->activateBulk($data['ids'], $user['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs activated']
        : ['status' => 'error', 'message' => 'Failed to activate packs'];
}

public function deactivatePacksBulk($data, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    if (empty($data['ids'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs are required'];
    }

    $result = $this->packModel->deactivateBulk($data['ids'], $user['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs deactivated']
        : ['status' => 'error', 'message' => 'Failed to deactivate packs'];
}

public function deletePacksBulk($data, $user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
    }
    
    if (empty($data['ids'])) {
        http_response_code(400);
        return ['status' => 'error', 'message' => 'Pack IDs are required'];
    }

    $result = $this->packModel->deleteBulk($data['ids'], $user['store_id']);
    return $result
        ? ['status' => 'success', 'message' => 'Packs deleted']
        : ['status' => 'error', 'message' => 'Failed to delete packs'];
}



    public function update($data, $user)
    {
        // Extract store_id from authenticated user
        if (!isset($user['store_id'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        $data['store_id'] = $user['store_id'];
        
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

        // Validate discount start date (optional)
        if (isset($data['discount_start_date'])) {
            if (!empty($data['discount_start_date'])) {
                $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_start_date']);
                if (!$startDate) {
                    $startDate = \DateTime::createFromFormat('Y-m-d', $data['discount_start_date']);
                }
                if (!$startDate) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Invalid discount start date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'];
                }
            }
        }

        // Validate discount end date (optional)
        if (isset($data['discount_end_date'])) {
            if (!empty($data['discount_end_date'])) {
                $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_end_date']);
                if (!$endDate) {
                    $endDate = \DateTime::createFromFormat('Y-m-d', $data['discount_end_date']);
                }
                if (!$endDate) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => 'Invalid discount end date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'];
                }
            }
        }

        // Validate that end date is after start date if both are provided
        if (isset($data['discount_start_date']) && isset($data['discount_end_date']) && 
            !empty($data['discount_start_date']) && !empty($data['discount_end_date'])) {
            $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_start_date']) ?: 
                         \DateTime::createFromFormat('Y-m-d', $data['discount_start_date']);
            $endDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['discount_end_date']) ?: 
                       \DateTime::createFromFormat('Y-m-d', $data['discount_end_date']);
            
            if ($startDate && $endDate && $endDate <= $startDate) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Discount end date must be after start date'];
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

    public function delete($data, $user)
    {
        // Extract store_id from authenticated user
        if (!isset($user['store_id'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        if (empty($data['id'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Pack ID is required'];
        }
        
        $result = $this->packModel->delete($data['id']);
        if ($result) {
            http_response_code(200); // OK
            return ['status' => 'success', 'message' => 'Pack deleted successfully'];
        } else {
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Failed to delete pack'];
        }
    }

    public function getAll($user, $page = 1, $limit = 10)
{
    // Extract store_id from authenticated user
    $storeId = $user['store_id'];
    
    if (!$storeId) {
        http_response_code(403);
        return ['status' => 'error', 'message' => 'Store ID not found for user'];
    }

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
        error_log("PackController::getPackById - Starting with ID: $id, User: " . json_encode($user));
        
        // Get store_id from database if not in JWT token
        $storeId = $user['store_id'] ?? null;
        error_log("PackController::getPackById - Initial store_id from JWT: " . ($storeId ?? 'null'));
        
        if (!$storeId) {
            error_log("PackController::getPackById - No store_id in JWT, fetching from database for user_id: " . $user['user_id']);
            // Fetch store from database using user_id
            $store = $this->storeModel->getStoreByUserId($user['user_id']);
            if (!$store) {
                error_log("PackController::getPackById - Store not found for user_id: " . $user['user_id']);
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Store not found for user'];
            }
            $storeId = $store['id'];
            error_log("PackController::getPackById - Store found with ID: $storeId");
        }
        
        error_log("PackController::getPackById - Final store_id: $storeId");
        
        $pack = $this->packModel->getPackById($id);
        error_log("PackController::getPackById - Pack data: " . json_encode($pack));
        
        if ($pack) {
            error_log("PackController::getPackById - Pack found, checking ownership. Pack store_id: " . $pack['store_id'] . ", User store_id: $storeId");
            // Check if the pack belongs to the user's store
            if ($pack['store_id'] != $storeId) {
                error_log("PackController::getPackById - Ownership check failed. Pack belongs to store " . $pack['store_id'] . " but user belongs to store $storeId");
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to access this pack'];
            }
            
            error_log("PackController::getPackById - Ownership check passed, returning pack data");
            http_response_code(200); // OK
            return ['status' => 'success', 'data' => $pack];
        } else {
            error_log("PackController::getPackById - Pack not found with ID: $id");
            http_response_code(404); // Not Found
            return ['status' => 'error', 'message' => 'Pack not found'];
        }
    }
}
