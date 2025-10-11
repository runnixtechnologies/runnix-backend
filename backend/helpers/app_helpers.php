<?php

use App\Class\ApiResponse;


if(!function_exists("json_success_response")){
    function json_success_response($message, $data = [], $meta = [], $httpCode = 200){
        header('Content-Type: application/json');
        ApiResponse::success($message, $data, $meta, $httpCode);
    }
}

if(!function_exists("json_error_response")){
    function json_error_response($message, $data = [], $errors = [], $httpCode = 200){
        header('Content-Type: application/json');
        ApiResponse::error($message, $data, $errors, $httpCode);
    }
}

if(!function_exists("abort")){
    function abort($code, $message, $headers = [], $errors = []){
        json_error_response($message, $headers, $errors, $code);
    }
}

if(!function_exists("arrayToObject")){
    function arrayToObject($array) {
        return json_decode(json_encode($array));
    }
}

if(!function_exists("upload_path")){
    function upload_path($path = null) {
        if($path)
            $path = trim($path, "/");
        return __DIR__."/../../uploads/$path";
    }
}
