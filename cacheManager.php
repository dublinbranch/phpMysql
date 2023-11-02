<?php

class Cache{
    //can be nullable else we can not even set it! congrats php
    static public ?DBWrapper $db = null;

    
    /**
     * @param string $key
     * @param int $maxAge if specified and bigger than 0 will avoid to return and old value
     * @return string
     */
    public static function get(string $key, int $maxAge = 0) : ?string {

        $w = "";
        if($maxAge > 0){
            $w = " AND createdAt > UNIX_TIMESTAMP() - $maxAge ";
        }

        $k64 = base64this($key);
        $sql = "SELECT value FROM cache WHERE `key` = $k64 $w";
        $row = Cache::$db->getLine($sql);
        if(empty($row)){
            return null;
        }
        return $row->value;
    }

    /**
     * @param string $key
     * @param string $value
     * @param $expire
     * @return void
     * @throws Exception in case of KEY DUPLICATION !
     */
    public static function insert(string $key, string $value, $expire = 3600 * 72){

        $k64 = base64this($key);
        $v64 = base64this($value);

        $sql = "INSERT INTO cache SET `key` = $k64, value = $v64, createdAt = UNIX_TIMESTAMP(), expireAt = UNIX_TIMESTAMP() + $expire";
        Cache::$db->query($sql);

    }

    public static function insertOrUpdate(string $key, string $value, $expire = 3600 * 72){

        $k64 = base64this($key);
        $v64 = base64this($value);

        $sql =<<<EOD
INSERT INTO cache SET 
`key` = $k64, 
value = $v64, 
createdAt = UNIX_TIMESTAMP(), 
expireAt = UNIX_TIMESTAMP() + $expire
ON DUPLICATE KEY UPDATE 
value = $v64, 
createdAt = UNIX_TIMESTAMP(), 
expireAt = UNIX_TIMESTAMP() + $expire
EOD;
        Cache::$db->query($sql);

    }




}
