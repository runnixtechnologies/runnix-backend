<?php 

namespace Model;

use Config\Database;
use PDO;

class BaseModel{

    protected $table;
    protected $fields = [];
    private $dataFields = [];
    private $dataValues = [];
    protected $primaryKey = "id";
    private $dbConnect;

    private function connectToDB(){
        $this->dbConnect = (new Database)->getConnection();
    }

    private function includeCreateDefaults($data = []){
        if(!in_array("created_at", $data))
            $data["created_at"] = date("Y-m-d H:i:s");
        if(!in_array("updated_at", $data))
            $data["updated_at"] = date("Y-m-d H:i:s");
        return $data;
    }

    private function includeUpdateDefaults($data = []){
        if(!in_array("updated_at", $data))
            $data["updated_at"] = date("Y-m-d H:i:s");
        return $data;
    }

    private function getFieldsData($data, $action = null){
        if($action == "create")
            $data = $this->includeCreateDefaults($data);
        if($action == "update")
            $data = $this->includeCreateDefaults($data);
        $this->dataFields = array_keys($data);
        $this->dataValues = array_values($data);
    }


    private function bindDbValues(&$stmt){
        foreach($this->dataFields as $key => $fl)
            $stmt->bindParam(":$fl", $this->dataValues[$key]);
    }

    // protected function get
    public function create($data){
        $this->connectToDB();
        $this->getFieldsData($data, "Create");
        $cols = implode(",", $this->dataFields);
        $newArray = array_map(function($item) {
            return ":".$item;
        }, $this->dataFields);
        $colParams = implode(",", $newArray);
        $sql = "INSERT INTO $this->table($cols) VALUES($colParams)";
        $stmt = $this->dbConnect->prepare($sql);
        $this->bindDbValues($stmt);
        $stmt->execute();
    }

    public function update($id, $data){
        $this->connectToDB();
        $data["id"] = $id;
        $this->getFieldsData($data, "update");

    }

    public function find($id, $params = []){
        $this->connectToDB();
        $dl = ["pk" => $id];
        $this->getFieldsData($params);
        $cols = $this->dataFields ? implode(",", $this->dataFields): "*";
        $sql = "SELECT $cols FROM $this->table WHERE $this->primaryKey = '$id'";
        $stmt = $this->dbConnect->prepare($sql);
        $this->bindDbValues($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get($params = []){
        $this->connectToDB();
        $this->getFieldsData($params);
        $cols = $this->dataFields ? implode(",", $this->dataFields): "*";
        $sql = "SELECT $cols FROM $this->table;";
        $stmt = $this->dbConnect->prepare($sql);
        $this->bindDbValues($stmt);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function delete()
    {

    }
}