<?php


require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';

require_once dirname(__DIR__) . '/../../vendor/autoload.php';
require_once dirname(__DIR__) . '/../assets/helpers/ErrorHandler.php';
require_once dirname(__DIR__) . '/../assets/controllers/Utils.php';
require_once dirname(__DIR__) . '/../assets/config/Database.php';
require_once dirname(__DIR__) . '/../assets/controllers/UserController.php';

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2) . '/assets/controllers');
$dotenv->load();

// Set up error handling
set_error_handler('ErrorHandler::handleError');
set_exception_handler('ErrorHandler::handleException');

$config = require dirname(__DIR__, 2) . '/assets/config/config.php';

$db = new Database($config['database']);

$apiUrl = $_ENV['EMAIL_API_URL'] . '/reni-mail/v1/sendSingleMail';
$bearerToken = $_ENV['EMAIL_BEARER_TOKEN'];

// $userController = new UserController($db);
$userController = new UserController($db, $apiUrl, $bearerToken);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json', true, 405); // Set the content type and status code
    echo json_encode([
        'status' => false,
        'message' => 'Method Not Allowed. Please use POST.'
    ]);
    exit; // Stop further execution
}

// Check authorization
if (Utils::checkAuthorization($config)) {
    // Decode the JSON data
    $data = json_decode(file_get_contents("php://input"));

    if ($data) {
        $data->usertoken = Utils::generateCode();

        // Validate user data using UserController
        $errors = $userController->validateUserData($data);

        if (empty($errors)) {
            // Send verification code
            $result = $userController->registerUser($data);
            // echo json_encode($result);
        } else {
            // Return validation errors
            // echo json_encode([
            //     'message' => 'Incomplete or Invalid Data',
            //     'errors' => $errors
            // ]);
            return Utils::returnData(false, $errors[0], null, true);
        }
    } else {
        echo json_encode(['message' => 'Invalid JSON data']);
    }
}
