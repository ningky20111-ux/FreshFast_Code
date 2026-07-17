<?php
$conn = new mysqli("localhost", "root", "", "freshfast");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>