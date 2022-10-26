<?php
class CrudKonnektion{
    
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
        
        $tbname = $table['name'];
        $primaryname = $table['fields']['primary']['Field'];
        define("$tbname"."_primary",$primaryname);
        $this->generate_delete_function($table);
        $this->generate_update_function($table);
        $this->generate_select_function($table);
        $this->select_chatmod_entries();
    }

    function generate_update_function($table){

        $tbname = $table['name'];
        foreach($table['fields']['usable'] as $field){
            $tail = "$tbname"."_".$field['Field'];  
            $fname = "update_$tail";
            $this->{$fname} = function($funcname,$value,$where=""){
                $funcnamearr = explode("_",$funcname);
                $fieldname = $funcnamearr[2];
                $tbname = $funcnamearr[1];
                $field = $this->gettablefielddata($tbname,$fieldname);
                $fieldname = $field['Field'];
                $req = "UPDATE $tbname set $fieldname = ";
                if(in_array($field['Field'],['password','pwd'])){
                    $req="$req"."password('$value')";
                }else{
                    if($field['Type']=='text' or $field['Type']=='char'){
                        $req = "$req '";
                    }
                    $req="$req$value";
                    if($field['Type']=='text' or $field['Type']=='char'){
                        $req = "$req'";
                    }
                }
                if($where){
                    $req ="$req WHERE $where"; 
                }
                $result = $this->query($req);
                return $result;
            };
        }
    }

    function generate_delete_function($table){
        
        
        $tbname = $table['name'];
        $this->{"delete_$tbname"."_entries"} = function ($funcname){
            $tbname = preg_replace("#_entries#","",preg_replace("#delete_#","",$funcname));
            $req = "DELETE FROM $tbname";
            $result = $this->query($req);
            return $result;
        }; 
        $this->{"delete_$tbname"."_entry"} = function ($funcname,$value){
            $tbname = preg_replace("#_entry#","",preg_replace("#delete_#","",$funcname));
            $primaryname = constant("$tbname"."_primary");
            $req = "DELETE FROM $tbname WHERE $primaryname = $value";
            $result = $this->query($req);
            return $result;
        }; 
        foreach($table['fields']['usable'] as $field){
            $this->{"delete_$tbname"."_entry_by_".$field['Field']} = function ($funcname,$value){
                $funcnamearr = explode("_by_",$funcname);
                $fieldname = $funcnamearr[1];
                $tbname = preg_replace("#_entry#","",preg_replace("#delete_#","",$funcnamearr[0]));
                $primaryname = constant("$tbname"."_primary");
                $req = "DELETE FROM $tbname WHERE $fieldname = $value";
                $result = $this->query($req);
                return $result;
            };

        }
        
    }

    function generate_select_function($table){
        
        
        $tbname = $table['name'];
        $this->{"select_$tbname"."_entries"} = function ($funcname,$target="*"){
            $tbname = preg_replace("#_entries#","",preg_replace("#select_#","",$funcname));
            $req = "select $target FROM $tbname ";
            echo $req;
            $result = $this->query($req);
            return $result;
        };
        $this->{"select_$tbname"."_entry"} = function ($funcname,$value,$target="*"){
            $tbname = preg_replace("#_entry#","",preg_replace("#select_#","",$funcname));
            $primaryname = constant("$tbname"."_primary");
            $req = "select $target FROM $tbname WHERE $primaryname = $value";
            echo $req;
            $result = $this->query($req);
            return $result;
        }; 
        foreach($table['fields']['raw'] as $field){
            $this->{"select_$tbname"."_entry_by_".$field['Field']} = function ($funcname,$value,$target="*"){
                $funcnamearr = explode("_by_",$funcname);
                $fieldname = $funcnamearr[1];
                $tbname = preg_replace("#_entry#","",preg_replace("#select_#","",$funcnamearr[0]));
                $primaryname = constant("$tbname"."_primary");
                $req = "select $target FROM $tbname WHERE $fieldname = $value";
                echo $req;
                $result = $this->query($req);
                return $result;
            };

        }
        
    }

    function gettablefielddata($table,$name){
        $match = null;
        $table = $this->gettabledata($table);
        
        if(!$table)return $match;
        foreach($table['fields']['raw'] as $field){
            if($field['Field']==$name) $match = $field;
        }
        return $match;
    }

    function gettabledata($table){
        $match = null;
        foreach($this->tables as $tbl){
            if($tbl['name']==$table) $match = $tbl;
        }
        return $match;
    }

    public function __call($name, $arguments){
        $args = [$name];
        foreach($arguments as $arg){
            array_push($args,$arg);
        }
        return call_user_func_array($this->{$name}, $args);
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