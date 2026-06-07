<?php
session_start();
header('Content-Type: application/json');

require_once 'config/config.php';
require_once 'classes/User.php';

$db = new Database();
$conn = $db->connect();
$user = new User($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $userData = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($userData && password_verify($password, $userData['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['fullname'] = $userData['fullname'];
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $userData['phone'];

    $updateSeen = $conn->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $updateSeen->execute([$userData['id']]);

    echo json_encode([
      'success' => true,
      'redirect' => $userData['role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'
    ]);
    exit;
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'Incorrect username or password.'
    ]);
    exit;
  }
}
