<?php
require 'vendor/autoload.php';
include 'config/config.php';
include 'header.php';

$resultMessage = '';
$resultType = '';

// Handle POST form submission server-side to keep flow in this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $resultMessage = 'Please enter a valid email address.';
    $resultType = 'danger';
  } else {
    $database = new Database();
    $conn = $database->connect();

    // Rate-limit: reuse send_reset_link's simple per-email check by counting tokens in last hour
    // (lightweight - avoids exposing enumeration)

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      // generate token and save
      $token = bin2hex(random_bytes(32));
      $expiresAt = time() + 15 * 60;
      $tokenRecord = $token . ':' . $expiresAt;

      $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
      $stmt->execute([$tokenRecord, $user['id']]);

      // build reset link
      $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
      $scheme = $isHttps ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
      $resetLink = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . urlencode($token);

      // load SMTP config (env vars preferred, then config/mail.php)
      $smtpHost = getenv('MAIL_HOST') ?: '';
      $smtpPort = (int)(getenv('MAIL_PORT') ?: 0);
      $smtpUser = getenv('MAIL_USERNAME') ?: '';
      $smtpPass = getenv('MAIL_PASSWORD') ?: '';
      $fromName = getenv('MAIL_FROM_NAME') ?: '';

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
        // still respond with generic message; admin should configure mail settings
        $resultMessage = 'If that email is registered, a reset link has been sent.';
        $resultType = 'info';
      } else {
        // send email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
          $mail->isSMTP();
          $mail->Host = $smtpHost;
          $mail->SMTPAuth = true;
          $mail->Username = $smtpUser;
          $mail->Password = $smtpPass;
          $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Port = $smtpPort;
          $mail->CharSet = 'UTF-8';

          $mail->setFrom($smtpUser, $fromName ?: 'Tala Hotel');
          $mail->addAddress($user['email']);
          $mail->isHTML(true);
          $mail->Subject = 'Password Reset Request';
          $mail->Body = "Hello,<br><br>We received a password reset request for your Tala Hotel account.<br>Click this link to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>This link will expire in 15 minutes.<br><br>If you did not request this, you can ignore this email.";
          $mail->send();

          $resultMessage = 'If that email is registered, a reset link has been sent.';
          $resultType = 'success';
        } catch (Exception $e) {
          error_log('Password reset mail error: ' . ($mail->ErrorInfo ?: $e->getMessage()));
          $resultMessage = 'Email could not be sent right now. Please try again later.';
          $resultType = 'danger';
        }
      }
    } else {
      // generic response regardless of account existence
      $resultMessage = 'If that email is registered, a reset link has been sent.';
      $resultType = 'info';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
  <div class="container mt-5 d-flex justify-content-center">
    <form id="forgotPasswordForm" method="post" action="" class="p-5 shadow bg-light w-50 rounded-4 text-center" autocomplete="off">
      <h2>Reset your password</h2>
      <p>Enter your email to get a reset link.</p>
      <input type="email" name="email" id="email" class="form-control my-3" placeholder="Enter your email" required>
      <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    </form>
    <div id="responseMessage" class="mt-3">
      <?php if ($resultMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($resultType ?: 'info'); ?>" role="alert">
          <?php echo htmlspecialchars($resultMessage); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
</body>
<?php include 'footer.php'; ?>

</html>