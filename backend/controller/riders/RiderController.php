<?php 

namespace Controller\Riders;

use App\Requests\HttpRequestHandler;
use Controller\BaseController;
use App\Facades\FileProcessor;

use function Middleware\authenticateRequest;

class RiderController extends BaseController{


    public $httpRequestHandler;
    public $model;
    public $docModel;
    public function __construct()
    {
        $this->httpRequestHandler = new HttpRequestHandler();
        $this->model = new \Model\Rider();
        $this->docModel = new \Model\RiderDocument();
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