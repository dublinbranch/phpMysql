<?php
require_once __DIR__ . "/../dbwrapper.php";

$conf = new DBConf();
//$conf->db = "test";
//$conf->host = "127.0.0.1";
//$conf->passwd = "97037BF97C19A2E88CF7891";
//$conf->user = "roys14";

$conf->db = "test";
$conf->host = "127.0.0.1";
$conf->passwd = "roy";
$conf->user = "roy";


$db = new DBWrapper($conf);

//use the first line to set the name of the column
$firstLineWithName = true;
$fileName = "file:///home/roy/Descargas/AFD - Sedo Assets.csv";
$tableName = "facebookAdFormat";
//Some CSV exporter tool should be jailed (even if they are not phisical person) because they add COMMA in the number -.-
$removeExtraComma = false;
$rowNumberAsId = false;
$idName = "id";
$splitWith = ",";
$createTable = true;

$fptr = fopen("{$fileName}", "r");


$start = "START TRANSACTION;";
$endCom = "COMMIT;";
$db->query($start, false, true);

$first = fgets($fptr);


//parse properly the CSV
$column = str_getcsv($first, $splitWith);

//print_r($column );

$pack = array();
$colName = array();
if ($rowNumberAsId) {
    $pack[] = "`{$idName}` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY";
    $colName[] = $idName;
}

foreach ($column as $key => $col) {


    //set the name of the column
    $name = $fallBack = 'col_' . $key;
    if ($firstLineWithName) {
        $name = $col;
    }

    $name = str_replace(',', '', $name);
    //remove space
    $name = str_replace(' ', '', $name);
    //remove dot
    $name = str_replace('.', '', $name);
    if ($name == '') {
        $name = $fallBack;
    }
    $name = trim($name);
    $name = $db->escape($name);

    $pack[] = "`{$name}` text COLLATE 'utf8mb4_bin' NOT NULL";
    $colName[] = $name;
}

$colSet = implode(',', $colName);
if ($createTable) {
    $sql = "DROP TABLE IF EXISTS `test`.{$tableName};";
    $db->query($sql);

    $DDL = implode(',', $pack);

    $sql = "CREATE TABLE `test`.{$tableName} ($DDL)";
    $db->query($sql);
}
if (!$firstLineWithName) {
    rewind($fptr);
}

$maxQ = 1000;
$i = 0;
$baseSql = "insert ignore into `test`.{$tableName} ($colSet) VALUES ";
$sql = $baseSql;
$pending = array();

//should be fine for 99% of the cases
while (($a = fgetcsv($fptr, 0, ",")) !== FALSE) {
//if you have an IMMENSE CSV try a hand rolled line by line splitting
//
//while (!feof($fptr)) {
//    $line = fgets($fptr);
//    $i++;
//    $line = trim($line);
//    if (strlen($line) < 1) { //what is that ?
//        continue;
//    }
//    $a = str_getcsv($line, $splitWith);

    $rer = array();
    if ($rowNumberAsId) {
        $rer[] = $i;
    }


    foreach ($a as $col) {
        if ($removeExtraComma) {
            $col = str_replace(',', '', $col);
        }
        //retard -.-
        $col = str_replace('$', '', $col);
        $col = str_replace('%', '', $col);
        $col = trim($col);

        $rer[] = base64this($col);
    }
    //certain exporter do not export the full line -.- so fill the gaps
    $r1 = count($rer);
    $h1 = count($colName);
    if ($r1 > $h1) {
        echo "line $i \n";
        print_r($line);
        print_r($a);
        die("\nmore line that header, fix the CSV $r1 vs $h1 (header)\n");
    }
    if ($r1 < $h1) {
        //just fill the gaps
        for ($x = 1; $x <= $h1 - $r1; $x++) {
            $rer[] = "''";
        }
    }

    //print_r($rer);

    $blob = implode(',', $rer);
    $pending[] = "($blob)";

    if ($i % $maxQ == 0) {
        $sql = $baseSql;
        $sql .= implode(',', $pending);
        $pending = array();

        echo "@ pos $i\n";
        //echo $sql;

        $db->query($sql);
        $db->query($start, false, true);
        $db->query($endCom, false, true);
    }
}
if (sizeof($pending)) {
//flush pending stuff
    $sql = $baseSql;
    $sql .= implode(',', $pending);

//echo "@ pos $i\n";

    $db->query($sql);
    $db->query($start, false, true);
    $db->query($endCom, false, true);
}


echo memory_get_peak_usage();
die();
