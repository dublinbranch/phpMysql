<?php

class DBConf{
	var $host;
	var $user;
	var $passwd;
	var $db; //default database
	var $port = 3306;
}

require_once __DIR__ . "/../db-config.php";

class DBWrapper{
	private $conn;
	private $lastId;
	function __construct(DBConf $conf){
		$this->conn = new mysqli($conf->host, $conf->user, $conf->passwd, $conf->db, $conf->port);
	}
	
function query(&$sql,$verbose = false,$keep = false){
    // debug
//     echo "sql = ", $sql, ' ';
//     echo "len = ", strlen($sql), ' ';

    $start = microtime(1);
    if($verbose){
        echo "Executing $sql \n";
    }
    if(strlen($sql) < 2){
        $err = "$sql is too short SEPPUKU!\n";
        fwrite(STDERR, $err);
        file_put_contents("error.log",$err, FILE_APPEND |  FILE_APPEND );
        throw new Exception($err);
    }
    $res = $this->conn->query($sql);

    if($res === false || $this->conn->error){
        $err = "$sql is wrong, error is " . $this->conn->error . "\n";
        fwrite(STDERR, $err);
        file_put_contents("error.log",$err, FILE_APPEND |  FILE_APPEND );
        throw new Exception($err);
    }
    $this->lastId = $this->conn->insert_id;
    $time = microtime(1) - $start;
    if(defined('VERBOSE_SQL_TIME') && VERBOSE_SQL_TIME == true){
        echo "\n ---------- \n$sql\nin $time \n";
    }
    if(!$keep){
        $sql = '';
        unset($sql);
    }
    return $res;    
}

function getLine(&$sql){
    $res = $this->query($sql);
    $row = $res->fetch_object();
	return $row;
}


function getAll(&$sql, $resulttype=MYSQLI_ASSOC){
    $res = $this->query($sql);
    $rows = mysqli_fetch_all($res, $resulttype);
    
    return $rows;
}

function getLastId() {
	    return $this->lastId;
}
}
