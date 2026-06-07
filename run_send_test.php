<?php
// Simple helper to trigger a password reset POST and show response + SMTP debug tail.
// Usage: visit /run_send_test.php?email=you@example.com

header('Content-Type: application/json');

$email = $_GET['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Provide a valid email as ?email=you@example.com']);
  exit;
}

$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/TALAHOTEL/send_reset_link.php';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['email' => $email]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$resp = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$logPath = __DIR__ . '/mail_debug.log';
$logTail = null;
if (file_exists($logPath)) {
  $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines !== false) {
    $tail = array_slice($lines, -200);
    $logTail = implode("\n", $tail);
  }
}

echo json_encode([
  'curl_error' => $err ?: null,
  'send_reset_response' => $resp ?: null,
  'mail_debug_tail' => $logTail,
], JSON_PRETTY_PRINT);
