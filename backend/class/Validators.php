<?php 

namespace App\Class;

use Exception;
use finfo;

class Validators{

    /**
     * Validators added
     * required
     * file
     * maxsize
     * mime
     * mimetype
     */
    private $requestFields = [];
    public $errors = [];
    public $validatedData = [];
    private $multiIndex = null;
    public $currentRules = [];
    private $errorMessages = [
        "required" => ":attribute is required",
        "file" => ":attribute must be a valid file",
        "maxsize" => ":attribute maximum size should be :paramKB",
        "mimetype" => ":attribute must be of type :param",
        "mime" => ":attribute must be :param",
        "in" => ":attribute must be any of the items :param",
        "array" => ":attribute must be of type array",
        "numeric" => ":attribute must be numeric"
    ];
    public function __construct()
    {
        //Parse data   
    }

    public function validate($formFields, $rules, $requestFields = []){
        $this->requestFields = $requestFields;
        foreach($formFields as $fmKey => $fmValue){
            $this->currentRules = $rules[$fmKey];
            if(strpos($fmValue, ".*")){
                $this->processMultiFielsValidator($fmValue);
            }else{
                $this->processSingleFieldValidator($fmValue);
            }
        }
    }

    private function processMultiFielsValidator($fieldKey){
        $realKey = explode(".*", $fieldKey)[0];
        $this->validateArray($realKey);
        if(!$this->hasError($realKey)){
            $fieldVal = $this->requestFields[$realKey];
            if($_FILES[$realKey] ?? false){
                foreach($fieldVal["name"] as $key => $fl){
                    $this->multiIndex = $key;
                    $this->processSingleFieldValidator($realKey);
                }
            }else{
                foreach($fieldVal as $key => $fl){
                    $this->multiIndex = $key;
                    $this->processSingleFieldValidator($realKey);
                }
            }
            
        }
        $this->multiIndex = null;
    }


    private function processSingleFieldValidator($fieldKey){
        $fmRules = $this->currentRules;
        foreach($fmRules as $rKey => $rValue){
            [$rValue, $rParam] = array_pad(explode(':', $rValue, 2), 2, null);
            $ruleMethod = "validate". ucfirst($rValue);
            try{
                if($this->hasError($fieldKey))
                    continue;
                $this->$ruleMethod($fieldKey, $rParam);
            }catch(Exception $ex){
                abort(500, "Validator rule for $rValue does not exist");
            }
        }
        if(!isset($this->errors[$fieldKey]))
            $this->addValidated($fieldKey);
    }

    private function hasError($key){
        if(is_null($this->multiIndex))
            return (isset($this->errors[$key]));
        else 
            return (isset($this->errors[$key][$this->multiIndex]));
    }

    private function validateIn($key, $param = null){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $param2 = explode(",", $param);
        if(is_null($this->multiIndex))
            $elvar = $this->requestFields[$key];
        else 
            $elvar = $this->requestFields[$key][$this->multiIndex];
        if(!in_array($elvar, $param2))
            $this->addError($key, "in", $param);
    }

    private function validateNumeric($key, $param = null){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        if(is_null($this->multiIndex))
            $elvar = $this->requestFields[$key];
        else 
            $elvar = $this->requestFields[$key][$this->multiIndex];
        if(!is_numeric($elvar))
            $this->addError($key, "numeric", $param);
    }

    private function validateArray($key, $param = null){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        if(is_null($this->multiIndex)){
            if(!is_array($this->requestFields[$key]))
                $this->addError($key, "array");
        }else{
            if(!is_array($this->requestFields[$key][$this->multiIndex]))
                $this->addError($key, "array");
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
        if(is_null($this->multiIndex)){
            if (!isset($file['error']) || is_array($file['error'])) {
                return $this->addError($key, "file");
            }
        }else{
            if (!isset($file['error'][$this->multiIndex]) || is_array($file['error'][$this->multiIndex])) {
                return $this->addError($key, "file");
            }
        }
    }

    private function validateMaxsize($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        $maxBytes = $param * 1024;
        if(is_null($this->multiIndex)){
            if ($file['size'] > $maxBytes) {
                return $this->addError($key, "maxsize", $param);
            }
        }else{
            if ($file['size'][$this->multiIndex] > $maxBytes) {
                return $this->addError($key, "maxsize", $param);
            }
        }
    }

    private function validateMime($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        $file = $_FILES[$key];
        if(is_null($this->multiIndex))
            $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        else 
            $extension = pathinfo($file["name"][$this->multiIndex], PATHINFO_EXTENSION);
        if(!is_array($param))
            $param = explode(",", $param);
        if (!in_array($extension, $param, true)) {
            return $this->addError($key, "mime", $param);
        }
    }

    private function validateMimetype($key, $param){
        $vl = $this->validateRequired($key);
        if(!$vl) return;
        if(!is_array($param))
            $param = explode(",", $param);
        $file = $_FILES[$key];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if(is_null($this->multiIndex))
            $mime = $finfo->file($file['tmp_name']);
        else
            $mime = $finfo->file($file['tmp_name'][$this->multiIndex]);
        if (!in_array($mime, $param, true)) {
            return $this->addError($key, "mimetype", $param);
        }
        
    }

    // public function 

    private function validateRequired($key, $param = null)
    {
        if(is_null($this->multiIndex))
            $rqDt = $this->requestFields[$key] ?? $_FILES[$key]["name"] ?? null;
        else 
            $rqDt = $this->requestFields[$key][$this->multiIndex] ?? $_FILES[$key]["name"][$this->multiIndex] ?? null;
        if(!$rqDt || is_null($rqDt) || empty($rqDt))
            return $this->addError($key, "required");
        return true;
    }

    private function validateNullable(){

    }

    private function addError($key, $errorBadge, $param = null){
        if(!isset($this->errors[$key]))
            $this->errors[$key] = [];
        if(!is_null($this->multiIndex)){
            if(!isset($this->errors[$key][$this->multiIndex]))
                $this->errors[$key][$this->multiIndex] = [];
        }
        $msg = $this->errorMessages[$errorBadge] ?? "";
        $msg = str_replace(":attribute", $key.($this->multiIndex ? ".$this->multiIndex": ''), $msg);
        if($param && is_array($param))
            $param = implode(",", $param);
        if($param)
            $msg = str_replace(":param", $param, $msg);
        if(is_null($this->multiIndex))
            $this->errors[$key][0] = $msg;
            // array_push($this->errors[$key], $msg);
        else 
            $this->errors[$key][$this->multiIndex][0] = $msg;
            // array_push($this->errors[$key][$this->multiIndex], $msg);
        
    }

    private function addValidated($key){
        if($_FILES[$key] ?? null)
            return;
        if(is_null($this->multiIndex))
            $this->validatedData[$key] = $this->requestFields[$key] ?? null;
        else{
            $this->validatedData[$key][$this->multiIndex] = $this->requestFields[$key][$this->multiIndex] ?? null;
        }
    }
}