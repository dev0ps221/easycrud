<?php

    include('dataconnection.php');
    $dataconnection = new KonnektKonnektion();
    if($dataconnection->__connect()){
        $dataconnection->getdatabasetables(); 
    }