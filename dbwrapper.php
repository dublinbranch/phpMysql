<?php

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
        $this->conn= new mysqli($conf->host, $conf->user, $conf->passwd, $conf->db, $conf->port);
        $this->conn->set_charset("utf8");
    }

    public function multiQuery(&$sql, $verbose = false, $keep = false)
    {
        $start = microtime(1);
        if ($verbose) {
            echo "Executing $sql \n";
        }
        if (strlen($sql) < 2) {
            $err = "$sql is too short SEPPUKU!\n";
            fwrite(STDERR, $err);
            file_put_contents("error.log", $err, FILE_APPEND | FILE_APPEND);
            throw new Exception($err);
        }
        $res = $this->conn->multi_query($sql);

        if ($res === false || $this->conn->error) {
            $err = "$sql is wrong, error is " . $this->conn->error . "\n";
            fwrite(STDERR, $err);
            file_put_contents("error.log", $err, FILE_APPEND |  FILE_APPEND);
            throw new Exception($err);
        }
        /*
        do {
            if ($res = $this->conn->store_result()) {
                $res->free();
            }
        } while ($this->conn->more_results() && $this->conn->next_result());
        */
        
        
        $results = [];
    do {
        /* store first result set */
        if ($result = $this->conn->store_result()) {
            while ($row = $result->fetch_object()) {
                $results[] = $row;
            }
            $result->free();
        }
    } while ($this->conn->next_result());


        $time = microtime(1) - $start;
        if (defined('VERBOSE_SQL_TIME') && VERBOSE_SQL_TIME == true) {
            echo "\n ---------- \n$sql\nin $time \n";
        }
        if (!$keep) {
            $sql = '';
            unset($sql);
        }
        return $results;
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
        if ($verbose) {
            echo "Executing $sql \n";
        }
        if (strlen($sql) < 2) {
            $err = "$sql is too short SEPPUKU!\n";
            fwrite(STDERR, $err);
            file_put_contents("error.log", $err, FILE_APPEND | FILE_APPEND);
            throw new Exception($err);
        }
        $res = $this->conn->query($sql);

        if ($res === false || $this->conn->error) {
            $err = "$sql is wrong, error is " . $this->conn->error . "\n";
            fwrite(STDERR, $err);
            file_put_contents("error.log", $err, FILE_APPEND |  FILE_APPEND);
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

    public function getLastId()
    {
        return $this->lastId;
    }
}

/* Goodies

$res->num_rows;


*/
