<?php
// update_status.php
header('Content-Type: application/json');

require_once 'config/config.php';
require_once 'classes/PackageRoom.php';

$database = new Database();
$conn = $database->connect(); // Assuming this returns a PDO instance

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

  if ($id > 0) {
    try {
      $stmt = $conn->prepare("UPDATE packages SET room_status = 'available' WHERE id = :id");
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);

      if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Room status updated to Available.']);
      } else {
        echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
      }
    } catch (PDOException $e) {
      echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing ID.']);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
