<?php

use Ramsey\Uuid\Uuid;

class Utils
{
    public static function checkAuthorization($config)
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? null;
        $expectedToken = $config['services']['apitoken'];

        if ($authHeader) {
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $apiToken = $matches[1];

                if ($apiToken === $expectedToken) {
                    return true;
                } else {
                    http_response_code(403);
                    return self::returnData(false, "Invalid Token", null, true);
                }
            } else {
                http_response_code(400);
                return self::returnData(false, "Authorization header is not in the expected format.", null, true);
            }
        } else {
            http_response_code(401);
            return self::returnData(false, "No Authorization header found", null, true);
        }
    }

    public static function returnData($status = false, $message = null, $data = null, $exit = false)
    {
        if ($data == null) {
            $data = [];
        }
        $payload = array(
            'status' => $status,
            'message' => $message,
            'data' => $data
        );

        echo json_encode($payload);

        if ($exit) {
            exit;
        }
    }

    public static function generateCode()
    {
        // Generate a UUID
        return Uuid::uuid4()->toString();
    }

    public static function generateAlphanumericCode($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

}
