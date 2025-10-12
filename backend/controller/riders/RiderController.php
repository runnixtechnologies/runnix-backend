<?php 

namespace Controller\Riders;

use App\Requests\HttpRequestHandler;
use Controller\BaseController;
use App\Facades\FileProcessor;

use function Middleware\authenticateRequest;

class RiderController extends BaseController{


    public $httpRequestHandler;
    public function __construct()
    {
        $this->httpRequestHandler = new HttpRequestHandler();
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
}