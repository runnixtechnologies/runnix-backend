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
   

    $saved = $this->store->saveVerificationAddress($storeId, $latitude, $longitude);

    if ($saved) {
        http_response_code(200);
        return ["status" => "success", "message" => "Address verification submitted"];
    } else {
        http_response_code(500);
        return ["status" => "error", "message" => "Failed to save verification data"];
    }
}

}
