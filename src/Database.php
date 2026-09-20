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
        //$socket = $_ENV['DB_SOCKET'];

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
        // Verify books table structure
        $booksCols = $this->connection->query("SHOW COLUMNS FROM books")->fetchAll(PDO::FETCH_COLUMN);
        $expectedBooks = ['id','book_title','book_author','class_name','student_name','num_pages'];
        foreach ($expectedBooks as $col){
            if (!in_array($col, $booksCols)) {
                throw new Exception("Missing column '{$col}' in books table");
            }
        }

        // Verify destinations table structure
        $destCols = $this->connection->query("SHOW COLUMNS FROM destinations")->fetchAll(PDO::FETCH_COLUMN);
        $expectedDest = ['id','name','order','lat','lng'];
        foreach ($expectedDest as $col){
            if (!in_array($col, $destCols)) {
                throw new Exception("Missing column '{$col}' in destinations table");
            }
        }

    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
