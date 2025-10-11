<?php 

namespace Controller;

use App\Requests\HttpRequestHandler;
use App\Requests\RegisterVehicleRequest;
use Model\RiderVehicle;

use function Middleware\authenticateRequest;

class RiderVehicleController extends BaseController{


    public $httpRequestHandler;
    public function __construct()
    {
        $this->httpRequestHandler = new HttpRequestHandler();
    }

    public function registerVehicle(){
        $req = new RegisterVehicleRequest();
        $data = $req->validate($this->httpRequestHandler->all());
        $data["rider_id"] = 1;//$this->authUser["user_id"];
        $model = new RiderVehicle();
        $model->create($data);
        return json_success_response("Vehicle registered successfully", $data);
    }
}