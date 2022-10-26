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
        }
        echo "<pre>";
            print_r($this->tables);
        echo "</pre>";
    }

    function learntablefields($tablename){
        $fields=[];

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