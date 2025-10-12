<?php 

namespace App\Requests\Riders;

use App\Requests\FormRequests;

class UploadVehicleAttachmentRequest extends FormRequests{

    public $formRules = [
        "document_type" => ["required", "array"],
        "document_type.*" => ["required"],
        "file" => ["required", "array"],
        "file.*" => ["required", "file", "mime:jpn,png,jpeg,pdf"],
    ];
}