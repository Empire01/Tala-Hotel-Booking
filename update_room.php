<?php
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

$response = ["success" => false, "message" => ""];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $room_id = $_POST['room_id'];
  $room_name = $_POST['room_name'];
  $max_pax = max(1, (int)($_POST['max_pax'] ?? 2));
  $price = str_replace(',', '', $_POST['price']);
  $room_type = $_POST['room_type'];
  $availability = $_POST['availability'];
  $image_path = $_POST['existing_image'];

  // Promo values
  $promo_discount = isset($_POST['promo_discount']) ? $_POST['promo_discount'] : 0;
  $discounted_price = str_replace(',', '', $_POST['discounted_price']);
  $start_date = $_POST['start_date'] ?? null;
  $end_date = $_POST['end_date'] ?? null;

  // Handle image upload if any
  if (!empty($_FILES['room_image']['name']) && $_FILES['room_image']['error'] === 0) {
    $upload_dir = 'uploads/';
    $image_name = $_FILES['room_image']['name'];
    $image_tmp_name = $_FILES['room_image']['tmp_name'];
    $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
    $new_image_name = uniqid() . '.' . $image_ext;

    if (move_uploaded_file($image_tmp_name, $upload_dir . $new_image_name)) {
      $image_path = $upload_dir . $new_image_name;
    } else {
      echo json_encode(["success" => false, "message" => "Image upload failed."]);
      exit();
    }
  }

  $stmt = $conn->prepare("
    UPDATE rooms
    SET 
      room_name = :room_name,
      max_pax = :max_pax,
      price = :price,
      room_type = :room_type,
      is_available = :availability,
      image_path = :image_path,
      promo_discount = :promo_discount,
      discounted_price = :discounted_price,
      start_date = :start_date,
      end_date = :end_date
    WHERE id = :room_id
  ");

  $stmt->bindParam(":room_name", $room_name);
  $stmt->bindParam(":max_pax", $max_pax, PDO::PARAM_INT);
  $stmt->bindParam(":price", $price);
  $stmt->bindParam(":room_type", $room_type);
  $stmt->bindParam(":availability", $availability);
  $stmt->bindParam(":image_path", $image_path);
  $stmt->bindParam(":promo_discount", $promo_discount);
  $stmt->bindParam(":discounted_price", $discounted_price);
  $stmt->bindParam(":start_date", $start_date);
  $stmt->bindParam(":end_date", $end_date);
  $stmt->bindParam(":room_id", $room_id);

  if ($stmt->execute()) {
    $response = ["success" => true, "message" => "Room updated successfully!"];
  } else {
    $response = ["success" => false, "message" => "Failed to update room."];
  }
}

header('Content-Type: application/json');
echo json_encode($response);
