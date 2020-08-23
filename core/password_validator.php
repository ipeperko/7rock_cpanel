<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$password_min_length = 5;

function validatePassword($pass) {
    
    global $password_min_length;
    
    $pattern = "/^[a-zA-Z\d!@#$%^&*.,;:]{{$password_min_length},}$/";
    $c1 = preg_match_all($pattern, $pass);

    if (!$c1) {
        return FALSE;
    }
    
    return TRUE;    
}

?>