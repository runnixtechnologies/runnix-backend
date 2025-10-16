<?php 

namespace App\Requests\Riders;

use App\Requests\FormRequests;

class RegisterVehicleRequest extends FormRequests{

    public $formRules = [
        "vehicle_type" => ["required", "in:bicycle,car,motorcycle,van"],
        "years_of_experience" => ["required", "numeric"],
        "brand" => ["required"],
        "model" => ["required"],
        "year" => ["required"],
        "color" => ["required"],
        "license_plate" => ["required"],
        "chassis_number" => ["required"]
    ];
}