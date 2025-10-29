<?php 
namespace Model;

use App\Traits\HasFileUrlTrait;

class RiderVehicleAttachment extends BaseModel{

    use HasFileUrlTrait;

    public $appends = ["file_url"];

    protected $table= "rider_vehicle_attachments";
}