<?php

require_once "dbWrapper/2.php";

$db = new DBWrapper($db1);

$sql = "SELECT NOW()";
$res = $db->query($sql);

/*
$db->getLine($sql);

//https://www.w3schools.com/php/func_mysqli_fetch_all.asp

$db->getAll($sql, resulttype MYSQLI_ASSOC); 

mysqli_fetch_all
*/
while($row = $res->fetch_object()){
	print_r($row);
}
