<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Payment.php';

header('Content-Type: application/json');

// Initialize database connection
$database = new Database();
$conn = $database->connect();

if (!$conn) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit();
}

// Required fields
$required_fields = ['package_room_id', 'guest_name', 'check_in', 'check_out', 'downpayment', 'user_id'];
foreach ($required_fields as $field) {
  if (empty($_POST[$field])) {
    echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
    exit();
  }
}

// Capture form data
$package_room_id = $_POST['package_room_id'];
$guest_name      = $_POST['guest_name'];
$check_in        = $_POST['check_in'];
$check_out       = $_POST['check_out'];
$check_in_time   = $_POST['check_in_time'] ?? '14:00';
$check_out_time  = $_POST['check_out_time'] ?? '12:00';
$guest_count     = max(1, (int)($_POST['guest_count'] ?? 1));
$user_id         = $_POST['user_id'];
$booking_id      = random_int(100000, 999999);
$status          = 'pending';
$created_at      = date('Y-m-d H:i:s');

$timezone = new DateTimeZone('Asia/Manila');
$check_in_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_in . ' ' . $check_in_time, $timezone);
$check_out_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_out . ' ' . $check_out_time, $timezone);
$now = new DateTime('now', $timezone);
$now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);

if (!$check_in_datetime || !$check_out_datetime || $check_out_datetime <= $check_in_datetime) {
  echo json_encode(['success' => false, 'message' => 'Check-out date and time must be later than check-in date and time.']);
  exit();
}

if ($check_in_datetime < $now) {
  echo json_encode(['success' => false, 'message' => 'Check-in time must be current time or later.']);
  exit();
}

$packageStmt = $conn->prepare("SELECT package_price, package_discount, package_end, max_pax FROM packages WHERE id = :id");
$packageStmt->bindParam(':id', $package_room_id, PDO::PARAM_INT);
$packageStmt->execute();
$packageData = $packageStmt->fetch(PDO::FETCH_ASSOC);

if (!$packageData) {
  echo json_encode(['success' => false, 'message' => 'Package not found.']);
  exit();
}

$nights = (int)$check_in_datetime->diff($check_out_datetime)->days;
$nightlyRate = (!empty($packageData['package_discount']) && !empty($packageData['package_end']) && strtotime($packageData['package_end']) >= strtotime(date('Y-m-d')))
  ? (float)$packageData['package_price'] - ((float)$packageData['package_price'] * ((float)$packageData['package_discount'] / 100))
  : (float)$packageData['package_price'];
$maxPax = max(1, (int)($packageData['max_pax'] ?? 2));
$extraGuests = max(0, $guest_count - $maxPax);
$extraFee = $extraGuests * 1000;
$totalPrice = ($nightlyRate * $nights) + $extraFee;
$downpayment = number_format($totalPrice * 0.3, 2, '.', '');
$amount_paid = $downpayment;

// Begin transaction
$conn->beginTransaction();

try {

  // Insert into package_reservation
  $sql = "INSERT INTO package_reservation 
      (booking_id, package_id, user_id, guest_name, check_in, check_out, check_in_time, check_out_time, guest_count, amount_paid, downpayment, status, created_at)
          VALUES 
      (:booking_id, :package_id, :user_id, :guest_name, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :amount_paid, :downpayment, :status, :created_at)";

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':booking_id', $booking_id);
  $stmt->bindParam(':package_id', $package_room_id);
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

  $reservationInserted = $stmt->execute();

  if (!$reservationInserted) {
    $conn->rollBack();
    $errorInfo = $stmt->errorInfo();
    error_log("Failed to insert reservation: " . print_r($errorInfo, true));
    echo json_encode(['success' => false, 'message' => 'Reservation insert failed: ' . $errorInfo[2]]);
    exit();
  }

  // Update room_status in packages table
  $updateSql = "UPDATE packages SET room_status = 'booked' WHERE id = :package_id";
  $updateStmt = $conn->prepare($updateSql);
  $updateStmt->bindParam(':package_id', $package_room_id);
  $roomStatusUpdated = $updateStmt->execute();

  if (!$roomStatusUpdated) {
    $conn->rollBack();
    $errorInfo = $updateStmt->errorInfo();
    error_log("Failed to update room_status: " . print_r($errorInfo, true));
    echo json_encode(['success' => false, 'message' => 'Failed to update room status: ' . $errorInfo[2]]);
    exit();
  }


  // Insert into payments table using your Payment class
  $payment = new Payment();
  $paymentSuccess = $payment->makePackagePayment(
    $package_room_id,
    $user_id,
    $amount_paid,
    'Credit Card',
    'completed'
  );

  if ($paymentSuccess) {
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation and payment successful!']);
  } else {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Payment failed. Transaction canceled.']);
  }
} catch (Exception $e) {
  $conn->rollBack();
  error_log("Exception during reservation: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
