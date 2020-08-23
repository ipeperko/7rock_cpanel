<?php

include_once 'core/session.php';
include_once 'Controller.php';

error_reporting(E_ALL);
ini_set("display_errors", 0);

$method = $_SERVER['REQUEST_METHOD'];

$ic = new Controller();  

if ($method == "GET" || $method == "POST") {
    $ic->processRequests();
    exit;
}

http_response_code(400);
