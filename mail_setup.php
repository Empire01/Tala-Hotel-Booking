<?php
// Local Mail Setup (SAFE FOR LOCAL USE ONLY)
// This page allows configuring SMTP credentials and testing them by sending
// a test email. It writes config/mail.php on success. ACCESS: localhost only.

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: text/html; charset=utf-8');

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', 'localhost'])) {
  http_response_code(403);
  echo "<h3>Forbidden</h3><p>mail_setup is only available from localhost for security.</p>";
  exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $fromName = trim($_POST['from_name'] ?? 'Tala Hotel');
  $host = trim($_POST['host'] ?? 'smtp.gmail.com');
  $port = (int)($_POST['port'] ?? 587);
  $testRecipient = trim($_POST['test_recipient'] ?? '');

  if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $message = '<div class="alert alert-danger">Provide a valid sender email.</div>';
  } elseif ($password === '') {
    $message = '<div class="alert alert-danger">Provide the App Password.</div>';
  } elseif (!filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
    $message = '<div class="alert alert-danger">Provide a valid test recipient email.</div>';
  } else {
    // Try sending a test email
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = $host;
      $mail->SMTPAuth = true;
      $mail->Username = $username;
      $mail->Password = $password;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = $port;
      $mail->setFrom($username, $fromName);
      $mail->addAddress($testRecipient);
      $mail->Subject = 'Tala Hotel SMTP setup test';
      $mail->Body = 'This is a test message to verify SMTP credentials.';
      $mail->send();

      // Write config/mail.php
      $cfg = "<?php\nreturn [\n  'username' => '" . addslashes($username) . "',\n  'password' => '" . addslashes($password) . "',\n  'from_name' => '" . addslashes($fromName) . "',\n  'host' => '" . addslashes($host) . "',\n  'port' => " . intval($port) . ",\n];\n";
      $path = __DIR__ . '/config/mail.php';
      file_put_contents($path, $cfg);
      @chmod($path, 0600);

      $message = '<div class="alert alert-success">Credentials verified and saved to config/mail.php. Test email sent to ' . htmlspecialchars($testRecipient) . '.</div>';
    } catch (Exception $e) {
      $err = htmlspecialchars($mail->ErrorInfo ?: $e->getMessage());
      $message = '<div class="alert alert-danger">SMTP test failed: ' . $err . '</div>';
    }
  }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Mail Setup - Tala Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h3>Mail Setup (local only)</h3>
    <?php echo $message; ?>
    <form method="post" class="mt-3">
      <div class="mb-3">
        <label class="form-label">Sender Email (Gmail)</label>
        <input class="form-control" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">App Password</label>
        <input class="form-control" name="password" value="" autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label class="form-label">From Name</label>
        <input class="form-control" name="from_name" value="<?php echo htmlspecialchars($_POST['from_name'] ?? 'Tala Hotel'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">SMTP Host</label>
        <input class="form-control" name="host" value="<?php echo htmlspecialchars($_POST['host'] ?? 'smtp.gmail.com'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">SMTP Port</label>
        <input class="form-control" name="port" value="<?php echo htmlspecialchars($_POST['port'] ?? '587'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Test Recipient</label>
        <input class="form-control" name="test_recipient" value="<?php echo htmlspecialchars($_POST['test_recipient'] ?? ''); ?>">
        <div class="form-text">A test email will be sent to this address to verify credentials.</div>
      </div>
      <button class="btn btn-primary">Save & Test</button>
    </form>
  </div>
</body>
</html>
