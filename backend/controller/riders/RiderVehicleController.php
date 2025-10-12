<?php 

namespace Controller\Riders;

use App\Requests\HttpRequestHandler;
use App\Requests\Riders\RegisterVehicleRequest;
use Model\RiderVehicle;
use App\Requests\Riders\UploadVehicleAttachmentRequest;
use Controller\BaseController;
use App\Facades\FileProcessor;

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
        $data["rider_id"] = $this->authUser["rider_id"];
        $model = new RiderVehicle();
        $model->create($data);
        return json_success_response("Vehicle registered successfully", $data);
    }

    public function uploadAttachements(){
        $vid = $_GET["vehicle_id"] ?? abort(400, "Vehicle id is required");
        $req= new UploadVehicleAttachmentRequest();
        $resp = $req->validate($this->httpRequestHandler->all());
        $fileProcessor = new FileProcessor();
        $docs = $resp["document_type"];
        $files = $fileProcessor->uploadedFiles("file");
        foreach($docs as $key => $dcVal){
            $fname = md5(time().mt_rand(1,9999));
            $url = $fileProcessor->storeFile("attachments", $files[$key], $fname);
            $riderDcModel = new \Model\RiderVehicleAttachment();
            $riderDcModel->create([
                "rider_id" => $this->authUser["rider_id"],
                "vehicle_id" => $_GET["vehicle_id"],
                "attachment_type" => $dcVal,
                "file_name" => $fname,
                "file_path" => $url,
                "file_size" => $files[$key]["size"],
                "file_type" => $files[$key]["type"]
            ]);
        }
        \json_success_response("Files uploaded", $docs);
    }
}