<?php 

namespace App\Requests\Riders;

use App\Requests\FormRequests;

class UpdateAddressRequest extends FormRequests{

    public $formRules = [
        "current_latitude" => ["required", "numeric"],
        "current_longitude" => ["required", "numeric"],
        "current_address" => ["required"],
    ];
}