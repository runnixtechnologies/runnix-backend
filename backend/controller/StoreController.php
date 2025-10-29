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
        
        // Check if at least one field is provided for update
        $updatableFields = ['store_name', 'biz_address'];
        $hasFieldsToUpdate = false;
        foreach ($updatableFields as $field) {
            if (isset($data[$field]) && !empty(trim($data[$field]))) {
                $hasFieldsToUpdate = true;
                break;
            }
        }
        
        // Check for sensitive fields that are not allowed for security reasons
        if (!empty($data['biz_email'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Business email updates are not allowed. Please contact support for email changes.'];
        }
        
        if (!empty($data['biz_phone'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Business phone updates are not allowed. Please contact support for phone number changes.'];
        }
        
        if (!empty($data['biz_reg_number'])) {
            http_response_code(403);
            return ['status' => 'error', 'message' => 'Business registration number updates are not allowed. Please contact support for registration number changes.'];
        }
        
        // Check if biz_logo or biz_photo is being uploaded
        if ((isset($_FILES['biz_logo']) && $_FILES['biz_logo']['error'] === UPLOAD_ERR_OK) ||
            (isset($_FILES['biz_photo']) && $_FILES['biz_photo']['error'] === UPLOAD_ERR_OK)) {
            $hasFieldsToUpdate = true;
        }
        
        if (!$hasFieldsToUpdate) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'At least one field must be provided for update'];
        }
        
        // Email, phone, and registration number validation removed for security reasons
        
        // Check for field uniqueness (only for provided fields)
        if (isset($data['store_name']) && !empty(trim($data['store_name']))) {
            if ($this->store->storeFieldExists('store_name', $data['store_name'], $storeId)) {
                http_response_code(409);
                return ['status' => 'error', 'message' => 'Store name already exists'];
            }
        }
        
        // Email, phone, and registration number uniqueness checks removed for security reasons
        
        // Prepare update data (only include provided fields)
        $updateData = [];
        
        if (isset($data['store_name']) && !empty(trim($data['store_name']))) {
            $updateData['store_name'] = trim($data['store_name']);
        }
        
        if (isset($data['biz_address']) && !empty(trim($data['biz_address']))) {
            $updateData['biz_address'] = trim($data['biz_address']);
        }
        
        // Email, phone, and registration number updates removed for security reasons
        
        // Handle biz_logo or biz_photo upload (optional)
        $logoFile = null;
        if (isset($_FILES['biz_logo']) && $_FILES['biz_logo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['biz_logo'];
        } elseif (isset($_FILES['biz_photo']) && $_FILES['biz_photo']['error'] === UPLOAD_ERR_OK) {
            $logoFile = $_FILES['biz_photo'];
        }
        
        if ($logoFile) {
            $allowedTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/x-png'
            ];
            $fileType = $logoFile['type'];
            $fileSize = $logoFile['size'];
            $fileName = $logoFile['name'];

            // Enhanced debugging for image format issues
            error_log("=== BIZ_LOGO UPLOAD DEBUG ===");
            error_log("File name: " . $fileName);
            error_log("File type (MIME): " . $fileType);
            error_log("File size: " . $fileSize . " bytes");
            error_log("Allowed types: " . implode(', ', $allowedTypes));
            error_log("Is file type allowed: " . (in_array($fileType, $allowedTypes) ? 'YES' : 'NO'));

            if (!in_array($fileType, $allowedTypes)) {
                error_log("=== UNSUPPORTED IMAGE FORMAT ERROR (BIZ_LOGO) ===");
                error_log("Received MIME type: " . $fileType);
                error_log("Expected MIME types: " . implode(', ', $allowedTypes));
                error_log("File name: " . $fileName);
                
                // Try to detect MIME type from file extension as fallback
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                error_log("File extension: " . $fileExtension);
                
                $extensionToMime = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png'
                ];
                
                if (isset($extensionToMime[$fileExtension])) {
                    $detectedMime = $extensionToMime[$fileExtension];
                    error_log("Detected MIME type from extension: " . $detectedMime);
                    
                    if (in_array($detectedMime, $allowedTypes)) {
                        error_log("Using detected MIME type instead of reported type");
                        $fileType = $detectedMime;
                    } else {
                        http_response_code(415);
                        return ["status" => "error", "message" => "Unsupported image format. Received: " . $fileType . ", File: " . $fileName];
                    }
                } else {
                    http_response_code(415);
                    return ["status" => "error", "message" => "Unsupported image format. Received: " . $fileType . ", File: " . $fileName];
                }
            }

            if ($fileSize > 3 * 1024 * 1024) { // 3MB
                http_response_code(413);
                return ["status" => "error", "message" => "Image exceeds max size of 3MB."];
            }

            // Use absolute path from document root
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/logos/';
            
            // Alternative path if document root doesn't work
            if (!is_dir($uploadDir)) {
                $uploadDir = dirname(__DIR__) . '/uploads/logos/';
                error_log("Using alternative upload directory: " . $uploadDir);
            }
            
            // Enhanced directory creation with error handling
            if (!is_dir($uploadDir)) {
                error_log("Creating upload directory: " . $uploadDir);
                $createResult = mkdir($uploadDir, 0777, true);
                if (!$createResult) {
                    error_log("Failed to create upload directory: " . $uploadDir);
                    error_log("Last error: " . json_encode(error_get_last()));
                    http_response_code(500);
                    return ["status" => "error", "message" => "Failed to create upload directory."];
                }
            }
            
            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                error_log("Upload directory is not writable: " . $uploadDir);
                http_response_code(500);
                return ["status" => "error", "message" => "Upload directory is not writable."];
            }

            $ext = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
            $filename = uniqid('biz_logo_', true) . '.' . $ext;
            $uploadPath = $uploadDir . $filename;
            
            error_log("Attempting to upload biz_logo:");
            error_log("- Source: " . $logoFile['tmp_name']);
            error_log("- Upload directory: " . $uploadDir);
            error_log("- Destination: " . $uploadPath);
            error_log("- File size: " . $logoFile['size'] . " bytes");
            error_log("- Is manually parsed: " . (strpos($logoFile['tmp_name'], 'put_upload_') !== false ? 'YES' : 'NO'));

            // Check if this is a manually parsed file (from PUT multipart parsing)
            $isManuallyParsed = (strpos($logoFile['tmp_name'], 'put_upload_') !== false);
            
            if ($isManuallyParsed) {
                error_log("Detected manually parsed file, using copy instead of move_uploaded_file");
                if (!copy($logoFile['tmp_name'], $uploadPath)) {
                    error_log("copy failed");
                    error_log("Last error: " . json_encode(error_get_last()));
                    http_response_code(500);
                    return ["status" => "error", "message" => "Failed to upload image. Check server logs for details."];
                }
                // Clean up the temporary file
                unlink($logoFile['tmp_name']);
            } else {
                if (!move_uploaded_file($logoFile['tmp_name'], $uploadPath)) {
                    error_log("move_uploaded_file failed");
                    error_log("Last error: " . json_encode(error_get_last()));
                    http_response_code(500);
                    return ["status" => "error", "message" => "Failed to upload image. Check server logs for details."];
                }
            }
            
            error_log("File uploaded successfully: " . $uploadPath);

            // Delete old image if it exists
            if (!empty($store['biz_logo'])) {
                $oldPath = $uploadDir . basename($store['biz_logo']);
                if (file_exists($oldPath)) {
                    if (unlink($oldPath)) {
                        error_log("Successfully deleted old biz_logo: " . $oldPath);
                    } else {
                        error_log("Failed to delete old biz_logo: " . $oldPath);
                    }
                } else {
                    error_log("Old biz_logo file not found: " . $oldPath);
                }
            }

            $bizLogoUrl = 'https://api.runnix.africa/uploads/logos/' . $filename;
            $updateData['biz_logo'] = $bizLogoUrl;
            error_log("Biz logo URL generated: " . $bizLogoUrl);
        }
        
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

    /**
     * Get stores for customer with filtering and sorting
     */
    public function getStoresForCustomer($user, $storeTypeId = null, $search = null, $sort = 'popular', $page = 1, $limit = 20)
    {
        try {
            $userId = $user['user_id'];
            
            // Get user location for distance calculation
            $userModel = new \Model\User();
            $userLocation = $userModel->getUserLocation($userId);
            
            // Get stores with filtering
            $stores = $this->store->getStoresForCustomer($storeTypeId, $search, $userLocation, $sort, $page, $limit);
            
            // Get total count for pagination
            $totalCount = $this->store->getStoresForCustomerCount($storeTypeId, $search);
            $totalPages = ceil($totalCount / $limit);
            
            // Format stores for response
            $formattedStores = [];
            foreach ($stores as $store) {
                $formattedStores[] = [
                    'id' => $store['id'],
                    'store_name' => $store['store_name'],
                    'store_type' => $store['store_type_name'],
                    'rating' => $store['rating'] ?? 0,
                    'review_count' => $store['review_count'] ?? 0,
                    'delivery_time' => $store['delivery_time'] ?? '20-30 mins',
                    'distance' => $store['distance'] ?? null,
                    'is_favorite' => $store['is_favorite'] ?? false,
                    'is_online' => $store['is_online'] ?? false,
                    'logo' => $store['biz_logo'] ?? null,
                    'address' => $store['biz_address'],
                    'phone' => $store['biz_phone']
                ];
            }
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $formattedStores,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get stores for customer error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to retrieve stores'];
        }
    }
    
    /**
     * Get user location for distance calculation
     */
    private function getUserLocation($userId)
    {
        try {
            $sql = "SELECT latitude, longitude FROM user_locations WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->store->getConnection()->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user location error: " . $e->getMessage());
            return null;
        }
    }

}
