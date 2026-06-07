<?php
require_once "config/config.php";

header('Content-Type: application/json');

$database = new Database();
$conn = $database->connect();

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $room_id = intval($_POST['id']);

  try {
    $conn->beginTransaction();

    $stmt1 = $conn->prepare("DELETE FROM reservations WHERE room_id = :room_id");
    $stmt1->bindParam(":room_id", $room_id, PDO::PARAM_INT);
    $stmt1->execute();

    $stmt2 = $conn->prepare("DELETE FROM rooms WHERE id = :room_id");
    $stmt2->bindParam(":room_id", $room_id, PDO::PARAM_INT);
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
