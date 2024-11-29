<?php

class User
{
    private $connection;

    private $table = 'users';

    // users properties
    public readonly string $name;
    public readonly string $email;
    public readonly string $password;
    public readonly string $usertoken;
    public string $verificationCode = '';
    public bool $is_verified;

    // constructor to accept database connection and user properties
    public function __construct($db, string $name, string $email, string $password, string $usertoken)
    {
        $this->connection = $db;
        $this->name = htmlspecialchars(strip_tags($name));
        $this->email = htmlspecialchars(strip_tags($email));
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->usertoken = htmlspecialchars(strip_tags($usertoken));
        $this->is_verified = false;
    }

    // Check if email already exists
    public function emailExists()
    {
        $query = "SELECT email FROM {$this->table} WHERE email = :email LIMIT 1";
        $statement = $this->connection->query($query, ['email' => $this->email]);
        return $statement->find() !== false; // Returns true if email exists
    }

    // register a user
    public function register()
    {
        // create query
        $query = "INSERT INTO {$this->table}(name, email, password, usertoken, verification_code, create_time) VALUES(:name, :email, :password, :usertoken, :verification_code, NOW())
            "
        ;

        // prepare the query
        $statement = $this->connection->query($query, [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'usertoken' => $this->usertoken,
            'verification_code' => $this->verificationCode
        ]);

        if ($statement) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $this->connection->statement->errorInfo()[2]); // Print the actual error message

        return false;
    }

    // Method to update verification status
    public function verifyUser($verificationCode)
    {
        // Fetch the stored verification code from the database
        $query = "SELECT verification_code FROM {$this->table} WHERE email = :email LIMIT 1";
        $statement = $this->connection->query($query, ['email' => $this->email]);
        $result = $statement->find();

        if ($result && $result['verification_code'] === $verificationCode) {
            // Update the user's verification status
            $updateQuery = "UPDATE {$this->table} SET is_verified = 1 WHERE email = :email";
            $updateStatement = $this->connection->query($updateQuery, ['email' => $this->email]);
            return $updateStatement; // Returns true if the update was successful
        }

        return false; // Verification failed
    }


    public function getUserByUsertoken($usertoken)
    {
        // Create query to fetch user data
        $query = "SELECT id, name, email, usertoken, is_verified, isPaid, create_time FROM {$this->table} WHERE usertoken = :usertoken LIMIT 1";

        // Prepare the statement
        $statement = $this->connection->query($query, ['usertoken' => $this->usertoken]);

        $userData = $statement->find();

        if (!$userData) {
            return false;
        }

        return $userData;

    }

    public function getUserByEmail($email)
    {
        // Create query to fetch user data
        $query = "SELECT id, name, email, usertoken, is_verified, isPaid, create_time FROM {$this->table} WHERE email = :email LIMIT 1";

        // Prepare the statement
        $statement = $this->connection->query($query, ['email' => $this->email]);

        $userData = $statement->find();

        if (!$userData) {
            return false;
        }

        return $userData;

    }

    // Method to authenticate a user
    public function login(string $email, string $password)
    {
        // Create query to fetch user data by email
        $query = "SELECT id, name, email, password , usertoken, is_verified FROM {$this->table} WHERE email = :email LIMIT 1";

        // Prepare the statement
        $statement = $this->connection->query($query, ['email' => htmlspecialchars(strip_tags($email))]);
        $userData = $statement->find();

        // Check if user exists and verify password
        if ($userData && password_verify($password, $userData['password'])) {
            return $userData; // Return user data if authentication is successful
        }

        return false; // Return false if authentication fails
    }

    public function updatePaymentStatus(string $email): bool
    {
        $query = "UPDATE {$this->table} SET isPaid = 1 WHERE email = :email";

        $statement = $this->connection->query($query, ['email' => htmlspecialchars(strip_tags($email))]);

        if (!$statement) {
            echo ("Error updating payment status: " . json_encode($this->connection->errorInfo()));
            return false;
        }

        return true;
    }

    // get user data
    public function getAllUsers(): array
    {
        $query = "SELECT id, name, email, usertoken, is_verified, create_time FROM {$this->table}";
        $statement = $this->connection->query($query);

        return $statement->getAll();
    }




}