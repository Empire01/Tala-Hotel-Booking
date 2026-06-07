<?php
session_start();
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
  $userId = $_SESSION['user_id'];

  // Update last_seen to null or to the current time when the user logs out
  $database = new Database();
  $conn = $database->connect();

  // Update the last_seen field to NULL or timestamp
  $conn->prepare("UPDATE users SET last_seen = NULL WHERE id = ?")->execute([$userId]);

  // Destroy the session
  session_destroy();
  unset($_SESSION['user_id']);

  // Redirect to login page or homepage
  header("Location: customer_login.php");
  exit();
} else {
  // Handle cases where the user is not logged in
  header("Location: customer_login.php");
  exit();
}
