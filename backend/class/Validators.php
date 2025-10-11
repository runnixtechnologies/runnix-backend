<?php 

namespace App\Class;

use Exception;
use finfo;

class Validators{

    private $requestFields = [];
    public $errors = [];
    public $validatedData = [];
    public $currentRules = [];
    private $errorMessages = [
        "required" => ":attribute is required",
        "file" => ":attribute must be a valid file",
        "maxsize" => ":attribute maximum size should be :paramKB",
        "mimetype" => ":attribute must be of type :param",
        "mime" => ":attribute must be :param"
    ];
    public function __construct()
    {
        //Parse data   
    }

    public function validate($formFields, $rules, $requestFields = []){
        $this->requestFields = $requestFields;
        foreach($formFields as $fmKey => $fmValue){
            $fmRules = $rules[$fmKey];
            $this->currentRules = $fmRules;
            foreach($fmRules as $rKey => $rValue){
                [$rValue, $rParam] = array_pad(explode(':', $rValue, 2), 2, null);
                $ruleMethod = "validate". ucfirst($rValue);
                try{
                    if(isset($this->errors[$fmValue]))
                        continue;
                    $this->$ruleMethod($fmValue, $rParam);
                }catch(Exception $ex){
                    abort(500, "Validator rule for $rValue does not exist");
                }
            }
            if(!isset($this->errors[$fmValue]))
                $this->addValidated($fmValue);
        }
    }

    private function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    private function validateFile($key, $params = null){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        if (!isset($file['error']) || is_array($file['error'])) {
            return $this->addError($key, "file");
        }
    }

    private function validateMaxsize($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        $maxBytes = $param * 1024;
        if ($file['size'] > $maxBytes) {
            return $this->addError($key, "maxsize", $param);
        }
    }

    private function validateMime($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        if(!is_array($param))
            $param = explode(",", $param);
        if (!in_array($extension, $param, true)) {
            return $this->addError($key, "mime", $param);
        }
    }

    private function validateMimetype($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if(!is_array($param))
            $param = explode(",", $param);
        if (!in_array($mime, $param, true)) {
            return $this->addError($key, "mimetype", $param);
        }
    }

    private function validateRequired($key, $param = null)
    {
        $rqDt = $this->requestFields[$key] ?? $_FILES[$key]["name"] ?? null;
        if(!$rqDt || is_null($rqDt) || empty($rqDt))
            return $this->addError($key, "required");
        return true;
    }

    private function validateNullable(){

    }

    private function addError($key, $errorBadge, $param = null){
        if(!isset($this->errors[$key]))
            $this->errors[$key] = [];
        $msg = $this->errorMessages[$errorBadge] ?? "";
        $msg = str_replace(":attribute", $key, $msg);
        if($param && is_array($param))
            $param = implode(",", $param);
        if($param)
            $msg = str_replace(":param", $param, $msg);
        array_push($this->errors[$key], $msg);
    }

    private function addValidated($key){
        $this->validatedData[$key] = $this->requestFields[$key] ?? null;
    }
}