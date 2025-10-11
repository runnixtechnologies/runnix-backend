<?php 

namespace App\Facades;

class FileProcessor{

    public $fileUrl;
    public $filePath;
    private $uploadPath;
    public function __construct()
    {
        // $this->uploadPath = upload_path();
    }

    public function checkDirectory($dir){
        return is_dir($dir);
    }
    public function createDirectory($dir){
        if (mkdir($dir, 0755, true)) {
            return true;
        } else {
            true;
        }
    }
    public function storeFile($path, $content, $filename = null){

        if(!$this->checkDirectory(upload_path($path)))
            $this->createDirectory(upload_path($path));
        $isUploaded = false;
        $upload = [];
        if(is_array($content)){
            $isUploaded = $content["tmp_name"] ?? null;
            $uploadFile = $content;
            if(!$isUploaded)
                $content = json_encode($content);
        }
        if($isUploaded){
            $filename = $filename ?? sha1(time());
            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);
            $extension = pathinfo($uploadFile["name"], PATHINFO_EXTENSION);
            $filename = $filename.".". $extension;
            $this->filePath = trim($path, "/")."/". $filename;
            if (!move_uploaded_file($isUploaded, upload_path($this->filePath))) {
                abort(422, "file could not be uploaded");
            }
        }else{
            $this->filePath = trim($path, "/")."/". $filename;
            file_put_contents(upload_path($this->filePath), $content);
        }
        return $this->filePath;
    }

    public function fileUrl(){
        
    }
}

