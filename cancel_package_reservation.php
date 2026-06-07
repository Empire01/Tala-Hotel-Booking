<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['reservation_id'])) {
  header('Location: my_reservations.php');
  exit();
}

$database = new Database();
$conn = $database->connect();

$reservation_id = (int)$_POST['reservation_id'];
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT pr.id, pr.package_id, pr.user_id, pr.check_in, pr.check_in_time, pr.status, p.room_status
  FROM package_reservation pr
  JOIN packages p ON p.id = pr.package_id
  WHERE pr.id = :id AND pr.user_id = :user_id LIMIT 1");
$stmt->execute([
  ':id' => $reservation_id,
  ':user_id' => $user_id,
]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
  header('Location: my_reservations.php?cancel=notfound');
  exit();
}

$currentDate = new DateTime(date('Y-m-d'));
$checkInDate = DateTime::createFromFormat('Y-m-d', $reservation['check_in']);

if (!$checkInDate) {
  header('Location: my_reservations.php?cancel=invalid');
  exit();
}

if ($checkInDate < $currentDate) {
  header('Location: my_reservations.php?cancel=too_late');
  exit();
}

try {
  $conn->beginTransaction();

  $updateReservation = $conn->prepare("UPDATE package_reservation SET status = 'Cancelled' WHERE id = :id AND user_id = :user_id");
  $updateReservation->execute([
    ':id' => $reservation_id,
    ':user_id' => $user_id,
  ]);

  $updatePackage = $conn->prepare("UPDATE packages SET room_status = 'available' WHERE id = :package_id");
  $updatePackage->execute([
    ':package_id' => $reservation['package_id'],
  ]);

  $conn->commit();
  header('Location: my_reservations.php?cancel=success');
  exit();
} catch (Exception $e) {
  if ($conn->inTransaction()) {
    $conn->rollBack();
  }
  header('Location: my_reservations.php?cancel=error');
  exit();
}
