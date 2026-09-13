<?php
require_once __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Database;
use App\Auth;

$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$auth = new Auth();
echo '<!DOCTYPE html><html><head><title>Cesta</title></head><body>';
echo '<h1>Welcome to Cesta</h1>';

try {
    $database = new Database();
    $db = $database->getConnection();

        $statsQuery = $db->query('SELECT class_name, SUM(num_pages) AS total FROM books GROUP BY class_name');
        $stats = $statsQuery->fetchAll();
        if ($stats) {
            echo '<h2>Class Statistics</h2><ul>';
            foreach ($stats as $s) {
                echo '<li>' . htmlspecialchars($s['class_name']) . ': ' . htmlspecialchars($s['total']) . ' pages</li>';
            }
            echo '</ul>';
        }

        if ($auth->isGuest()) {
            echo '<p><a href="/login.php">Login</a></p>';
            echo '</body></html>';
        } else {
            echo '<p><a href="/admin/books.php">Books management</a></p>';
            echo '<p><a href="/logout.php">Logout</a></p>';
            echo '</body></html>';
        }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
}

