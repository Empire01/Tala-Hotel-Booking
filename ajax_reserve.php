<?php
require_once "config/config.php";
require_once 'classes/Payment.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  session_start();
  if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "unauthorized", "message" => "You must be logged in to book a room."]);
    exit();
  }

  $user_id = $_SESSION['user_id'];
  $room_id = $_POST['room_id'];
  $check_in = $_POST['check_in'];
  $check_out = $_POST['check_out'];
  $check_in_time = $_POST['check_in_time'] ?? '14:00';
  $check_out_time = $_POST['check_out_time'] ?? '12:00';
  $guest_count = max(1, (int)($_POST['guest_count'] ?? 1));

  $timezone = new DateTimeZone('Asia/Manila');
  $check_in_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_in . ' ' . $check_in_time, $timezone);
  $check_out_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_out . ' ' . $check_out_time, $timezone);
  $now = new DateTime('now', $timezone);
  $now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);

  if (!$check_in_datetime || !$check_out_datetime || $check_out_datetime <= $check_in_datetime) {
    echo json_encode(["status" => "error", "message" => "Check-out date and time must be later than check-in date and time."]);
    exit();
  }

  if ($check_in_datetime < $now) {
    echo json_encode(["status" => "error", "message" => "Check-in time must be current time or later."]);
    exit();
  }

  $stmtUser = $conn->prepare("SELECT fullname FROM users WHERE id = :user_id");
  $stmtUser->bindParam(":user_id", $user_id, PDO::PARAM_INT);
  $stmtUser->execute();
  $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
  }

  $customer_name = $user['fullname'];

  $stmt = $conn->prepare("SELECT price, discounted_price, promo_discount, end_date, max_pax, is_available FROM rooms WHERE id = :room_id");
  $stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
  $stmt->execute();
  $room = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$room) {
    echo json_encode(["status" => "error", "message" => "Room not found."]);
    exit();
  }

  if ($room['is_available'] == 1) {
    try {
      $days = (int)$check_in_datetime->diff($check_out_datetime)->days;
      $nightly_rate = (!empty($room['promo_discount']) && strtotime($room['end_date']) > time() && !empty($room['discounted_price']))
        ? (float)$room['discounted_price']
        : (float)$room['price'];
      $extra_guests = max(0, $guest_count - max(1, (int)($room['max_pax'] ?? 2)));
      $extra_fee = $extra_guests * 1000;
      $payment_amount = number_format((($nightly_rate * $days) + $extra_fee) / 2, 2, '.', '');

      $conn->beginTransaction();
      $status = 'Pending';

            $sql = "INSERT INTO bookings (room_id, customer_name, payment_amount, check_in, check_out, check_in_time, check_out_time, guest_count, status, booking_date, user_id) 
              VALUES (:room_id, :customer_name, :payment_amount, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :status, NOW(), :user_id)";
      $stmt2 = $conn->prepare($sql);
      $stmt2->bindParam(":room_id", $room_id);
      $stmt2->bindParam(":customer_name", $customer_name);
      $stmt2->bindParam(":payment_amount", $payment_amount);
      $stmt2->bindParam(":check_in", $check_in);
      $stmt2->bindParam(":check_out", $check_out);
            $stmt2->bindParam(":check_in_time", $check_in_time);
            $stmt2->bindParam(":check_out_time", $check_out_time);
            $stmt2->bindParam(":guest_count", $guest_count, PDO::PARAM_INT);
      $stmt2->bindParam(":status", $status);
      $stmt2->bindParam(":user_id", $user_id);
      $stmt2->execute();

      $booking_id = $conn->lastInsertId();

      $payment_sql = "INSERT INTO payments (booking_id, user_id, amount, payment_status) 
              VALUES (:booking_id, :user_id, :amount_paid, 'Pending')";
      $stmt_payment = $conn->prepare($payment_sql);
      $stmt_payment->bindParam(":booking_id", $booking_id);
      $stmt_payment->bindParam(":user_id", $user_id);
      $stmt_payment->bindParam(":amount_paid", $payment_amount);
      $stmt_payment->execute();

            $reservation_sql = "INSERT INTO reservations (user_id, room_id, guest_name, check_in, check_out, check_in_time, check_out_time, guest_count, status, booking_id, amount_paid, downpayment) 
              VALUES (:user_id, :room_id, :guest_name, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :status, :booking_id, :amount_paid, :downpayment)";
      $stmt_reservation = $conn->prepare($reservation_sql);
      $stmt_reservation->bindParam(":user_id", $user_id);
      $stmt_reservation->bindParam(":room_id", $room_id);
      $stmt_reservation->bindParam(":guest_name", $customer_name);
      $stmt_reservation->bindParam(":check_in", $check_in);
      $stmt_reservation->bindParam(":check_out", $check_out);
            $stmt_reservation->bindParam(":check_in_time", $check_in_time);
            $stmt_reservation->bindParam(":check_out_time", $check_out_time);
            $stmt_reservation->bindParam(":guest_count", $guest_count, PDO::PARAM_INT);
      $stmt_reservation->bindParam(":status", $status);
      $stmt_reservation->bindParam(":booking_id", $booking_id);
      $stmt_reservation->bindParam(":amount_paid", $payment_amount);
      $stmt_reservation->bindParam(":downpayment", $payment_amount);
      $stmt_reservation->execute();

      $updateRoom = "UPDATE rooms SET is_available = 0 WHERE id = :room_id";
      $stmt3 = $conn->prepare($updateRoom);
      $stmt3->bindParam(":room_id", $room_id);
      $stmt3->execute();

      if ($stmt3->rowCount() > 0) {
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Booking confirmed!"]);
      } else {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => "Failed to update room status. Try again."]);
      }
    } catch (Exception $e) {
      $conn->rollBack();
      echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
  } else {
    echo json_encode(["status" => "error", "message" => "Room is already booked."]);
  }
}
