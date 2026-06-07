<?php
// Safely inspect config/mail.php without revealing secrets.
header('Content-Type: application/json');

$path = __DIR__ . '/config/mail.php';
if (!file_exists($path)) {
  echo json_encode(['found' => false, 'message' => 'config/mail.php not found']);
  exit;
}

$cfg = include $path;
if (!is_array($cfg)) {
  echo json_encode(['found' => true, 'valid' => false, 'message' => 'config/mail.php did not return an array']);
  exit;
}

function mask($s) {
  if (!$s) return null;
  $len = strlen($s);
  if ($len <= 4) return str_repeat('*', $len);
  return substr($s,0,2) . str_repeat('*', max(1, $len-4)) . substr($s,-2);
}

$username = $cfg['username'] ?? '';
$password = $cfg['password'] ?? '';

echo json_encode([
  'found' => true,
  'username_masked' => mask($username),
  'username_length' => $username ? strlen($username) : 0,
  'password_set' => $password ? true : false,
  'password_length' => $password ? strlen($password) : 0,
], JSON_PRETTY_PRINT);
