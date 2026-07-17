<?php
require_once __DIR__ . "/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
echo !empty($_ENV['GMAIL_APP_PASSWORD']) ? "OK" : "NOT FOUND";
