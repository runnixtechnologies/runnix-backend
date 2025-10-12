<?php 

namespace App\Requests;

class HttpRequestHandler{

    public $requestBody = [];
    private $requestMethodHandler;
    private $allRequestBody = [];
    public $requestData = [];
    public function __construct()
    {       
        $this->handle();
    }

    public function allowPostMethod(){
        return $this->allowMethod("POST");
    }

    public function allowGetMethod(){
        return $this->allowMethod("GET");
    }
    public function allowMethod($method){
        $srvMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $method = strtolower($method);
        if ($srvMethod !== $method) {
            abort(405, strtoupper($srvMethod) ." method is not allowed");
        }
        $this->requestMethodHandler = $method == "post" ? $_POST : $_GET;
        // $this->requests();
    }

    public function get($key, $default){
        return $this->requestBody[$key] ?? $default;
    }

    public function all(){
        
        return $this->allRequestBody;
    }

    private function handle(){
        $headers = getallheaders();
        $this->allRequestBody = (($headers["Content-Type"] ?? '') == "application/json") ? json_decode(file_get_contents("php://input"), true) : array_merge($_GET, $_POST, $_FILES);
    }

    public function requests(){
        
    }
}