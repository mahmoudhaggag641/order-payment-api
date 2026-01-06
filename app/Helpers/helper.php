<?php

function gv($params, $key, $default = null)
{
    return (isset($params[$key]) && $params[$key]) ? $params[$key] : $default;
}
