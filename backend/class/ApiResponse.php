<?php 

namespace App\Class;

class ApiResponse{

    public static function error($message, $data = [], $errors = [], $httpCode = 200){
        http_response_code($httpCode);
        echo json_encode(["status" => "error", "message" => $message, "data" => $data, "errors" => $errors]);
        exit;
    }

    public static function success($message, $data, $meta, $httpCode = 200){
        http_response_code($httpCode);
        echo json_encode(["status" => "success", "message" => $message, "data" => $data, "meta" => $meta]);
        exit;
    }
}

