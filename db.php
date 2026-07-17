<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = mysqli_init();

$conn->ssl_set(
    null,
    null,
    __DIR__ . "/ca.pem",
    null,
    null
);

$conn->real_connect(
    getenv("DB_HOST"),
    getenv("DB_USER"),
    getenv("DB_PASS"),
    getenv("DB_NAME"),
    getenv("DB_PORT"),
    null,
    MYSQLI_CLIENT_SSL
);

$conn->set_charset("utf8mb4");