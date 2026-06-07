<?php
header('Content-Type: application/json');
require_once 'config/config.php';
require_once 'classes/Room.php';
require_once 'classes/PackageRoom.php';

// Parse JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['type']) || !isset($data['id'])) {
  echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request.'
  ]);
  exit;
}

$type = $data['type'];
$id = (int) $data['id'];

$database = new Database();
$conn = $database->connect();

if ($type === 'room') {
  $room = new Room($conn);
  $updated = $room->markAsAvailable($id);
  if ($updated) {
    echo json_encode(['status' => 'success', 'message' => 'Regular room checked out successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to checkout regular room.']);
  }
} elseif ($type === 'package') {
  $package = new PackageRoom($conn);
  $updated = $package->markAsAvailable($id);
  if ($updated) {
    echo json_encode(['status' => 'success', 'message' => 'Package room checked out successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to checkout package room.']);
  }
} else {
  echo json_encode(['status' => 'error', 'message' => 'Unknown room type.']);
}
