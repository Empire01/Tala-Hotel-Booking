<?php
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

$query = $conn->query("SELECT * FROM events");
$events = [];

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
  $events[] = [
    'id' => $row['id'],
    'title' => $row['title'],
    'description' => $row['description'],
    'start' => $row['start_date'],
    'end' => $row['end_date'],
  ];
}

echo json_encode($events);
