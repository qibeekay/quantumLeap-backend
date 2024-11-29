<?php

require_once dirname(__DIR__) . '/assets/helpers/headers.inc.php';
require_once dirname(__DIR__) . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/assets/helpers/ErrorHandler.php';
require_once dirname(__DIR__) . '/assets/models/User.php';
require_once dirname(__DIR__) . '/assets/models/Payment.php';
require_once dirname(__DIR__) . '/assets/config/Database.php';

// Set up error handling
set_error_handler('ErrorHandler::handleError');
set_exception_handler('ErrorHandler::handleException');

// Load configuration
$configPath = dirname(__DIR__, 2) . '/src/assets/config/config.php'; // Adjusted path
if (!file_exists($configPath)) {
    die("Configuration file not found: " . $configPath);
}
$config = require $configPath;


// $config = require dirname(__DIR__, 2) . 'src/assets/config/config.php';

$db = new Database($config['database']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve raw POST data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true); // Decode JSON to an associative array

    // Validate the decoded data
    if (!isset($data['reference']) || !isset($data['amount']) || !isset($data['email'])) {
        echo json_encode(["status" => "error", "message" => "Missing required fields."]);
        exit();
    }

    $reference = $data['reference'];
    $amount = $data['amount'];
    $email = $data['email'];

    // Initialize User and Payment models
    $user = new User($db, '', $email, '', '');
    $payment = new Payment($db);

    // Get user details
    $userData = $user->getUserByEmail($email);

    if (!$userData) {
        echo json_encode(["status" => "error", "message" => "User not found."]);
        exit();
    }

    $payment->user_id = $userData['id'];
    $payment->amount = $amount;
    $payment->reference = $reference;

    // Verify payment with Paystack
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$reference}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer sk_test_9b861b4d3ef59992076278befaad55fb1a7831eb",
            "Cache-Control: no-cache",
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(["status" => "error", "message" => "Verification failed."]);
        exit();
    }

    $result = json_decode($response, true);

    if ($result['status'] && $result['data']['amount'] === $amount * 100) {
        // Record payment
        $payment->transaction_status = 'success';
        if (!$payment->recordPayment()) {
            echo json_encode(["status" => "error", "message" => "Failed to record payment."]);
            exit();
        }

        // Update user's payment status
        $user->updatePaymentStatus($userData['email']);

        echo json_encode(["status" => "success", "message" => "Payment verified and user status updated."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Payment verification failed."]);
    }
}
