<?php
require_once 'config/config.php';
session_start();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current) {
  echo json_encode(['success' => false, 'message' => 'User not found']);
  exit();
}

$fullname = !empty($_POST['fullname']) ? ucwords(strtolower(trim($_POST['fullname']))) : $current['fullname'];
$email = !empty($_POST['email']) ? $_POST['email'] : $current['email'];
$dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : $current['date_of_birth'];
$present_address = !empty($_POST['present_address']) ? ucwords(strtolower(trim($_POST['present_address']))) : $current['present_address'];
$permanent_address = !empty($_POST['permanent_address']) ? ucwords(strtolower(trim($_POST['permanent_address']))) : $current['permanent_address'];
$city = !empty($_POST['city']) ? ucwords(strtolower(trim($_POST['city']))) : $current['city'];
$postal_code = !empty($_POST['postal_code']) ? $_POST['postal_code'] : $current['postal_code'];
$country = !empty($_POST['country']) ? ucwords(strtolower(trim($_POST['country']))) : $current['country'];
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if ($new_password !== '' || $confirm_password !== '') {
  if ($new_password === '' || $confirm_password === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in both password fields.']);
    exit();
  }

  if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
  }

  $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
} else {
  $hashed_password = $current['password'];
}

$new_img_name = $current['profile_image'];

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
  $img_name = $_FILES['profile_image']['name'];
  $tmp_name = $_FILES['profile_image']['tmp_name'];
  $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
  $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

  if (in_array($img_ext, $allowed_ext)) {
    $new_img_name = uniqid("IMG-", true) . '.' . $img_ext;
    if (!move_uploaded_file($tmp_name, "profile/" . $new_img_name)) {
      echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
      exit();
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid image format.']);
    exit();
  }
}

$update = "UPDATE users SET fullname=?, email=?, date_of_birth=?, present_address=?, permanent_address=?, city=?, postal_code=?, country=?, profile_image=?, password=? WHERE id=?";
$stmt = $conn->prepare($update);
$success = $stmt->execute([
  $fullname,
  $email,
  $dob,
  $present_address,
  $permanent_address,
  $city,
  $postal_code,
  $country,
  $new_img_name,
  $hashed_password,
  $user_id
]);

if ($success) {
  $response['success'] = true;
  $response['message'] = 'Profile updated successfully!';
} else {
  $response['message'] = 'Update failed: ' . implode(", ", $stmt->errorInfo());
}

echo json_encode($response);
exit();
