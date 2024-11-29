<?php

require_once dirname(__DIR__) . '/../assets/models/User.php';
require_once dirname(__DIR__) . '/../assets/controllers/Utils.php';
require_once dirname(__DIR__) . '/../assets/controllers/EmailService.php';

class UserController
{
    private Database $db;
    private EmailService $emailService;

    public function __construct(Database $db, $apiUrl, $bearerToken)
    {
        $this->db = $db;
        $this->emailService = new EmailService($apiUrl, $bearerToken);
    }

    // Method to validate input data
    public function validateUserData($data)
    {
        $errors = [];
        $expectedFields = ['name', 'email', 'password', 'usertoken', 'cpassword'];

        if (empty($data->name))
            $errors[] = 'name is missing or empty';
        if (empty($data->email))
            $errors[] = 'email is missing or empty';
        if (empty($data->usertoken))
            $errors[] = 'usertoken is missing or empty';
        if (empty($data->password))
            $errors[] = 'password is missing or empty';
        if (empty($data->cpassword))
            $errors[] = 'confirm password is missing or empty';
        if (strlen($data->password) < 8) {
            $errors[] = 'Password cannot be less than 8 characters';
        }

        if (!preg_match('~[0-9]+~', $data->password)) {
            $errors[] = 'Password must contain at least a number';

        }

        if (!preg_match('/[\'^£$%&*()}{@#~?><>,!|=_+¬-]/', $data->password)) {
            $errors[] = 'Password must contain at least a character';

        }

        // Password match check
        if (!empty($data->password) && !empty($data->cpassword) && $data->password !== $data->cpassword) {
            $errors[] = 'password and confirm password do not match';
        }

        // Check for unexpected fields
        foreach ($data as $key => $value) {
            if (!in_array($key, $expectedFields)) {
                $errors[] = "unexpected field: $key";
            }
        }

        return $errors;
    }

    public function fetchAllUsers(): array
    {
        $userModel = new User($this->db, '', '', '', '', ); // Create an instance of the User model
        return $userModel->getAllUsers(); // Call the method to fetch all users
    }

    public function fetchUserByToken($usertoken)
    {
        // Validate that the usertoken is not empty
        if (empty($usertoken)) {
            return Utils::returnData(false, 'User token is required', null, true);
        }

        // Create an instance of the User model
        $userModel = new User($this->db, '', '', '', $usertoken);

        // Fetch user data by token
        $userData = $userModel->getUserByUsertoken($usertoken);

        if ($userData) {
            // User found, return the data
            return Utils::returnData(true, 'User found', $userData, false);
        } else {
            // User not found
            return Utils::returnData(false, 'User not found', null, true);
        }
    }



    // Method to register a user
    public function registerUser($data)
    {
        $user = new User($this->db, $data->name, $data->email, $data->password, $data->usertoken);

        // Check if email already exists
        if ($user->emailExists()) {
            return Utils::returnData(false, 'Email already in use', null, true);
        }

        // Generate verification code
        $verificationCode = Utils::generateAlphanumericCode();
        $user->verificationCode = $verificationCode; // Set the verification code

        // Send verification email first
        $emailResponse = $this->emailService->sendVerificationEmail($data->email, $verificationCode);

        if ($emailResponse['success']) {
            // Only register the user if the email was sent successfully
            if ($user->register()) {
                $newUserData = $user->getUserByUsertoken($data->usertoken);
                return Utils::returnData(true, 'Verification Email Sent', null, false);
            } else {
                // Handle any issues with user registration
                return Utils::returnData(false, 'User could not be created due to a database error', null, true);
            }
        }

        if (!$emailResponse['success']) {
            // Return email error if email could not be sent
            return Utils::returnData(false, 'User could not be created because verification email failed to send', $emailResponse, true);
        }
    }


    public function verifyEmail($data)
    {
        $user = new User($this->db, '', $data->email, '', ''); // Create user instance with email only
        if ($user->verifyUser($data->verificationCode)) {
            $newUserData = $user->getUserByEmail($data->email);

            // Remove password from response data
            unset($newUserData['password']);

            return Utils::returnData(true, 'Email Verification successful', $newUserData, false);
        } else {
            return Utils::returnData(false, 'Verification failed. Invalid code or email.', null, true);
        }
    }


    public function validateLoginData($data)
    {
        $errors = [];
        if (empty($data->email)) {
            $errors[] = 'Email is missing or empty';
        }
        if (empty($data->password)) {
            $errors[] = 'Password is missing or empty';
        }

        return $errors;
    }


    // Method to handle user login
    public function loginUser($data)
    {
        // Validate login data
        $errors = $this->validateLoginData($data);
        if (!empty($errors)) {
            return Utils::returnData(false, 'Validation errors', $errors, true);
        }

        // Create Login model instance
        $login = new User($this->db, '', $data->email, $data->password, '');
        $userData = $login->login($data->email, $data->password);

        if ($userData) {
            // Remove password from response data
            unset($userData['password']);

            // Successful login
            return Utils::returnData(true, 'Login successful', $userData, false);

        } else {
            // Failed login
            return Utils::returnData(false, 'Invalid email or password', null, true);
        }
    }

}
