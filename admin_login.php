<?php
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Toastify -->
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>

<body>
  <div class="container mt-5">
    <div class="row m-0 p-0 shadow bg-light overflow-hidden d-flex align-items-stretch login-form-dark">
      <div class="col-md-8 py-2">
        <img src="img/img1.jpg" alt="" class="img-fluid h-100">
      </div>
      <div class="py-5 col-md-4 px-5 d-flex justify-content-center align-items-center">
        <form id="loginForm" class="d-flex gap-3 flex-column w-100 py-5">
          <div class="form-group">
            <h5 class="fw-bold d-flex align-items-center mb-5">
              <i class="bi bi-gem me-1"></i>Tala
            </h5>
            <h3>Welcome back Admin</h3>
            <div class="m-0 p-0 d-flex align-items-center gap-1 justify-content-start">
            </div>
          </div>
          <div class="form-group">
            <small class="fw-bold">Email</small>
            <input type="email" name="email" id="email" class="mt-1 mb-2 form-control rounded-3 py-2" placeholder="Email Address" required autocomplete="off">
            <small class="fw-bold">Password</small>
            <input type="password" name="password" id="password" class="my-1 form-control rounded-3 py-2" placeholder="Password" required>
          </div>
          <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
          <div class="m-0 p-0">
            <button type="submit" class="btn btn-primary w-100 rounded-3">Sign in</button>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault(); 

      const form = this;
      const formData = new FormData(form);

      fetch('login.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            // Show toast
            Toastify({
              text: data.message,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "red",
              stopOnFocus: true
            }).showToast();

            // Reset inputs
            form.reset();
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    });
  </script>
</body>
<?php include 'footer.php'; ?>

</html>