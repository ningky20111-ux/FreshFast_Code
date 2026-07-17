<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "config.php";

$conn = mysqli_init();

$conn->ssl_set(
    null,
    null,
    "ca.pem",
    null,
    null
);

$conn->real_connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT,
    null,
    MYSQLI_CLIENT_SSL
);

$conn->set_charset("utf8mb4");