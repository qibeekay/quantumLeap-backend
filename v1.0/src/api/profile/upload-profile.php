<?php

require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';

require_once dirname(__DIR__) . '/../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../assets/helpers/ErrorHandler.php';
require_once dirname(__DIR__) . '/../assets/controllers/Utils.php';
require_once dirname(__DIR__) . '/../assets/controllers/ProfileController.php';
require_once dirname(__DIR__) . '/../assets/config/Database.php';

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2) . '/assets/controllers');
$dotenv->load();

// Set up error handling
set_error_handler('ErrorHandler::handleError');
set_exception_handler('ErrorHandler::handleException');

$config = require dirname(__DIR__, 2) . '/assets/config/config.php';

$db = new Database($config['database']);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json', true, 405); // Set the content type and status code
    echo json_encode([
        'status' => false,
        'message' => 'Method Not Allowed. Please use POST.'
    ]);
    exit; // Stop further execution
}

if (Utils::checkAuthorization($config)) {
    // Decode the JSON data
    $data = json_decode(file_get_contents("php://input"), true);

    // echo json_encode($data, JSON_PRETTY_PRINT);
    $profileController = new ProfileController($db);
    $response = $profileController->createProfile($data);

    echo json_encode($response);
}