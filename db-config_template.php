<?php
COPY and move this file as db-config.php

THIS FILE MUST BE (usually) ON TLD level of the project

IT WILL BE INCLUDED as ../phpMySql

$db1 = new DBConf;
$db1->db = "test";
$db1->host = "127.0.0.1";
$db1->passwd = "roy";
$db1->user = "roy";

//You can also use this nice goodie
require_once __DIR__ . "/phpMysql/dbwrapper.php";

function DBS7(): DBWrapper
{
    static $db = null;
    if (!$db) {
        $db7 = new DBConf;
        $db7->db = "test";
        $db7->host = "127.0.0.1";
        $db7->port = 3307;
        $db7->passwd = "";
        $db7->user = "";
        $db = new \DBWrapper($db7);
    }
    return $db;
}

