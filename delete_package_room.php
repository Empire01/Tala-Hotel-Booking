<?php
require_once 'config/config.php';
header('Content-Type: application/json');

$database = new Database();
$conn = $database->connect();

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $package_id = intval($_POST['id']); // Consistent variable name

  try {
    $conn->beginTransaction();

    // First delete from dependent table
    $stmt1 = $conn->prepare("DELETE FROM package_reservation WHERE package_id = :package_id");
    $stmt1->bindParam(":package_id", $package_id, PDO::PARAM_INT);
    $stmt1->execute();

    // Then delete from packages
    $stmt2 = $conn->prepare("DELETE FROM packages WHERE id = :package_id");
    $stmt2->bindParam(":package_id", $package_id, PDO::PARAM_INT);
    $stmt2->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Room deleted successfully.']);
  } catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error deleting room: ' . $e->getMessage()]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
