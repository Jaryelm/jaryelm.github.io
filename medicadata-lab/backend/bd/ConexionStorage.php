<?php

$dbConfiguration = array (
    "local" => array(
        "main" => array (
            'host' => 'localhost',
            'user' => 'root',
            'pass' => 'hpk7pdwM4',
            'name' => 'medic9ue_medi_data',
        ),
        "rrhh" => array (
            'host' => 'localhost',
            'user' => 'root',
            'pass' => 'hpk7pdwM4',
            'name' => 'medic9ue_medi_rrhh_interviews'
    
        ),
        "singletons" => ["main", "rrhh"]
    ),
    "production" => array (
        "main" => array (
            'host' => '162.241.123.41',
            'user' => 'medic9ue_moisesc',
            'pass' => 'Mrecords%7',
            'name' => 'medic9ue_medi_data',
        ),
        "rrhh" => array (
            'host' => 'localhost',
            'user' => 'root',
            'pass' => '',
            'name' => 'medic9ue_medi_rrhh_interviews'
        ),
        "singletons" => ["main", "rrhh"]
    )
);

