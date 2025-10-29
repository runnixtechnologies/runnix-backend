<?php 
namespace Model;

use App\Traits\HasFileUrlTrait;

class RiderDocument extends BaseModel{

    use HasFileUrlTrait;

    public $appends = ["file_url"];

    protected $table= " rider_documents";
}