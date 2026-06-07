<?php
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help and Customer Support</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f5f6f8;
    }

    h1 {
      font-weight: 700;
    }

    section {
      padding: 20px 0;
    }

    h4 {
      font-weight: 600;
    }

    ol {
      margin-top: 0.5rem;
      padding-left: 1.5rem;
    }

    p {
      margin-top: 0.25rem;
      color: #555;
    }
  </style>
</head>

<body>
  <div class="container my-5 min-vh-100 p-0 bg-light rounded-4 py-5 px-5 shadow-sm help-container-dark">
    <h1 class="mb-3">Help and Customer Support</h1>

    <div class="section-group d-flex flex-column">
      <section>
        <h4>Creating an Account</h4>
        <p>Follow these steps to create your account:</p>
        <ol>
          <li>Go to the <a href="customer_register.php">Register</a> page.</li>
          <li>Fill in your full name, email, phone number, username, and password.</li>
          <li>Optionally upload a profile image.</li>
          <li>Click the <a href="customer_register.php">Create Account</a> button to finish.</li>
        </ol>
      </section>

      <section>
        <h4>Reserving a Room</h4>
        <p>To book a room:</p>
        <ol>
          <li>Login to your account.</li>
          <li>Go to the <strong>Room Booking</strong> section.</li>
          <li>Select your preferred room, dates, and number of guests.</li>
          <li>Review the price and click <strong>Reserve Now</strong>.</li>
        </ol>
      </section>

      <section>
        <h4>Reserving a Package Room</h4>
        <p>To reserve a package room:</p>
        <ol>
          <li>Login to your account.</li>
          <li>Navigate to the <strong>Packages</strong> page.</li>
          <li>Choose a package and review its details.</li>
          <li>Select your check-in and check-out dates.</li>
          <li>Click <strong>Book Package</strong> to confirm your reservation.</li>
        </ol>
      </section>

      <section>
        <h4>Editing Your Profile</h4>
        <p>To update your profile:</p>
        <ol>
          <li>Click on your profile image or navigate to the <strong>My Profile</strong> page.</li>
          <li>Click the <strong>Edit Profile</strong> button.</li>
          <li>Update your information like name, phone number, email, or profile image.</li>
          <li>Click <strong>Save Changes</strong> to apply updates.</li>
        </ol>
      </section>

      <section>
        <h4>Messaging Customer Service</h4>
        <p>If you need help or have a question:</p>
        <ol>
          <li>Go to the <strong>Contact Us</strong> or <strong>Support</strong> section.</li>
          <li>Fill in your name, email, subject, and your message.</li>
          <li>Click <strong>Send Message</strong> and wait for a reply via email.</li>
          <li>Live chat or other support options may also be available.</li>
        </ol>
      </section>

      <!-- Forgot Password and Change Password Section -->
      <section>
        <h4>Forgot Your Password?</h4>
        <p>If you’ve forgotten your password, follow these steps to reset it:</p>
        <ol>
          <li>Go to the <a href="forgot_password.php">Forgot Password</a> page.</li>
          <li>Enter your registered email address.</li>
          <li>You will receive a password reset link in your email inbox.</li>
          <li>Click the link and follow the instructions to reset your password.</li>
        </ol>
      </section>

      <section>
        <h4>Changing Your Password</h4>
        <p>If you wish to change your password:</p>
        <ol>
          <li>Login to your account and go to the <strong>My Profile</strong> page.</li>
          <li>Click on the <strong>Edit Profile</strong> button.</li>
          <li>Enter your current password and then your new password.</li>
          <li>Click <strong>Save Changes</strong> to update your password.</li>
        </ol>
      </section>
    </div>
  </div>

  <?php include 'footer.php'; ?>
</body>

</html>