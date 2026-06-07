<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $userId = $_POST['user_id'];

  $database = new Database();
  $conn = $database->connect();

  $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'customer'");
  $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

  if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully!']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
  }
}
