<?php 

namespace Model;

use Config\Database;
use PDO;

class BaseModel{

    protected $table;
    protected $fields = [];
    private $dataFields = [];
    private $dataValues = [];
    private $offDataFields = [];
    private $offDataValues = [];
    protected $primaryKey = "id";
    private $dbConnect;
    private $processedData = [];
    private $sqlQuery;
    private $sqlQueryBuild;

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
            $data = $this->includeUpdateDefaults($data);
        $this->processedData = $data;
        $this->dataFields = array_keys($data);
        $this->dataValues = array_values($data);
    }


    private function bindDbValues(&$stmt){
        foreach($this->dataFields as $key => $fl)
            $stmt->bindParam(":$fl", $this->dataValues[$key]);
        foreach($this->offDataFields as $key => $fl)
            $stmt->bindParam(":$fl", $this->offDataValues[$key]);
    }

    public function buildQuery($query, $col, $value){
        $this->sqlQueryBuild .= $query;
        $this->sqlQueryBuild = trim($this->sqlQueryBuild, "AND");
        $this->sqlQueryBuild = str_replace("AND WHERE", "AND", $this->sqlQueryBuild);
        array_push($this->offDataFields, $col);
        array_push($this->offDataValues, $value);
        
        return $this;
    }

    public function where($col, $operand, $value){
        $sql = "AND WHERE {$col}{$operand}:{$col} ";
        return $this->buildQuery($sql, $col, $value);
    }

    public function getRawSql(){
        return $this->sqlQuery. $this->sqlQueryBuild;
    }

    public function orWhere($col, $operand, $value){
        $sql = "OR {$col}{$operand}:{$col} ";
        return $this->buildQuery($sql, $col, $value);
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
        $this->queryBuilder("INSERT INTO $this->table($cols) VALUES($colParams)");
        $stmt = $this->dbConnect->prepare($this->sqlQuery);
        $this->bindDbValues($stmt);
        $stmt->execute();
        $lstId = $this->dbConnect->lastInsertId();
        return $this->processedData + ["id" => $lstId];
    }

    public function update($data){
        $this->connectToDB();
        $this->getFieldsData($data, "update");
        $upq = "";
        foreach($this->dataFields as $key => $val){
            $upq.= " `$val`=:$val,";
        }
        $upq = trim($upq, ",");
        $this->queryBuilder("UPDATE $this->table SET $upq");
        $stmt = $this->dbConnect->prepare($this->sqlQuery);
        $this->bindDbValues($stmt);
        $stmt->execute();
    }

    private function queryBuilder($query){
        $this->sqlQuery = $query. $this->sqlQueryBuild.";";
        return $this->sqlQuery;
    }

    private function sendQuery(){

    }

    public function find($id, $params = []){
        $this->connectToDB();
        $dl = ["pk" => $id];
        $this->getFieldsData($params);
        $cols = $this->dataFields ? implode(",", $this->dataFields): "*";
        $this->queryBuilder("SELECT $cols FROM $this->table WHERE $this->primaryKey = '$id'");
        $stmt = $this->dbConnect->prepare($this->sqlQuery);
        $this->bindDbValues($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get($params = []){
        $this->connectToDB();
        $this->getFieldsData($params);
        $cols = $this->dataFields ? implode(",", $this->dataFields): "*";
        $this->queryBuilder("SELECT $cols FROM $this->table");
        $stmt = $this->dbConnect->prepare($this->sqlQuery);
        $this->bindDbValues($stmt);
        $stmt->execute();
        return ($stmt->fetchAll(PDO::FETCH_ASSOC) ?? []);
    }

    public function first($params = []){
        $this->connectToDB();
        $this->getFieldsData($params);
        $cols = $this->dataFields ? implode(",", $this->dataFields): "*";
        $this->queryBuilder("SELECT $cols FROM $this->table");
        $stmt = $this->dbConnect->prepare($this->sqlQuery);
        $this->bindDbValues($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function delete()
    {

    }
}