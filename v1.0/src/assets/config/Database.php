<?php

class Database
{
    private $connection;
    public $statement;

    public function __construct($config, $username = 'eehpknmj_chibuike', $password = 'k;*fO=uYuWaW')
    {
        $dsn = 'mysql:' . http_build_query($config, '', ';');

        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION  // Enable exception mode for errors
        ]);
    }

    public function query($query, $params = [])
    {
        $this->statement = $this->connection->prepare($query);

        $this->statement->execute($params);

        return $this;
    }

    // fetchall 
    public function getAll()
    {
        return $this->statement->fetchAll();
    }

    public function find()
    {
        return $this->statement->fetch();
    }

    // Fetch a single column
    public function fetchColumn()
    {
        return $this->statement->fetchColumn();
    }
}