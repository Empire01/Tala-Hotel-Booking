<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'config/config.php';
require_once 'header.php';
require_once 'classes/Payment.php';

$database = new Database();
$conn = $database->connect();

$required_fields = ['package_id', 'guest_name', 'check_in', 'check_out', 'downpayment'];
foreach ($required_fields as $field) {
  if (empty($_POST[$field])) {
    echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
    exit();
  }
}

$package_id   = $_POST['package_id'];
$guest_name   = $_POST['guest_name'];
$check_in     = $_POST['check_in'];
$check_out    = $_POST['check_out'];
$check_in_time = $_POST['check_in_time'] ?? '14:00';
$check_out_time = $_POST['check_out_time'] ?? '12:00';
$guest_count   = max(1, (int)($_POST['guest_count'] ?? 1));
$downpayment  = $_POST['downpayment'];
$user_id      = $_POST['user_id'] ?? null;
$status       = 'pending';
$booking_id   = 'BOOK' . strtoupper(uniqid());
$amount_paid  = $downpayment;
$created_at   = date('Y-m-d H:i:s');

$check_in_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_in . ' ' . $check_in_time);
$check_out_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_out . ' ' . $check_out_time);

if (!$check_in_datetime || !$check_out_datetime || $check_out_datetime <= $check_in_datetime) {
  echo json_encode(['success' => false, 'message' => 'Check-out date and time must be later than check-in date and time.']);
  exit();
}

$conn->beginTransaction();

try {
  $sql = "INSERT INTO package_reservation 
      (booking_id, package_id, user_id, guest_name, check_in, check_out, check_in_time, check_out_time, guest_count, amount_paid, downpayment, status, created_at)
          VALUES 
      (:booking_id, :package_id, :user_id, :guest_name, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :amount_paid, :downpayment, :status, :created_at)";

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':booking_id', $booking_id);
  $stmt->bindParam(':package_id', $package_id);
  $stmt->bindParam(':user_id', $user_id);
  $stmt->bindParam(':guest_name', $guest_name);
  $stmt->bindParam(':check_in', $check_in);
  $stmt->bindParam(':check_out', $check_out);
  $stmt->bindParam(':check_in_time', $check_in_time);
  $stmt->bindParam(':check_out_time', $check_out_time);
  $stmt->bindParam(':guest_count', $guest_count, PDO::PARAM_INT);
  $stmt->bindParam(':amount_paid', $amount_paid);
  $stmt->bindParam(':downpayment', $downpayment);
  $stmt->bindParam(':status', $status);
  $stmt->bindParam(':created_at', $created_at);

  if ($stmt->execute()) {
    $payment = new Payment();
    $payment_success = $payment->makePackagePayment(
      $package_id,
      $user_id,
      $amount_paid,
      'Credit Card',
      'completed'
    );

    if ($payment_success) {
      $conn->commit();
      echo json_encode(['success' => true, 'message' => 'Reservation and payment successful!']);
    } else {
      $conn->rollBack();
      echo json_encode(['success' => false, 'message' => 'Payment failed. Please try again.']);
    }
  } else {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to create reservation.']);
  }
} catch (Exception $e) {
  $conn->rollBack();
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
