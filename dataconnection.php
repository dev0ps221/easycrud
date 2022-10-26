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

    function generate_table_queries($table){
        $this->generate_delete_function($table);
    }

    function generate_delete_function($table){
        
        $tbname = $table['name'];
        $primaryname = $table['fields']['primary']['Field'];
        define("$tbname"."_primary",$primaryname);
        
        $this->{"delete_$tbname"."_entry"} = function ($funcname,$value){
            $tbname = preg_replace("#_entry#","",preg_replace("#delete_#","",$funcname));
            $primaryname = constant("$tbname"."_primary");
            $req = "DELETE FROM $tbname WHERE $primaryname = $value";
            // echo $req;
        }; 
        foreach($table['fields']['usable'] as $field){
            $this->{"delete_$tbname"."_entry_by_".$field['Field']} = function ($funcname,$value){
                $funcnamearr = explode("_by_",$funcname);
                $fieldname = $funcnamearr[1];
                $tbname = preg_replace("#_entry#","",preg_replace("#delete_#","",$funcnamearr[0]));
                $primaryname = constant("$tbname"."_primary");
                $req = "DELETE FROM $tbname WHERE $fieldname = $value";
                echo $req;
            };  
            $this->{"delete_$tbname"."_entry_by_".$field['Field']}('ehi');

        }
        $this->{"delete_$tbname"."_entry"}('test');
        
    }

    function gettablefield($table,$name){
        ($this->tables)
    }

    public function __call($name, $arguments){
        $args = [$name];
        foreach($arguments as $arg){
            array_push($args,$arg);
        }
        return call_user_func_array($this->{$name}, $args);
    }

    function generate_field_queries($tablename,$field){
        $tail = "$tablename"."_".$field['Field'];
        $this->generate_update_function($tablename,$field,$tail);
    }

    function generate_update_function($tablename,$field,$tail){
        $fname = "update_$tail";
        $this->{$fname} = function($value){
            // global $tablename;
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
        $fields['primary'] = null;
        foreach($this->query("describe ".$tablename) as $key=>$field){
            $fields[$key] = [];
            array_push($fields['raw'],$field);
            if($field['Extra']!='auto_increment'){
                $this->generate_field_queries($tablename,$field);
                array_push($fields['usable'],$field);
            }
            if($field['Key']=='PRI'){
                $fields['primary']=$field;
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