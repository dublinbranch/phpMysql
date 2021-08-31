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
        public int $connTimeout = 5;
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
        private ?DBConf $conf = null;

        public function __construct(?DBConf $conf = NULL)
        {
            if ($conf) {
                $this->setConf($conf);
            }
        }

        public function setConf(DBConf $conf)
        {
            if ($this->conf) {
                throw new Exception("fix your code, you are not supposed to recycle this class");
            }
            $this->conf = $conf;
        }


        public function getConn()
        {
            if (!$this->conn) {
                $mysqli = mysqli_init();
                if (!$mysqli) {
                    die('mysqli_init failed');
                }

                if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->conf->connTimeout)) {
                    die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
                }

                if(!$this->conf){
                    throw new Exception('DB wrapper has no config!');
                }
                $flag = 0;
                if ($this->conf->ssl) {
                    $flag |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT | MYSQLI_CLIENT_SSL;
                }

                if (!@$mysqli->real_connect($this->conf->host, $this->conf->user, $this->conf->passwd, $this->conf->db, $this->conf->port, NULL, $flag)) {
                    throw new Exception('Internal DB Error -.- please retry');
                }

                $this->conn = $mysqli;
                $this->getConn()->set_charset("utf8");
                $this->singleShotQuery('SET time_zone = "UTC";');
            }
            return $this->conn;
        }

        public function escape(string $sql): string
        {
            $res = $this->getConn()->real_escape_string($sql);
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

        public function querySS($sql, $verbose = false, $keep = false)
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
            $res = $this->getConn()->query($sql);

            if ($res === false || $this->getConn()->error) {
                $err = "$sql is wrong, error is " . $this->getConn()->error . "\n";
                if (defined("STDERR")) {
                    fwrite(STDERR, $err);
                }
                $date = new DateTime();
                $date = $date->format('Y-m-d H:i:s.u');
                file_put_contents(__DIR__ . "/error.log", $date . "\n" . $err, FILE_APPEND | LOCK_EX);
                throw new Exception($err);
            }
            $this->lastId = $this->getConn()->insert_id;
            $time = microtime(1) - $start;
            if (defined('VERBOSE_SQL_TIME') && VERBOSE_SQL_TIME == true) {
		                    $date = new DateTime();
                $date = $date->format('Y-m-d H:i:s.u');
		    file_put_contents(__DIR__ . "/timing.log", $date ."\n" . $sql . "\n" . $time . "\n***********************\n" , FILE_APPEND | LOCK_EX);
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
            $broken = $this->getConn()->affected_rows;
            return $broken;
        }

        public function toggleBinLog(int $status = 0): void
        {
            $this->querySS("SET SESSION sql_log_bin = $status");
        }
    }

    //This is usually used as a closure for register_shutdown_function
    function query(DBWrapper $db, $sql)
    {

        $db->query($sql);
    }
    /* Goodies

    $res->num_rows;


    */

}
