<?php
// Diagnostic endpoint to check MAIL_* environment visibility to PHP/Apache.
header('Content-Type: application/json');

$user = getenv('MAIL_USERNAME');
$pass = getenv('MAIL_PASSWORD');

function mask_username($u) {
  if (!$u) return null;
  $len = strlen($u);
  if ($len <= 4) return str_repeat('*', $len);
  return substr($u,0,2) . str_repeat('*', max(1, $len-4)) . substr($u,-2);
}

$apacheEnv = null;
if (function_exists('apache_getenv')) {
  $apacheEnv = apache_getenv('MAIL_USERNAME') ?: null;
}

echo json_encode([
  'mail_username_masked' => mask_username($user),
  'mail_password_set' => $pass ? true : false,
  'apache_getenv_mail_username_masked' => mask_username($apacheEnv),
  'php_sapi' => PHP_SAPI,
]);
