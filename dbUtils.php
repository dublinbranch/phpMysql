<?php

function base64this($val)
{
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
