<?php

class Payment
{
    private $connection;
    private $table = 'payments';

    // Payment properties
    public int $user_id;
    public int $amount;
    public string $reference;
    public string $transaction_status;

    // Constructor to initialize the database connection
    public function __construct($db)
    {
        $this->connection = $db;
    }

    // Method to record a payment
    public function recordPayment(): bool
    {
        $query = "INSERT INTO {$this->table} (user_id, amount, reference, transaction_status) 
                  VALUES (:user_id, :amount, :reference, :transaction_status)";

        $statement = $this->connection->query($query, [
            'user_id' => $this->user_id,
            'amount' => htmlspecialchars(strip_tags($this->amount)),
            'reference' => htmlspecialchars(strip_tags($this->reference)),
            'transaction_status' => htmlspecialchars(strip_tags($this->transaction_status))
        ]);

        return $statement ? true : false;
    }

    // Method to verify a payment by reference
    public function verifyPayment(string $reference): array|bool
    {
        $query = "SELECT * FROM {$this->table} WHERE reference = :reference LIMIT 1";

        $statement = $this->connection->query($query, ['reference' => $reference]);
        $result = $statement->find();

        return $result ?: false;
    }

    // Method to update transaction status
    public function updateTransactionStatus(string $reference, string $status): bool
    {
        $query = "UPDATE {$this->table} SET transaction_status = :transaction_status WHERE reference = :reference";

        $statement = $this->connection->query($query, [
            'transaction_status' => htmlspecialchars(strip_tags($status)),
            'reference' => htmlspecialchars(strip_tags($reference))
        ]);

        return $statement ? true : false;
    }
}
