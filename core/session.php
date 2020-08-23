<?php

// Definitions
function dflt_upload_dir() {
    return '/var/www/html/uploads';
}

if (defined('NO_SESSION_START')) {
    return;
} 

// Core session
ob_start();
session_start();

//define('LOCAL_TESTING', true);

if (!defined('LOCAL_TESTING')) {
    if (!defined('BASEPATH')) define('BASEPATH', $_SERVER['DOCUMENT_ROOT']);
} else {
    if (!defined('BASEPATH')) define('BASEPATH', $_SERVER['DOCUMENT_ROOT'] . '/7cpanel');
}

error_reporting(E_ALL);
ini_set("display_errors", 0);

function isSSL()
{
    if (isset($_SERVER['HTTPS']))
    {
            if ($_SERVER['HTTPS'] == 1)
            {
                    return TRUE;
            }
            elseif ($_SERVER['HTTPS'] == 'on')
            {
                    return TRUE;
            }
    }

    return FALSE;
}

function is_valid_session() {
    
    if ($_SESSION['valid'] === "1" && $_SESSION['username'] && $_SESSION['username'] !== "") {
        return TRUE;
    }
    
    return FALSE;
}

function clear_session() {
    
    unset($_SESSION["username"]);
    unset($_SESSION["password"]);
    unset($_SESSION["user_role"]);
    unset($_SESSION["valid"]);
    unset($_SESSION["time"]);
}

if (!isSSL()) {    
    // Tole dela ok :    
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
}
