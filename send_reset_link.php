<?php
require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'config/config.php';

$database = new Database();
$conn = $database->connect();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $tokenTtlSeconds = 15 * 60;

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
      'success' => false,
      'message' => 'Please enter a valid email address.'
    ]);
    exit();
  }

  // Check if user exists (case-insensitive so Gmail case differences still work)
  $stmt = $conn->prepare("SELECT id, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  // Basic rate-limiting to prevent abuse: per-email 3/hr, per-IP 10/hr.
  $storageDir = __DIR__ . '/storage';
  if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0700, true);
  }
  $limitsFile = $storageDir . '/forgot_limits.json';
  $limits = [];
  if (file_exists($limitsFile)) {
    $json = @file_get_contents($limitsFile);
    $limits = $json ? json_decode($json, true) : [];
    if (!is_array($limits)) $limits = [];
  }

  // helper to persist limits safely
  $saveLimits = function() use ($limitsFile, &$limits) {
    @file_put_contents($limitsFile, json_encode($limits));
  };

  // prune old entries (older than 1 hour)
  $now = time();
  $oneHour = 3600;
  foreach ($limits as $k => $arr) {
    $filtered = array_filter($arr, function($ts) use ($now, $oneHour) {
      return ($ts > $now - $oneHour);
    });
    if (empty($filtered)) unset($limits[$k]); else $limits[$k] = array_values($filtered);
  }

  $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $emailKey = 'email:' . $email;
  $ipKey = 'ip:' . $clientIp;

  $emailCount = isset($limits[$emailKey]) ? count($limits[$emailKey]) : 0;
  $ipCount = isset($limits[$ipKey]) ? count($limits[$ipKey]) : 0;

  if ($emailCount >= 3 || $ipCount >= 10) {
    // Don't reveal blocking to the user; respond normally but do not send email.
    echo json_encode([
      'success' => true,
      'message' => 'If that email is registered, a reset link has been sent.'
    ]);
    exit();
  }

  // record attempt tentatively (we'll keep it even if the email doesn't exist to avoid timing/user enumeration)
  $limits[$emailKey][] = $now;
  $limits[$ipKey][] = $now;
  $saveLimits();

  if ($user) {
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiresAt = time() + $tokenTtlSeconds;
    $tokenRecord = $token . ':' . $expiresAt;

    // Save token in database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
    $stmt->execute([$tokenRecord, $user['id']]);

    // Build reset link based on current host
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $resetLink = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . urlencode($token);

    // SMTP config: require environment variables for credentials to avoid hardcoded secrets.
    $smtpHost = getenv('MAIL_HOST') ?: '';
    $smtpPort = (int)(getenv('MAIL_PORT') ?: 0);
    $smtpUser = getenv('MAIL_USERNAME') ?: '';
    $smtpPass = getenv('MAIL_PASSWORD') ?: '';
    $fromName = getenv('MAIL_FROM_NAME') ?: '';

    // If environment variables are not set, allow a local config file (config/mail.php)
    // Admins should copy config/mail.example.php -> config/mail.php and set values there.
    if ((empty($smtpUser) || empty($smtpPass))) {
      $localConfigPath = __DIR__ . '/config/mail.php';
      if (file_exists($localConfigPath)) {
        $local = include $localConfigPath;
        if (is_array($local)) {
          $smtpUser = $smtpUser ?: ($local['username'] ?? '');
          $smtpPass = $smtpPass ?: ($local['password'] ?? '');
          $fromName = $fromName ?: ($local['from_name'] ?? 'Tala Hotel');
          $smtpHost = $smtpHost ?: ($local['host'] ?? 'smtp.gmail.com');
          $smtpPort = $smtpPort ?: ((int)($local['port'] ?? 587));
        }
      }
    }

    if (empty($smtpUser) || empty($smtpPass)) {
      error_log('SMTP credentials not configured for password reset emails. Set MAIL_USERNAME and MAIL_PASSWORD as environment variables or create config/mail.php from config/mail.example.php.');
      echo json_encode([
        'success' => false,
        'message' => 'Mail server not configured. Please set MAIL_USERNAME and MAIL_PASSWORD as environment variables or create config/mail.php from config/mail.example.php.'
      ]);
      exit();
    }

    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->Host = $smtpHost;
      $mail->SMTPAuth = true;
      $mail->Username = $smtpUser;
      $mail->Password = $smtpPass;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = $smtpPort;
      $mail->CharSet = 'UTF-8';

      // Enable debug output only if MAIL_DEBUG env var is set to '1'.
      $mailDebug = getenv('MAIL_DEBUG') === '1';
      $mail->SMTPDebug = $mailDebug ? 2 : 0;
      if ($mailDebug) {
        $mail->Debugoutput = function($str, $level) {
          file_put_contents(__DIR__ . '/mail_debug.log', date('[Y-m-d H:i:s] ') . $str . PHP_EOL, FILE_APPEND);
        };
      }

      // Gmail is strict with sender identity; use authenticated mailbox as From.
      $mail->setFrom($smtpUser, $fromName);
      $mail->addAddress($user['email']);

      $mail->isHTML(true);
      $mail->Subject = 'Password Reset Request';
      $mail->Body    = "Hello,<br><br>We received a password reset request for your Tala Hotel account.<br>Click this link to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>This link will expire in 15 minutes.<br><br>If you did not request this, you can ignore this email.";
      $mail->send();

      echo json_encode([
        'success' => true,
        'message' => 'Reset link sent to your email.'
      ]);
    } catch (Exception $e) {
      $err = $mail->ErrorInfo ?: $e->getMessage();
      error_log('Password reset mail error: ' . $err);
      // Also write the error to the debug log for easier access.
      if (getenv('MAIL_DEBUG') === '1') {
        file_put_contents(__DIR__ . '/mail_debug.log', date('[Y-m-d H:i:s] ') . 'ERROR: ' . $err . PHP_EOL, FILE_APPEND);
      }
      echo json_encode([
        'success' => false,
        'message' => 'Email could not be sent right now. Please try again later.',
        'debug' => $err
      ]);
    }
  } else {
    // Keep a safe message so user can still proceed without exposing account existence.
    echo json_encode([
      'success' => true,
      'message' => 'If that email is registered, a reset link has been sent.'
    ]);
  }
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid request method.'
  ]);
}
