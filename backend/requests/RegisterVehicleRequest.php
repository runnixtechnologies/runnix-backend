<?php 

namespace App\Requests;

class RegisterVehicleRequest extends FormRequests{

    public $formRules = [
        "vehicle_type" => ["required"],
        "brand" => ["required"],
        "model" => ["required"],
        "year" => ["required"],
        "color" => ["required"],
        "license_plate" => ["required"],
        "chassis_number" => ["required"]
    ];
}