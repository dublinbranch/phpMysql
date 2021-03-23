<?php
if (!function_exists("dummyDbWrapper")) {

    function dummyDbWrapper()
    {
    }

    require_once realpath(__DIR__ . '/dbUtils.php');

    class DBConf
    {
        public string $host;
        public string $user;
        public string $passwd;
        public ?string $db = null; //default database
        public int $port = 3306;
        public bool $ssl = false;
    }

    $oldDbConfigPath = __DIR__ . "/../db-config.php";
//include will still emit a warning if the file do not exists -.- why it even exists ?
    if (file_exists($oldDbConfigPath)) {
        require_once $oldDbConfigPath;
    }

    class DBWrapper
    {
        private ?mysqli $conn = null;
        private $lastId;

        public function __construct(?DBConf $conf = NULL)
        {
            if ($conf) {
                $this->setConf($conf);
            }
        }

        public function setConf(DBConf $conf)
        {
            if ($this->conn) {
                throw new Exception("fix your code, you are not supposed to recycle this class");
            }


            $mysqli = mysqli_init();
            if (!$mysqli) {
                die('mysqli_init failed');
            }

            if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
                die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
            }

            $flag = 0;
            if($conf->ssl){
                $flag |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT | MYSQLI_CLIENT_SSL;
            }

            if (!$mysqli->real_connect($conf->host, $conf->user, $conf->passwd, $conf->db, $conf->port, NULL, $flag)) {
                die('Connect Error (' . mysqli_connect_errno() . ') '
                    . mysqli_connect_error());
            }

            //echo 'Success... ' . $mysqli->host_info . "\n";

            $this->conn = $mysqli;
            $this->conn->set_charset("utf8");
            $this->singleShotQuery('SET time_zone = "UTC";');
        }

        public function getConn()
        {
            return $this->conn;
        }

        public function escape(string $sql): string
        {
            $res = $this->conn->real_escape_string($sql);
            return $res;
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
                if (defined("STDERR")) {
                    fwrite(STDERR, $err);
                }
		$date = new DateTime();
                $date = $date->format('Y-m-d H:i:s');
                file_put_contents(__DIR__ . "/error.log", $date . "\n" . $err, FILE_APPEND | LOCK_EX);
                throw new Exception($err);
            }
            $res = $this->conn->query($sql);

            if ($res === false || $this->conn->error) {
                $err = "$sql is wrong, error is " . $this->conn->error . "\n";
                if (defined("STDERR")) {
                    fwrite(STDERR, $err);
                }
		$date = new DateTime();
                $date = $date->format('Y-m-d H:i:s');
                file_put_contents(__DIR__ . "/error.log", $date . "\n" . $err, FILE_APPEND | LOCK_EX);
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

}
