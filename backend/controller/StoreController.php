<?php 
namespace Controller;

use Model\Store;
use Config\JwtHandler;

class StoreController
{
    private $store;

    public function __construct()
    {
        $this->store = new Store();
    }

    public function getStoreByUserId($userId)
    {
        $store = $this->store->getStoreByUserId($userId);
        if ($store) {
            return ["status" => "success", "store" => $store];
        }

        http_response_code(404);
        return ["status" => "error", "message" => "Store not found"];
    }

    
  public function verifyStoreAddress($data)
{
    if (empty($data['store_id']) || empty($data['latitude']) || empty($data['longitude'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "Missing required fields"];
    }

    $storeId = $data['store_id'];
    $latitude = $data['latitude'];
    $longitude = $data['longitude'];

    // Check if store exists
    $store = $this->store->getStoreById($storeId);
    if (!$store) {
        http_response_code(404);
        return ["status" => "error", "message" => "Store does not exist"];
    }

    // Save verification address
    $saved = $this->store->saveVerificationAddress($storeId, $latitude, $longitude);

    if ($saved) {
        http_response_code(200);
        return ["status" => "success", "message" => "Address verification submitted"];
    } else {
        http_response_code(409);
        return ["status" => "error", "message" => "A pending verification already exists"];
    }
}


// StoreTypeController.php
public function getActiveStoreTypes()
{
   
    $types = $this->store->getStoreTypes(); // status = 1

    if (empty($types)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active store types found"];
    }

    return ["status" => "success", "data" => $types];
}

public function getActiveCategories()
{
   
    $types = $this->store->getActiveCategories(); // status = 1

    if (empty($types)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active categories found"];
    }

    return ["status" => "success", "data" => $types];
}


public function setStoreStatus($data, $user) {
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Store ID not found. Please ensure you are logged in as a merchant with a store setup."];
    }
    
    if (!isset($data['is_online'])) {
        http_response_code(400);
        return ["status" => "error", "message" => "is_online is required."];
    }

    // Verify store belongs to the user
    $store = $this->store->getStoreById($user['store_id']);

    if (!$store || $store['user_id'] != $user['user_id']) {
        http_response_code(403);
        return ["status" => "error", "message" => "Unauthorized access to store."];
    }

    return $this->store->updateStoreStatus($user['store_id'], $data['is_online']);
}

public function getStatus($user) {
    if (!isset($user['user_id'])) {
        http_response_code(401);
        return ["status" => "error", "message" => "Unauthorized"];
    }

    $storeData = $this->getStoreByUserId($user['user_id']);
    
    if ($storeData['status'] !== 'success') {
        // Return the "Store not found" error from getStoreByUserId
        return $storeData;
    }

    $store = $storeData['store'];
    $storeId = $store['id'];

    return $this->store->getStoreStatus($storeId);
}

public function getActiveCategoriesByStoreType($user)
{
    // Extract store_id from authenticated user
    if (!isset($user['store_id'])) {
        http_response_code(403);
        return ["status" => "error", "message" => "Store ID not found. Please ensure you are logged in as a merchant with a store setup."];
    }
    
    $storeId = $user['store_id'];
    
    // Try to get store_type_id from JWT token first
    $storeTypeId = $user['store_type_id'] ?? null;
    
    // If not in JWT token, fetch it from database using store_id
    if (!$storeTypeId) {
        $store = $this->store->getStoreById($storeId);
        if (!$store) {
            http_response_code(404);
            return ["status" => "error", "message" => "Store not found"];
        }
        $storeTypeId = $store['store_type_id'];
    }
    
    $categories = $this->store->fetchActiveCategoriesByStoreType($storeTypeId);

    if (empty($categories)) {
        http_response_code(404);
        return ["status" => "error", "message" => "No active categories found for this store type"];
    }

    return ["status" => "success", "data" => $categories];
}

    // Operating Hours Methods
    public function getOperatingHours($user)
    {
        // Extract store_id from authenticated user
        $storeId = $user['store_id'] ?? null;
        
        if (!$storeId) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found for user'];
        }
        
        $operatingHours = $this->store->getOperatingHours($storeId);
        
        if ($operatingHours === false) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to retrieve operating hours'];
        }
        
        // If no operating hours set, return default structure
        if (empty($operatingHours)) {
            $operatingHours = [
                'monday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'tuesday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'wednesday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'thursday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'friday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'saturday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
                'sunday' => ['is_closed' => true, 'open_time' => null, 'close_time' => null]
            ];
        }
        
        http_response_code(200);
        return [
            'status' => 'success',
            'data' => $operatingHours
        ];
    }
    
    public function updateOperatingHours($data, $user)
    {
        // Extract store_id from authenticated user
        $storeId = $user['store_id'] ?? null;
        
        if (!$storeId) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found for user'];
        }
        
        // Validate operating hours data
        if (!isset($data['operating_hours']) || !is_array($data['operating_hours'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Operating hours data is required and must be an array'];
        }
        
        $operatingHours = $data['operating_hours'];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Validate each day
        foreach ($validDays as $day) {
            if (!isset($operatingHours[$day])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "Operating hours for $day is required"];
            }
            
            $dayData = $operatingHours[$day];
            
            if (!isset($dayData['is_closed']) || !is_bool($dayData['is_closed'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "is_closed field is required for $day and must be boolean"];
            }
            
            if (!$dayData['is_closed']) {
                // If not closed, validate time fields
                if (!isset($dayData['open_time']) || !isset($dayData['close_time'])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "Open and close times are required for $day when not closed"];
                }
                
                // Validate time format (HH:MM)
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dayData['open_time']) ||
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dayData['close_time'])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "Invalid time format for $day. Use HH:MM format"];
                }
                
                // Validate that close time is after open time
                if (strtotime($dayData['close_time']) <= strtotime($dayData['open_time'])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "Close time must be after open time for $day"];
                }
            }
        }
        
        $result = $this->store->updateOperatingHours($storeId, $operatingHours);
        
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Operating hours updated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update operating hours'];
        }
    }

}
