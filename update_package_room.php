<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
  exit;
}

$database = new Database();
$conn = $database->connect();

$id = $_POST['id'] ?? '';
$package_name = $_POST['package_name'] ?? '';
$included_rooms = $_POST['included_rooms'] ?? '';
$max_pax = max(1, (int)($_POST['max_pax'] ?? 2));
$package_price = str_replace(',', '', $_POST['package_price']) ?? '';
$promo_discount = $_POST['promo_discount'] ?? null;
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;
$room_status = $_POST['room_status'] ?? '';

if (empty($id) || empty($package_name) || empty($included_rooms) || empty($package_price) || empty($room_status)) {
  echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
  exit;
}

// Handle image uploads
$imageFields = ['package_image', 'attachment_1', 'attachment_2', 'attachment_3'];
$uploadedImages = [];

foreach ($imageFields as $field) {
  if (!empty($_FILES[$field]['name'])) {
    $targetDir = "uploads/"; // Make sure this folder exists and is writable
    $targetFile = $targetDir . time() . '_' . basename($_FILES[$field]["name"]);
    if (move_uploaded_file($_FILES[$field]["tmp_name"], $targetFile)) {
      $uploadedImages[$field] = $targetFile;
    }
  }
}

// Build update query
$sql = "UPDATE packages SET 
  package_name = :package_name,
  included_rooms = :included_rooms,
  max_pax = :max_pax,
  package_price = :package_price,
  package_discount = :package_discount,
  package_start = :package_start,
  package_end = :package_end,
  room_status = :room_status";

foreach ($uploadedImages as $field => $path) {
  $sql .= ", {$field} = :{$field}";
}

$sql .= " WHERE id = :id";

$stmt = $conn->prepare($sql);

$stmt->bindParam(':package_name', $package_name);
$stmt->bindParam(':included_rooms', $included_rooms);
$stmt->bindParam(':max_pax', $max_pax, PDO::PARAM_INT);
$stmt->bindParam(':package_price', $package_price);
$stmt->bindParam(':package_discount', $promo_discount);
$stmt->bindParam(':package_start', $start_date);
$stmt->bindParam(':package_end', $end_date);
$stmt->bindParam(':room_status', $room_status);
$stmt->bindParam(':id', $id);

foreach ($uploadedImages as $field => $path) {
  $stmt->bindValue(":$field", $path);
}

try {
  $stmt->execute();
  echo json_encode(['status' => 'success', 'message' => 'Package updated successfully!']);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
}
