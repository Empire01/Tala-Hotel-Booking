<?php
include 'config/config.php';
include 'header.php';

$database = new Database();
$conn = $database->connect();

if (isset($_GET['token'])) {
  $token = $_GET['token'];

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
      echo "Invalid or expired token.";
      exit();
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>Reset Password</title>
      <script src="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.js"></script>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>

    <body>
      <div class="container mt-5 d-flex justify-content-center">
        <form id="resetPasswordForm" class="p-5 shadow bg-light w-50 text-center rounded-4">
          <h4 class="fw-bolder"><i class="bi bi-gem me-2"></i>Tala Hotel</h4>
          <h2>Set New Password</h2>

          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

          <!-- New Password Input -->
          <input type="password" name="new_password" id="newPassword" class="form-control my-3" placeholder="New Password" required>

          <!-- Confirm Password Input -->
          <input type="password" name="confirm_password" id="confirmPassword" class="form-control my-3" placeholder="Confirm Password" required>
          <!-- Terms and Conditions Details -->
          <div class="terms-conditions text-start mb-3 mt-4">
            <h6>Terms and Conditions</h6>
            <p>
              By resetting your password, you agree to our Terms and Conditions. These conditions outline the rules and regulations for using our services.
            </p>
          </div>

          <!-- Terms and Conditions Section -->
          <div class="form-check text-start mb-4">
            <input type="checkbox" class="form-check-input" id="termsCheckbox" required>
            <label class="form-check-label" for="termsCheckbox">
              I agree to the Terms and Conditions and User Rights
            </label>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form>
      </div>

      <script>
        $('#resetPasswordForm').on('submit', function(e) {
          e.preventDefault();

          // Get the password values
          var newPassword = $('#newPassword').val();
          var confirmPassword = $('#confirmPassword').val();

          // Check if passwords match
          if (newPassword !== confirmPassword) {
            Toastify({
              text: 'Passwords do not match!',
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "red",
              stopOnFocus: true
            }).showToast();
            return; // Stop the form from submitting
          }

          var formData = $(this).serialize();

          $.ajax({
            url: 'update_password.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                Toastify({
                  text: response.message,
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "green",
                  stopOnFocus: true
                }).showToast();

                // Clear the form fields after success
                $('#newPassword').val('');
                $('#confirmPassword').val('');
              } else {
                Toastify({
                  text: response.message,
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "red",
                  stopOnFocus: true
                }).showToast();
              }
            },
            error: function(xhr, status, error) {
              Toastify({
                text: 'An error occurred. Please try again.',
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "red",
                stopOnFocus: true
              }).showToast();
            }
          });
        });
      </script>

      <?php include 'footer.php'; ?>
    </body>

    </html>
<?php
  } else {
    echo "Invalid or expired token.";
  }
} else {
  echo "No token provided.";
}
?>