<?php
require_once __DIR__ . '/dbUtils.php';

class DBConf
{
    public $host;
    public $user;
    public $passwd;
    public $db; //default database
    public $port = 3306;
}

require_once __DIR__ . "/../db-config.php";

class DBWrapper
{
    private $conn;
    private $lastId;

    public function __construct(DBConf $conf)
    {
        $this->conn = new mysqli($conf->host, $conf->user, $conf->passwd, $conf->db, $conf->port);
        $this->conn->set_charset("utf8");
    }

    public function getConn()
    {
        return $this->conn;
    }

    public function multiQuery(&$sql, $verbose = false, $keep = false)
    {
        throw new Exception("multiQuery is in some way broken, I (Roy) am not able to find a single working example with proper error reporting");
    }

    public function singleShotQuery($sql, $verbose = false, $keep = false)
    {
        return $this->query($sql, $verbose, $keep);
    }

    public function query(&$sql, $verbose = false, $keep = false)
    {
        // debug
//     echo "sql = ", $sql, ' ';
//     echo "len = ", strlen($sql), ' ';

        $start = microtime(1);
        if ($verbose || (defined("ECHO_SQL") && ECHO_SQL)) {
            echo "Executing $sql \n";
        }
        if (strlen($sql) < 2) {
		$err = "$sql is too short SEPPUKU!\n";
		if(defined(STDERR)){
			fwrite(STDERR, $err);
		}
            file_put_contents("error.log", $err, FILE_APPEND | FILE_APPEND);
            throw new Exception($err);
        }
        $res = $this->conn->query($sql);

        if ($res === false || $this->conn->error) {
            $err = "$sql is wrong, error is " . $this->conn->error . "\n";
	                    if(defined(STDERR)){
                        fwrite(STDERR, $err);
                }

	    file_put_contents("error.log", $err, FILE_APPEND | FILE_APPEND);
            throw new Exception($err);
        }
        $this->lastId = $this->conn->insert_id;
        $time = microtime(1) - $start;
        if (defined('VERBOSE_SQL_TIME') && VERBOSE_SQL_TIME == true) {
            echo "\n ---------- \n$sql\nin $time \n";
        }
        if (!$keep) {
            $sql = '';
            unset($sql);
        }
        return $res;
    }

    public function getLineSS($sql)
    {
        $res = $this->query($sql);
        $row = $res->fetch_object();
        return $row;
    }

    public function getLine(&$sql)
    {
        $res = $this->query($sql);
        $row = $res->fetch_object();
        return $row;
    }


    public function getAll(&$sql, $resulttype = MYSQLI_ASSOC)
    {
        $res = $this->query($sql);
        $rows = mysqli_fetch_all($res, $resulttype);
        return $rows;
    }

    public function getAllObj(&$sql)
    {
        $res = $this->query($sql);
        $arr = [];
        while ($row = $res->fetch_object()) {
            $arr[] = $row;
        }
        return $arr;
    }

    public function getLastId()
    {
        return $this->lastId;
    }

    /**
     * countrary to what documentation states, this return -1 for select
     * @return int
     */
    public function affectedRows(): int
    {
        //for some reason this need to be explicitly swapped, or will be optimized away
        $broken = $this->conn->affected_rows;
        return $broken;
    }
}

/* Goodies

$res->num_rows;


*/
