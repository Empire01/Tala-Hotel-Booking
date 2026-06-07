<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"));

  $title = $data->title ?? '';
  $description = $data->description ?? '';
  $start = $data->start ?? '';
  $end = $data->end ?? '';

  if (!$title || !$start) {
    echo json_encode(['error' => 'Required fields missing.']);
    exit;
  }

  $database = new Database();
  $conn = $database->connect();

  $stmt = $conn->prepare("INSERT INTO events (title, description, start_date, end_date) VALUES (?, ?, ?, ?)");
  $stmt->execute([$title, $description, $start, $end]);

  echo json_encode(['success' => true]);
}
