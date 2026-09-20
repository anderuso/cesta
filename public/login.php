<?php
require_once __DIR__.'/bootstrap.php';

use Dotenv\Dotenv;
use App\Auth;

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid credentials';
}

?><!DOCTYPE html>
<html>
<head>
    <title>Login – Cesta</title>
</head>
<body>
    <h1>Admin Login</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="login.php">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Login</button>
    </form>
    <p><a href="index.php">Back</a></p>
</body>
</html>
