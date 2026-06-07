<?php
require_once "config/config.php";
require_once 'header.php';
require_once 'classes/Payment.php';

$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to book a room.'); window.location.href = 'rooms.php';</script>";
    exit();
  }

  // Retrieve logged-in user's ID
  $user_id = $_SESSION['user_id'];
  $room_id = $_POST['room_id'];
  $check_in = $_POST['check_in'];
  $check_out = $_POST['check_out'];
  $check_in_time = $_POST['check_in_time'] ?? '14:00';
  $check_out_time = $_POST['check_out_time'] ?? '12:00';
  $guest_count = max(1, (int)($_POST['guest_count'] ?? 1));
  $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : ''; // Ensure payment_method is captured

  $timezone = new DateTimeZone('Asia/Manila');
  $check_in_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_in . ' ' . $check_in_time, $timezone);
  $check_out_datetime = DateTime::createFromFormat('Y-m-d H:i', $check_out . ' ' . $check_out_time, $timezone);
  $now = new DateTime('now', $timezone);
  $now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);

  // Check if check-in is earlier than check-out
  if (!$check_in_datetime || !$check_out_datetime || $check_out_datetime <= $check_in_datetime) {
    echo "<script>alert('Check-out date and time must be later than check-in date and time.'); window.location.href = 'rooms.php';</script>";
    exit();
  }

  if ($check_in_datetime < $now) {
    echo "<script>alert('Check-in time must be current time or later.'); window.location.href = 'rooms.php';</script>";
    exit();
  }

  // Fetch user full name
  $stmtUser = $conn->prepare("SELECT fullname FROM users WHERE id = :user_id");
  $stmtUser->bindParam(":user_id", $user_id, PDO::PARAM_INT);
  $stmtUser->execute();
  $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo "<script>alert('User not found. Please log in again.'); window.location.href = 'index.php';</script>";
    exit();
  }

  $customer_name = $user['fullname'];

  // Fetch room details
  $stmt = $conn->prepare("SELECT price, discounted_price, promo_discount, end_date, max_pax, is_available FROM rooms WHERE id = :room_id");
  $stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
  $stmt->execute();
  $room = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$room) {
    echo "<script>alert('Room not found.'); window.location.href = 'rooms.php';</script>";
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
      $total_amount = ($nightly_rate * $days) + $extra_fee;
      $payment_amount = number_format($total_amount / 2, 2, '.', '');

      // Begin transaction
      $conn->beginTransaction();

      // Set the default status value
      $status = 'Pending';  // You can change this to 'Confirmed' or other values as per your requirements

      // Insert into bookings table
      $sql = "INSERT INTO bookings (room_id, customer_name, payment_amount, check_in, check_out, check_in_time, check_out_time, guest_count, status, booking_date, user_id) 
        VALUES (:room_id, :customer_name, :payment_amount, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :status, NOW(), :user_id)";
      $stmt2 = $conn->prepare($sql);
      $stmt2->bindParam(":room_id", $room_id, PDO::PARAM_INT);
      $stmt2->bindParam(":customer_name", $customer_name, PDO::PARAM_STR);
      $stmt2->bindParam(":payment_amount", $payment_amount, PDO::PARAM_STR);
      $stmt2->bindParam(":check_in", $check_in);
      $stmt2->bindParam(":check_out", $check_out);
      $stmt2->bindParam(":check_in_time", $check_in_time);
      $stmt2->bindParam(":check_out_time", $check_out_time);
      $stmt2->bindParam(":guest_count", $guest_count, PDO::PARAM_INT);
      $stmt2->bindParam(":status", $status, PDO::PARAM_STR);
      $stmt2->bindParam(":user_id", $user_id, PDO::PARAM_INT);
      $stmt2->execute();

      // Get last inserted booking ID
      $booking_id = $conn->lastInsertId();

      // Insert into payments table
      $payment_sql = "INSERT INTO payments (booking_id, user_id, amount, payment_status) 
            VALUES (:booking_id, :user_id, :amount_paid, 'Pending')";
      $stmt_payment = $conn->prepare($payment_sql);
      $stmt_payment->bindParam(":booking_id", $booking_id, PDO::PARAM_INT);
      $stmt_payment->bindParam(":user_id", $user_id, PDO::PARAM_INT);
      $stmt_payment->bindParam(":amount_paid", $payment_amount, PDO::PARAM_STR);
      $stmt_payment->execute();

      // Now insert the booking into the 'reservations' table
      $reservation_sql = "INSERT INTO reservations (user_id, room_id, guest_name, check_in, check_out, check_in_time, check_out_time, guest_count, status, booking_id, amount_paid, downpayment) 
        VALUES (:user_id, :room_id, :guest_name, :check_in, :check_out, :check_in_time, :check_out_time, :guest_count, :status, :booking_id, :amount_paid, :downpayment)";
      $stmt_reservation = $conn->prepare($reservation_sql);
      $stmt_reservation->bindParam(":user_id", $user_id, PDO::PARAM_INT);
      $stmt_reservation->bindParam(":room_id", $room_id, PDO::PARAM_INT);
      $stmt_reservation->bindParam(":guest_name", $customer_name, PDO::PARAM_STR);
      $stmt_reservation->bindParam(":check_in", $check_in);
      $stmt_reservation->bindParam(":check_out", $check_out);
      $stmt_reservation->bindParam(":check_in_time", $check_in_time);
      $stmt_reservation->bindParam(":check_out_time", $check_out_time);
      $stmt_reservation->bindParam(":guest_count", $guest_count, PDO::PARAM_INT);
      $stmt_reservation->bindParam(":status", $status);
      $stmt_reservation->bindParam(":booking_id", $booking_id, PDO::PARAM_INT);  // Use booking_id
      $stmt_reservation->bindParam(":amount_paid", $payment_amount, PDO::PARAM_STR);
      $stmt_reservation->bindParam(":downpayment", $payment_amount, PDO::PARAM_STR);
      $stmt_reservation->execute();

      // Mark room as unavailable
      $updateRoom = "UPDATE rooms SET is_available = 0 WHERE id = :room_id";
      $stmt3 = $conn->prepare($updateRoom);
      $stmt3->bindParam(":room_id", $room_id, PDO::PARAM_INT);
      $stmt3->execute();

      if ($stmt3->rowCount() > 0) {
        $conn->commit();
        echo "<script>alert('Booking confirmed! Payment recorded. Reservation added.'); window.location.href = 'rooms.php';</script>";
      } else {
        $conn->rollBack();
        echo "<script>alert('Failed to update room status. Room might already be booked or unavailable. Try again.');</script>";
      }
    } catch (Exception $e) {
      $conn->rollBack();
      echo "<script>alert('Error processing booking: " . addslashes($e->getMessage()) . "');</script>";
    }
  } else {
    echo "<script>alert('This room is already booked or unavailable.'); window.location.href = 'rooms.php';</script>";
  }
}
