<?php
class KonnektKonnektion{
    
    private $connection = null;
    private $dbuser = null;
    private $dbpass = null;
    private $dbhost = null;
    private $dbname = null;


    function gotconnection()    {
        return $this->connection != null;
    }

    function getconnection()    {
        return $this->connection;
    }

    function __connect()    {
        try {
            $this->connection = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname",$this->dbuser,$this->dbpass);
        } catch (\Exception $e)    {
            error_log(json_encode($e));
            //throw $th;
        }
        return $this->gotconnection();
    }

    function __construct($dbhost,$dbuser,$dbpass,$dbname){
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->dbname = $dbname;
    }
}