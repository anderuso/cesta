<?php
require_once __DIR__.'/bootstrap.php';

use Dotenv\Dotenv;
use App\Auth;

$auth = new Auth();
$auth->logout();
header('Location: /');
exit;
