<?php
require_once "config/config.php";
require_once "classes/User.php";

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $fullname = $_POST['fullname'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $role = $_POST['role'] ?? 'customer';

  // Check if the passwords match
  if ($password !== $confirm_password) {
    $response['message'] = 'Passwords do not match!';
  } else {
    $user = new User();
    // Call the createUser method to insert into the database
    if ($user->createUser($fullname, $email, $phone, $password, $role)) {
      $response['success'] = true;
      $response['message'] = 'Registration successful!';
    } else {
      $response['message'] = 'Registration failed! Email might already exist.';
    }
  }
} else {
  $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit;
