<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $database = new Database();
    $db = $database->getConnection();

    $result = $db->query('SELECT VERSION() AS version')->fetch();

    echo 'Database connection successful!<br>';
    echo 'MariaDB version: ' . htmlspecialchars($result['version']);

} catch (Throwable $e) {
    http_response_code(500);

    echo 'Database error: ';
    echo htmlspecialchars($e->getMessage());
}

