<?php

function escape($value) {
    $value = str_replace("'", "\\'", $value);
    $value = str_replace('"', '\\"', $value);
    return $value;
}