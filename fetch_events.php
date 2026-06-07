<?php
require_once 'config/config.php';
header('Content-Type: application/json');

try {
  $database = new Database();
  $conn = $database->connect();

  $type = $_GET['type'] ?? '';
  $id = $_GET['id'] ?? '';
  $events = [];

  if ($type === 'room' && $id) {
    // Fetch reservations for rooms
    $stmt = $conn->prepare("SELECT 
      CONCAT('Room ', room_id) AS title,
      check_in AS start,  -- Full datetime for backend
      check_out AS end,   -- Full datetime for backend
      DATE(check_in) AS start_date, -- Extract only the date for display
      DATE(check_out) AS end_date  -- Extract only the date for display
      FROM reservations 
      WHERE room_id = :id AND status = 'Pending'");
    $stmt->execute(['id' => $id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($events)) {
      echo json_encode(['message' => 'No pending reservations found for this room.']);
      exit;
    }
  } elseif ($type === 'package' && $id) {
    // Fetch reservations for packages
    $stmt = $conn->prepare("SELECT 
      CONCAT('Package ', package_id) AS title,
      check_in AS start,  -- Full datetime for backend
      check_out AS end,   -- Full datetime for backend
      DATE(check_in) AS start_date, -- Extract only the date for display
      DATE(check_out) AS end_date  -- Extract only the date for display
      FROM package_reservation 
      WHERE package_id = :id AND status = 'pending'");
    $stmt->execute(['id' => $id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Return the events as JSON
  echo json_encode($events);
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
