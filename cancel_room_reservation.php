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

$stmt = $conn->prepare("SELECT r.id, r.booking_id, r.room_id, r.user_id, r.check_in, r.check_in_time, r.status, rm.is_available
  FROM reservations r
  JOIN rooms rm ON rm.id = r.room_id
  WHERE r.id = :id AND r.user_id = :user_id LIMIT 1");
$stmt->execute([
  ':id' => $reservation_id,
  ':user_id' => $user_id,
]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
  header('Location: my_reservations.php?room_cancel=notfound');
  exit();
}

$currentDate = new DateTime(date('Y-m-d'));
$checkInDate = DateTime::createFromFormat('Y-m-d', $reservation['check_in']);

if (!$checkInDate) {
  header('Location: my_reservations.php?room_cancel=invalid');
  exit();
}

if ($checkInDate < $currentDate) {
  header('Location: my_reservations.php?room_cancel=too_late');
  exit();
}

if (in_array(strtolower($reservation['status'] ?? ''), ['cancelled', 'canceled'], true)) {
  header('Location: my_reservations.php?room_cancel=already');
  exit();
}

try {
  $conn->beginTransaction();

  $updateReservation = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = :id AND user_id = :user_id");
  $updateReservation->execute([
    ':id' => $reservation_id,
    ':user_id' => $user_id,
  ]);

  $updateBooking = $conn->prepare("UPDATE bookings SET status = 'canceled' WHERE booking_id = :booking_id");
  $updateBooking->execute([
    ':booking_id' => $reservation['booking_id'],
  ]);

  $updatePayment = $conn->prepare("UPDATE payments SET payment_status = 'Cancelled' WHERE booking_id = :booking_id");
  $updatePayment->execute([
    ':booking_id' => $reservation['booking_id'],
  ]);

  $updateRoom = $conn->prepare("UPDATE rooms SET is_available = 1 WHERE id = :room_id");
  $updateRoom->execute([
    ':room_id' => $reservation['room_id'],
  ]);

  $conn->commit();
  header('Location: my_reservations.php?room_cancel=success');
  exit();
} catch (Exception $e) {
  if ($conn->inTransaction()) {
    $conn->rollBack();
  }
  header('Location: my_reservations.php?room_cancel=error');
  exit();
}
