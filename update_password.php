<?php
include 'config/config.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the token, new password, and confirm password from POST data
  $token = trim($_POST['token'] ?? '');
  $newPassword = $_POST['new_password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';  // Added confirm password field

  if ($token === '') {
    echo json_encode([
      'success' => false,
      'message' => 'Invalid or expired token.'
    ]);
    exit;
  }

  // Check if the new password and confirm password match
  if ($newPassword !== $confirmPassword) {
    // Return error response if passwords don't match
    echo json_encode([
      'success' => false,
      'message' => 'Passwords do not match.'
    ]);
    exit;
  }

  // Hash the password if they match
  $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

  // Check if token exists (supports both legacy token and token:expiry format)
  $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? OR reset_token LIKE ? LIMIT 1");
  $stmt->execute([$token, $token . ':%']);
  $user = $stmt->fetch();

  if ($user) {
    $isTokenValid = true;
    $storedToken = (string)$user['reset_token'];
    if (strpos($storedToken, ':') !== false) {
      [$rawToken, $expiresAtRaw] = array_pad(explode(':', $storedToken, 2), 2, null);
      $expiresAt = (int)$expiresAtRaw;
      if (!hash_equals((string)$rawToken, (string)$token) || $expiresAt <= time()) {
        $isTokenValid = false;
      }
    } elseif (!hash_equals($storedToken, (string)$token)) {
      $isTokenValid = false;
    }

    if (!$isTokenValid) {
      $clearStmt = $conn->prepare("UPDATE users SET reset_token = NULL WHERE id = ?");
      $clearStmt->execute([$user['id']]);
      echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired token.'
      ]);
      exit;
    }

    // Update password and clear token
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->execute([$hashedPassword, $user['reset_token']]);

    // Return success response
    echo json_encode([
      'success' => true,
      'message' => 'Password updated successfully!'
    ]);
  } else {
    // Return failure response if token is invalid
    echo json_encode([
      'success' => false,
      'message' => 'Invalid or expired token.'
    ]);
  }
} else {
  // Return failure if not a POST request
  echo json_encode([
    'success' => false,
    'message' => 'Invalid request method.'
  ]);
}
