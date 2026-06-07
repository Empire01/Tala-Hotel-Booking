<?php
require_once 'config/config.php';

$response = ['status' => 'error', 'message' => 'Something went wrong.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = $_POST['user_id'] ?? '';
  $image = $_FILES['profile_image'] ?? null;

  if ($userId && $image && $image['error'] === 0) {
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $newName = uniqid('profile_', true) . '.' . $ext;
    $uploadPath = 'profile/' . $newName;

    if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
      $database = new Database();
      $conn = $database->connect();

      $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
      if ($stmt->execute([$newName, $userId])) {
        $response = [
          'status' => 'success',
          'message' => 'Profile image updated successfully.',
          'new_image_path' => $uploadPath
        ];
      }
    }
  } else {
    $response['message'] = 'Invalid image or user.';
  }
}

echo json_encode($response);
