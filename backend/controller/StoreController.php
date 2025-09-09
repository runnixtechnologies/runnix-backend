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
        
        // If store_id is not directly available, try to get it from the user's store
        if (!$storeId) {
            $store = $this->store->getStoreByUserId($user['user_id']);
            if ($store) {
                $storeId = $store['id'];
            }
        }
        
        if (!$storeId) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found for user'];
        }
        
        $operatingHoursData = $this->store->getOperatingHours($storeId);
        
        if ($operatingHoursData === false) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to retrieve operating hours'];
        }
        
        // If no operating hours set, return default structure
        if (empty($operatingHoursData['operating_hours'])) {
            $operatingHoursData = [
                'business_24_7' => false,
                'operating_hours' => [
                    'monday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'tuesday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'wednesday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'thursday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'friday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'saturday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null],
                    'sunday' => ['enabled' => false, 'is_24hrs' => false, 'is_closed' => true, 'open_time' => null, 'close_time' => null]
                ]
            ];
        }
        
        http_response_code(200);
        return [
            'status' => 'success',
            'data' => $operatingHoursData
        ];
    }
    
    public function updateOperatingHours($data, $user)
    {
        // Extract store_id from authenticated user
        $storeId = $user['store_id'] ?? null;
        
        // If store_id is not directly available, try to get it from the user's store
        if (!$storeId) {
            $store = $this->store->getStoreByUserId($user['user_id']);
            if ($store) {
                $storeId = $store['id'];
            }
        }
        
        if (!$storeId) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found for user'];
        }
        
        // Get business 24/7 setting
        $business247 = $data['business_24_7'] ?? false;
        
        // Validate operating hours data
        if (!isset($data['operating_hours']) || !is_array($data['operating_hours'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Operating hours data is required and must be an array'];
        }
        
        $operatingHours = $data['operating_hours'];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Validate each day that is provided
        foreach ($operatingHours as $day => $dayData) {
            if (!in_array($day, $validDays)) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "Invalid day: $day"];
            }
            
            // Validate enabled field
            if (!isset($dayData['enabled'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "enabled field is required for $day"];
            }
            
            // Convert string booleans to actual booleans
            if (is_string($dayData['enabled'])) {
                $dayData['enabled'] = strtolower(trim($dayData['enabled'])) === 'true' || $dayData['enabled'] === '1';
            }
            
            // If day is enabled, validate additional fields
            if ($dayData['enabled']) {
                // Check if 24hrs mode is enabled
                $is24hrs = $dayData['is_24hrs'] ?? false;
                if (is_string($is24hrs)) {
                    $is24hrs = strtolower(trim($is24hrs)) === 'true' || $is24hrs === '1';
                }
                
                if (!$is24hrs) {
                    // Validate time fields for non-24hr days
                if (!isset($dayData['open_time']) || !isset($dayData['close_time'])) {
                    http_response_code(400);
                        return ['status' => 'error', 'message' => "Open and close times are required for $day when not in 24hr mode"];
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
        }
        
        // Ensure all days are present in the data (fill missing days as disabled)
        foreach ($validDays as $day) {
            if (!isset($operatingHours[$day])) {
                $operatingHours[$day] = [
                    'enabled' => false,
                    'is_24hrs' => false,
                    'is_closed' => true,
                    'open_time' => null,
                    'close_time' => null
                ];
            }
        }
        
        $result = $this->store->updateOperatingHours($storeId, $operatingHours, $business247);
        
        if ($result) {
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Operating hours updated successfully'];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update operating hours'];
        }
    }
    
    public function updateStoreProfile($data, $user)
    {
        // Extract store_id from authenticated user
        if (!isset($user['store_id'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.'];
        }
        
        $storeId = $user['store_id'];
        
        // Verify store belongs to the user
        $store = $this->store->getStoreById($storeId);
        if (!$store || $store['user_id'] != $user['user_id']) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Unauthorized access to store.'];
        }
        
        // Validate required fields
        $requiredFields = ['store_name', 'biz_address', 'biz_phone', 'biz_reg_number'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                return ['status' => 'error', 'message' => "$field is required"];
            }
        }
        
        // Validate phone number format
        $phone = $data['biz_phone'];
        if (!preg_match('/^0?\d{10}$/', $phone)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid phone number format. Use 10 or 11 digits.'];
        }
        
        // Format phone number to international format
        $formattedPhone = '234' . ltrim($phone, '0');
        
        // Check if phone number is being changed
        $phoneChanged = $store['biz_phone'] !== $formattedPhone;
        
        if ($phoneChanged) {
            // Check if new phone number already exists
            if ($this->store->storeFieldExists('biz_phone', $formattedPhone, $storeId)) {
                http_response_code(409);
                return ['status' => 'error', 'message' => 'Business phone number already exists'];
            }
            
            // Check if OTP was verified for phone change
            $otpModel = new \Model\Otp();
            if (!$otpModel->isOtpVerified($formattedPhone, 'business_phone_update')) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Phone number OTP not verified. Please verify OTP before updating.'];
            }
        }
        
        // Check for other field uniqueness
        if ($this->store->storeFieldExists('store_name', $data['store_name'], $storeId)) {
            http_response_code(409);
            return ['status' => 'error', 'message' => 'Store name already exists'];
        }
        
        if ($this->store->storeFieldExists('biz_reg_number', $data['biz_reg_number'], $storeId)) {
            http_response_code(409);
            return ['status' => 'error', 'message' => 'Business registration number already exists'];
        }
        
        // Prepare update data
        $updateData = [
            'store_name' => trim($data['store_name']),
            'biz_address' => trim($data['biz_address']),
            'biz_phone' => $formattedPhone,
            'biz_reg_number' => trim($data['biz_reg_number'])
        ];
        
        // Update store profile
        $result = $this->store->updateStoreProfile($storeId, $updateData);
        
        if ($result) {
            http_response_code(200);
            return [
                'status' => 'success', 
                'message' => 'Business profile updated successfully',
                'data' => $updateData
            ];
        } else {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update business profile'];
        }
    }

}
