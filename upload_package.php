<?php
// Include the Database class
include('config/config.php');

// Create a new instance of the Database class and establish the connection
$db = new Database();
$conn = $db->connect();

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get form data
  $package_name = $_POST['package_name'];
  $included_rooms = $_POST['included_rooms'];
  $max_pax = max(1, (int)($_POST['max_pax'] ?? 2));
  $package_price = $_POST['package_price'];
  $package_description = $_POST['package_description'];  // Get the package description

  // Format the price by removing '₱' and commas
  $package_price = str_replace(['₱', ','], '', $package_price);

  // Handle optional inputs with default values
  $package_discount = isset($_POST['package_discount']) && $_POST['package_discount'] !== '' ? $_POST['package_discount'] : 0;
  $package_start = !empty($_POST['package_start']) ? $_POST['package_start'] : null;
  $package_end = !empty($_POST['package_end']) ? $_POST['package_end'] : null;

  // Handle image upload (Package Image)
  $package_image = null;
  if (isset($_FILES['package_image'])) {
    $package_image = uploadFile($_FILES['package_image']);
  }

  // Handle attachments (Side View, Back View, Outside View)
  $attachment_1 = isset($_FILES['attachment_1']) ? uploadFile($_FILES['attachment_1']) : null;
  $attachment_2 = isset($_FILES['attachment_2']) ? uploadFile($_FILES['attachment_2']) : null;
  $attachment_3 = isset($_FILES['attachment_3']) ? uploadFile($_FILES['attachment_3']) : null;

  // Insert data into the database using a prepared statement
  $sql = "INSERT INTO packages 
      (package_name, included_rooms, max_pax, package_price, package_discount, package_start, package_end, package_image, attachment_1, attachment_2, attachment_3, package_description) 
          VALUES 
      (:package_name, :included_rooms, :max_pax, :package_price, :package_discount, :package_start, :package_end, :package_image, :attachment_1, :attachment_2, :attachment_3, :package_description)";

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':package_name', $package_name);
  $stmt->bindParam(':included_rooms', $included_rooms);
  $stmt->bindParam(':max_pax', $max_pax, PDO::PARAM_INT);
  $stmt->bindParam(':package_price', $package_price);
  $stmt->bindValue(':package_discount', $package_discount, PDO::PARAM_INT);
  $stmt->bindValue(':package_start', $package_start, $package_start === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
  $stmt->bindValue(':package_end', $package_end, $package_end === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
  $stmt->bindParam(':package_image', $package_image);
  $stmt->bindParam(':attachment_1', $attachment_1);
  $stmt->bindParam(':attachment_2', $attachment_2);
  $stmt->bindParam(':attachment_3', $attachment_3);
  $stmt->bindParam(':package_description', $package_description);  // Bind the package_description parameter

  if ($stmt->execute()) {
    echo json_encode(['toast' => 'Package room uploaded successfully!']);
  } else {
    echo json_encode(['error' => 'Error: Unable to upload package room.']);
  }
}

// Function to upload a file
function uploadFile($file)
{
  $target_dir = "uploads/"; // Define your target upload directory
  $target_file = $target_dir . basename($file["name"]);

  // File validation (you can customize this based on your needs)
  $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
  $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  // Check if the file type is allowed
  if (!in_array($file_extension, $allowed_extensions)) {
    return null;
  }

  // Check if the file was uploaded successfully
  if (move_uploaded_file($file["tmp_name"], $target_file)) {
    return $target_file;
  } else {
    return null;
  }
}
