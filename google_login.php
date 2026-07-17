<?php
session_start();

if (!isset($_POST['token'])) {
    exit("No token");
}

$token = $_POST['token'];

// ส่ง token ไปตรวจสอบกับ Google
$google_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$response = file_get_contents($google_url);

if ($response === FALSE) {
    exit("Token verification failed");
}

$data = json_decode($response, true);

// ดึงอีเมลจาก Google
if (isset($data['email'])) {
    $_SESSION['user_email'] = $data['email'];
    echo "success";
} else {
    echo "Invalid login";
}
