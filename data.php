<?php

    include('crudconnection.php');
    $dataconnection = new CrudConnection();
    if($dataconnection->__connect()){
        print_r($dataconnection->getdatabasetables()); 
    }