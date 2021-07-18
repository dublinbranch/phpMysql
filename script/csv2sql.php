<?php
require_once __DIR__ . "/../dbwrapper.php";

$conf = new DBConf();
$conf->db = "test";
$conf->host = "127.0.0.1";
$conf->passwd = "roy";
$conf->user = "roy";

$db = new DBWrapper($conf);

//use the first line to set the name of the column
$firstLineWithName = true;
$fileName = "/home/roy/deletein/repack1.csv";
$tableName = "repack1";
//Some CSV exporter tool should be jailed (even if they are not phisical person) because they add COMMA in the number -.-
$removeExtraComma = false;
$rowNumberAsId = false;
$idName = "uid";
$splitWith = ",";
$createTable = false;

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
    $sql = "CREATE TABLE `test`.{$tableName} (";
    $sql .= implode(',', $pack);
    $sql .= ")";


    $db->query($sql);
}
if (!$firstLineWithName) {
    rewind($fptr);
}

$maxQ = 1000;
$i = 0;
$baseSql = "insert into `test`.{$tableName} ($colSet) VALUES ";
$sql = $baseSql;
$pending = array();
while (!feof($fptr)) {
    $line = fgets($fptr);
    $i++;
    $line = trim($line);
    if (strlen($line) < 1) { //what is that ?
        continue;
    }
    $a = str_getcsv($line, $splitWith);

    //print_r($a);
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