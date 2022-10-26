<?php

    include('dataconnection.php');
    $dataconnection = new CrudtKonnektion();
    if($dataconnection->__connect()){
        $dataconnection->getdatabasetables(); 
    }