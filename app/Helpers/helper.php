<?php

function gv($params, $key, $default = null)
{
    return (isset($params[$key]) && $params[$key]) ? $params[$key] : $default;
}

function toDateTime($date)
{
    if (!$date) {
        return $date;
    }

    return date('Y-m-d H:i', strtotime($date));
}
