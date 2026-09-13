<?php
require_once __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Auth;

$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$auth = new Auth();
$auth->logout();
header('Location: /');
exit;
