<?php
require_once __DIR__.'/../../knihonauti/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../../knihonauti/');
$dotenv->load();
