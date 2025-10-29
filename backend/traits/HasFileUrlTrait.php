<?php 

namespace App\Traits;
trait HasFileUrlTrait{

    public function append_file_url($params = [])
    {
        return $_ENV["APP_URL"]."/uploads/" . ($params["file_path"] ?? '');
    }
}