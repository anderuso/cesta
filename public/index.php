<?php
require_once __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Database;
use App\Auth;

$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$auth = new Auth();
if ($auth->isGuest()) {
    // Guest view: simple welcome page
    echo '<!DOCTYPE html><html><head><title>Cesta</title></head><body>';
    echo '<h1>Welcome to Cesta</h1>';
    echo '<p><a href="/login.php">Login as admin</a></p>';
    echo '</body></html>';
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $result = $db->query('SELECT VERSION() AS version')->fetch();
    echo '<!DOCTYPE html><html><head><title>Cesta Admin</title></head><body>';
    echo '<h1>Cesta – Admin Panel</h1>';
    echo '<p>MariDB version: ' . htmlspecialchars($result['version']) . '</p>';
    echo '<p><a href="/logout.php">Logout</a></p>';
    echo '</body></html>';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
}

