<?php

// require_once dirname(__DIR__) . '../../AllowCors.php';

// $cors = new AllowCors;

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     $cors->init();
//     header('HTTP/1.1 200 OK');
//     exit;
// }

// header('Access-Control-Allow-Origin: *');
// header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Stop further processing if it's a preflight request
    header('HTTP/1.1 200 OK');
    exit();
}
