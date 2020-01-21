<?php

require_once "dbwrapper.php";

$db = new DBWrapper($db2);


//-------------------------------------------------------
//$sql = "SELECT * FROM tracking";
//$res = $db->query($sql);

// while($row = $res->fetch_object()){
// 	print_r($row);
// }


//-------------------------------------------------------
// // test getLine()
// echo 'test getLine()
// ';
// $sql = "SELECT * FROM tracking";
// $row = $db->getLine($sql);
// print_r($row);


//-------------------------------------------------------
// https://www.w3schools.com/php/func_mysqli_fetch_all.asp
// echo 'test getAll() MYSQLI_ASSOC';
//
// $sql = "SELECT * FROM tracking";
// $rows = $db->getAll($sql, MYSQLI_ASSOC);
// foreach ($rows as &$row) {
//     print_r($row);
// }


//-------------------------------------------------------
echo 'test getAll() MYSQLI_NUM';

$sql = "SELECT * FROM tracking";
$rows = $db->getAll($sql, MYSQLI_NUM);
foreach ($rows as &$row) {
    print_r($row);
}
