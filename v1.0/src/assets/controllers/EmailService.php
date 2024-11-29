<?php

class EmailService
{
    private $apiUrl;
    private $bearerToken;

    public function __construct($apiUrl, $bearerToken)
    {
        $this->apiUrl = $apiUrl;
        $this->bearerToken = $bearerToken;
    }

    public function sendVerificationEmail($recipientEmail, $verificationCode)
    {
        // Email data payload
        $data = [
            'email' => $recipientEmail,
            'subject' => 'Hope Sport Verify your email',
            'body' => "Your verification code is: $verificationCode",
            "html" => true
        ];

        // Initialize cURL
        $ch = curl_init($this->apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->bearerToken
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute the request and get the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'message' => "cURL Error: $error"
            ];
        }

        // Close cURL session
        curl_close($ch);

        // Decode the response
        $responseData = json_decode($response, true);

        // Check the response
        if (isset($responseData['success']) && $responseData['success']) {
            return [
                'success' => true,
                'message' => 'Verification email sent successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => isset($responseData['message']) ? $responseData['message'] : 'Failed to send verification email.'
            ];
        }


    }
}
