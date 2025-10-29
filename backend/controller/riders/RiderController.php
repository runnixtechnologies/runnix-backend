<?php 

namespace Controller\Riders;

use App\Requests\HttpRequestHandler;
use Controller\BaseController;
use App\Facades\FileProcessor;
use Model\RiderDocument;
use Model\RiderVehicle;
use Model\RiderVehicleAttachment;

use function Middleware\authenticateRequest;

class RiderController extends BaseController{


    public $httpRequestHandler;
    public $model;
    public $docModel;

    private $requiredDocs = [
        [
            "label" => "Drivers Licence",
            "key" => "license"
        ],
        [
            "label" => "Operation Permit",
            "key" => "operation_permit",
        ]
    ];
    public function __construct()
    {
        $this->httpRequestHandler = new HttpRequestHandler();
        $this->model = new \Model\Rider();
        $this->docModel = new \Model\RiderDocument();
    }

    public function requiredDocuments(){
        return json_success_response("Data fetched", $this->requiredDocs);
    }

    public function verifications(){
        $resp = [
            "kyc_verification" => $this->kycVerification(),
            "vehicle_verification" => $this->vehicleVerification(),
            "documents_verification" => $this->documentVerification(),
            "address_verification" => $this->addressVerification(),
            "profile_verification" => $this->profileVerification(),
            "vehicle_photos_verification" => $this->vehiclePhotosVerification()
        ];
        return json_success_response("Verification details fetched", $resp);
    }

    private function profileVerification(){
        $user = $this->authUser;
        $rider = $this->model->where("user_id", "=", $user["user_id"])->first();
        return [
            "status" => "approved",
            "data" => $rider
        ];
    }

    private function kycVerification(){
        $user = $this->authUser;
        $rider = $this->model->where("user_id", "=", $user["user_id"])->first();
        return [
            "status" => $rider["verification_status"] ?? "not_started",
            "data" => $rider ?? []
        ];
    }

    private function vehicleVerification(){
        $user = $this->authUser;
        $vehicle = (new RiderVehicle)->where("rider_id", "=", $user["rider_id"])->first();
        return [
            "status" => ($vehicle) ? "approved" : "not_started",
            "data" => $vehicle ?? []
        ]; 
    }

    private function vehiclePhotosVerification(){
        $user = $this->authUser;
        $atts = (new RiderVehicleAttachment)->where("rider_id", "=", $user["rider_id"])->get();
        return [
            "status" => ($atts) ? "approved" : "not_started",
            "data" => $atts ?? []
        ]; 
    }

    private function addressVerification(){
        $user = $this->authUser;
        $rider = $this->model->where("user_id", "=", $user["user_id"])->first();
        $st = ($rider["current_address"] ?? false) ? "approved" : "not_started";
        return [
            "status" => $st,
            "data" => $rider ?? []
        ];
    }

    private function documentVerification(){
        $user = $this->authUser;
        $st = "pending";
        $docs = (new RiderDocument)->where("rider_id", "=", $user["rider_id"])->where("status", "<>", "rejected")->get();
        if(count($docs) == 0)
            $st = "not_started";
        return [
            "status" => $st,
            "data" => $docs
        ];
    }

    public function updateAddress(){
        $req = new \App\Requests\Riders\UpdateAddressRequest();
        $resp = $req->validate($this->httpRequestHandler->all());
        $rid = $this->authUser["rider_id"];
        $this->model->where("id", "=", $rid)->update($resp);
        return \json_success_response("Address updated", $resp);
    }

    public function updateNotificationPreference()
    {
        $req = new \App\Requests\Riders\UpdateAddressRequest();
        $resp = $this->httpRequestHandler->all();
        $rid = $this->authUser["user_id"];
        (new \Model\UserNotificationPreference)->where("user_id", "=", $rid)->update($resp);
        return \json_success_response("Notification preference updated", $resp);
    }

    public function uploadDocuments(){
        $req = new \App\Requests\Riders\UploadRiderDocumentRequest();
        $resp = $req->validate($this->httpRequestHandler->all());
        $fileProcessor = new FileProcessor();
        $docs = $resp["document_type"];
        $files = $fileProcessor->uploadedFiles("file");
        foreach($docs as $key => $dcVal){
            $fname = md5(time().mt_rand(1,9999));
            $url = $fileProcessor->storeFile("documents", $files[$key], $fname);
            $riderDcModel = new \Model\RiderDocument();
            $riderDcModel->create([
                "rider_id" => $this->authUser["rider_id"],
                "document_type" => $dcVal,
                "document_name" => $fname,
                "file_path" => $url,
                "file_size" => $files[$key]["size"],
                "mime_type" => $files[$key]["type"]
            ]);
        }
        \json_success_response("Files uploaded", $docs);
    }

    public function getDocuments($id = null){
        $rid =$this->authUser["rider_id"];
        if($id)
            $resp = $this->docModel->where("rider_id", "=", $rid)->where("id", "=", $id)->first();
        else 
            $resp = $this->docModel->where("rider_id", "=", $rid)->get();
        return \json_success_response("Documents fetched", $resp);
    }
}