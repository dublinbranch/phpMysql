<?php

function base64this($val)
{
    $val = is_null($val) ? "" : $val;
    $v = base64_encode($val);
    return " FROM_BASE64('$v') ";
}

function base64nullable($val)
{
    if (is_null($val) || strtolower($val) == 'null') {
        return 'NULL';
    }
    return base64this($val);
}

class Unlocker{
    public function __destruct()
    {
        global $db;
        $db->singleShotQuery("UNLOCK TABLE");
    }
    public function avoidOptimizeOut(){
        return time();
    }
}
class Committer{
    public function __destruct()
    {
        global $db;
        $db->singleShotQuery("COMMIT");
    }
    public function avoidOptimizeOut(){
        return time();
    }
}

