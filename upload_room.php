<?php
require_once "config/config.php";
require_once "classes/Room.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Your form processing logic remains the same
  $room_name = $_POST['room_name'];
  $room_number = $_POST['room_number'];
  $room_type = $_POST['room_type'];
  $max_pax = max(1, (int)($_POST['max_pax'] ?? 2));
  $price = str_replace(',', '', $_POST['price']);
  $is_available = 1; // Default to available
  $promo_discount = $_POST['promo_discount'];
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];

  // Image Upload Handling
  $target_dir = "uploads/";
  if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true); // Create uploads directory if it doesn't exist
  }

  $image_name = basename($_FILES["room_image"]["name"]);
  $target_file = $target_dir . $image_name;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  // Allowed file types
  $allowed_types = ["jpg", "jpeg", "png", "gif"];
  if (!in_array($imageFileType, $allowed_types)) {
    echo json_encode(["error" => "Error: Only JPG, JPEG, PNG, & GIF files are allowed."]);
    exit();
  }

  // Move uploaded file
  if (move_uploaded_file($_FILES["room_image"]["tmp_name"], $target_file)) {
    $room = new Room();

    // Check if room number already exists before inserting
    if ($room->roomExists($room_number)) {
      echo json_encode(["error" => "Room number already exists!"]);
      exit();
    }

    // Insert room into the database
    $result = $room->createRoom($room_name, $room_number, $room_type, $max_pax, $price, $is_available, $target_file, $promo_discount, $start_date, $end_date);

    if ($result === true) {
      echo json_encode(["toast" => "Room added successfully!"]);
    } else {
      echo json_encode(["error" => "Error: Unable to add room."]);
    }
  } else {
    echo json_encode(["error" => "Error uploading file."]);
  }
}
