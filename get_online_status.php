<?php
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

$query = $conn->query("SELECT id, last_seen FROM users WHERE role = 'customer'");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

function timeAgo($datetime)
{
  $timestamp = strtotime($datetime);
  $difference = time() - $timestamp;

  if ($difference < 60) {
    return 'Just now';
  } elseif ($difference < 3600) {
    $minutes = floor($difference / 60);
    return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
  } elseif ($difference < 86400) {
    $hours = floor($difference / 3600);
    return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
  } else {
    $days = floor($difference / 86400);
    return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
  }
}

$response = [];

foreach ($users as $user) {
  $status = 'Offline';
  if ($user['last_seen']) {
    if ((time() - strtotime($user['last_seen'])) <= 300) {
      $status = 'Online';
    } else {
      $status = 'Active ' . timeAgo($user['last_seen']);
    }
  }
  $response[] = [
    'id' => $user['id'],
    'status' => $status
  ];
}

header('Content-Type: application/json');
echo json_encode($response);
