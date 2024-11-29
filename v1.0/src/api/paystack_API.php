<?php

ob_clean();

require_once dirname(__DIR__) . '/assets/helpers/headers.inc.php';
require_once dirname(__DIR__) . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/assets/helpers/ErrorHandler.php';
require_once dirname(__DIR__) . '/assets/models/User.php';
require_once dirname(__DIR__) . '/assets/config/Database.php';


// Set up error handling
set_error_handler('ErrorHandler::handleError');
set_exception_handler('ErrorHandler::handleException');

// Load configuration
$configPath = dirname(__DIR__, 2) . '/src/assets/config/config.php'; // Adjusted path

if (!file_exists($configPath)) {
    die("Configuration file not found:{$configPath}");
}

$config = require $configPath;


// $config = require dirname(__DIR__, 2) . 'src/assets/config/config.php';

$db = new Database($config['database']);

$user = new User($db, '', $_POST['email'], '', '');

// Get user details
$userData = $user->getUserByEmail($_POST['email']);

// Check if user exists
if (!$userData) {
    echo json_encode(["status" => "error", "message" => "Unauthorized user cannot make payment."]);
    exit();
}


$url = "https://api.paystack.co/transaction/initialize";

$fields = [
    'email' => $_POST['email'],
    'amount' => $_POST['amount'] * 100,
    'firstname' => $_POST['firstname'],
    'lastname' => $_POST['lastname'],
    'callback_url' => 'https://0ffa6b44-95d6-4827-ad3e-ee6a49f08740-00-24x86j57bjlyu.kirk.replit.dev/verify'
];

$arr = [];

$fields_string = http_build_query($fields);

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer sk_test_9b861b4d3ef59992076278befaad55fb1a7831eb",
    "Cache-Control: no-cache",
]);

//So that curl_exec returns the contents of the cURL; rather than echoing it
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//execute post
$result = curl_exec($ch);
// echo $result;

$arr['status'] = 'success';
$arr['mess'] = $result;

echo json_encode($result);

exit();