<?php
class KonnektKonnektion{
    
    private $connection = null;
    private $dbuser = null;
    private $dbpass = null;
    private $dbhost = null;
    private $dbname = null;
    private $tables = [];

    function gotconnection(){
        return $this->connection != null;
    }

    function getconnection(){
        return $this->connection;
    }

    function __connect(){
        try{
            $this->connection = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname",$this->dbuser,$this->dbpass);
        } catch (\Exception $e){
            error_log(json_encode($e));
            //throw $th;
        }
        return $this->gotconnection();
    }

    function getdatabasetables(){
        $this->tables = [];
        foreach($this->query('show tables') as $key=>$table){
            $this->tables[$key] = [];
            $this->tables[$key]['name'] = $table[0];
            $this->tables[$key]['fields'] = $this->learntablefields($table[0]);
            $this->generate_table_queries($this->tables[$key]);
        }
        echo "<pre>";
            print_r($this->tables);
        echo "</pre>";
    }

    function generate_field_queries($tablename,$field){
        $tail = "$tablename_$field";
        generate_update_function($tablename,$field,$tail);
    }

    function generate_update_function($tablename,$field,$tail){
        $fname = "update_$tail";
        $this->{$fname} = function($value){
            $fieldname = $field['Field'];
            $req = "UPDATE $tablename set $fieldname = ";
            if($field['Type']=='text' or $field['Type']=='char'){
                $req = "$req '";
            }
            $req=$value;
            if($field['Type']=='text' or $field['Type']=='char'){
                $req = "$req'";
            }
        };        
    }

    function learntablefields($tablename){
        $fields=[];
        $fields['raw'] = [];
        $fields['usable'] = [];
        foreach($this->query("describe ".$tablename) as $key=>$field){
            $fields[$key] = [];
            array_push($fields['raw'],$field);
            if($field['Extra']!='auto_increment'){
                $field = $this->generate_field_queries($tablename,$field);
                array_push($fields['usable'],$field);
            }
        }
        return $fields;
    }

    function query($query,$dofetch=true){
        $action =   $this->connection->query($query);
        if($action){
            if($dofetch==false){
                return $action;
            }
            $res = [];
            while($donnees=$action->fetch()){
                array_push($res,$donnees);
            }
            return $res;
        }else{
            return $action;
        }
    }

    function __construct($dbhost='',$dbuser='root',$dbpass='',$dbname='konnektdata'){
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->dbname = $dbname;
    }
}