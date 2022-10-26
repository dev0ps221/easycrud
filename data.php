<?php

    include('dataconnection.php');
    $dataconnection = new CrudKonnektion();
    if($dataconnection->__connect()){
        $dataconnection->getdatabasetables(); 
    }