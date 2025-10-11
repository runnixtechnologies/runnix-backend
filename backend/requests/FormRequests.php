<?php 

namespace App\Requests;

use App\Class\Validators;
use Exception;

class FormRequests{

    public $formRules = [];
    private $formFields = [];
    protected $rules = [];
    protected $validator;
    private $formErrors;
    private $formValid = [];
    public $requestFields = [];
    public function __construct()
    {
        
    }

    public function validate($requestFields){
        try{
            $this->requestFields = $requestFields ?? [];
            $this->validator = new Validators();
            $this->formFields = array_keys($this->formRules);
            $this->rules = array_values($this->formRules);

            $this->validator->validate($this->formFields, $this->rules, $this->requestFields);
            $this->formErrors = $this->validator->errors;
            $this->formValid = $this->validator->validatedData;
            if(count($this->formErrors) > 0){
                $msg = implode("\r\n", array_values($this->formErrors)[0]);
                abort(400, $msg, [], $this->formErrors);
            }
            return $this->formValid;
        }catch(Exception $ex){
            echo($ex->getMessage());
        }
    }

    public function getFile($key){
        return $_FILES[$key] ?? null;
    }

    private function validated(){
        return $this->formValid;
    }

    public function errors(){
        return $this->formErrors;
    }

    public function fields(){
        return $this->formFields;
    }
}