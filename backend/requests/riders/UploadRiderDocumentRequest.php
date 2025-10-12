<?php 

namespace App\Requests\Riders;

use App\Requests\FormRequests;

class UploadRiderDocumentRequest extends FormRequests{

    public $formRules = [
        "document_type" => ["required", "array"],
        "document_type.*" => ["required", "in:id_card,license,insurance"],
        "file" => ["required", "array"],
        "file.*" => ["required", "mime:jpg,png", "maxsize:500"]
    ];
}