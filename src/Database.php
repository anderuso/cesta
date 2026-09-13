<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $database = $_ENV['DB_DATABASE'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];
        $socket = $_ENV['DB_SOCKET'];

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        //$dsn = "mysql:unix_socket={$socket};dbname={$database};charset=utf8mb4";

        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
        // Ensure books table exists
        $this->connection->exec("CREATE TABLE IF NOT EXISTS books (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, book_title TEXT, book_author TEXT, class_name TEXT, student_name TEXT, num_pages INTEGER)");
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
