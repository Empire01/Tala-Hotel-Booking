<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
  <style>
    body {
      background: #f8f9fa;
      font-family: "Segoe UI", sans-serif;
    }

    .step {
      display: none;
      opacity: 0;
      transform: translateY(10px);
      transition: all 0.4s ease;
    }

    .step.active {
      display: block;
      opacity: 1;
      transform: translateY(0);
    }

    .progress-indicator {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 1rem;
    }

    .progress-indicator .circle {
      width: 35px;
      height: 35px;
      background-color: #dee2e6;
      color: #333;
      font-size: 14px;
      line-height: 35px;
      border-radius: 50%;
      text-align: center;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .progress-indicator .circle.active {
      background-color: #0d6efd;
      color: #fff;
    }

    .step-buttons {
      display: flex;
      justify-content: space-between;
      gap: 10px;
    }

    .hidden {
      display: none !important;
    }
  </style>
</head>

<body>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 bg-white shadow-sm rounded-4 p-5 login-form-dark">
        <form id="registerForm" class="d-flex gap-3 flex-column">
          <div class="text-center mb-3">
            <h5 class="fw-bold"><i class="bi bi-gem me-1"></i>Tala</h5>
            <h3>Create your account</h3>
            <small class="text-muted">Already have an account? <a href="customer_login.php">Sign in</a></small>
          </div>

          <!-- Progress -->
          <div class="progress-indicator">
            <div class="circle active" data-step="1">1</div>
            <div class="circle" data-step="2">2</div>
          </div>

          <!-- Step 1 -->
          <div class="step active" id="step-1">
            <input type="text" class="form-control rounded-3 py-2 required-step1 mb-3" autocomplete="off" name="fullname" placeholder="Full Name" required />
            <input type="text" class="form-control rounded-3 py-2 required-step1" autocomplete="off" name="phone" placeholder="Phone Number" required />
          </div>

          <!-- Step 2 -->
          <div class="step" id="step-2">
            <input type="email" class="form-control rounded-3 py-2 required-step2 mb-3" autocomplete="off" name="email" placeholder="Email Address" required />
            <input type="password" class="form-control rounded-3 py-2 required-step2 mb-3" autocomplete="off" name="password" placeholder="Password" required />
            <input type="password" class="form-control rounded-3 py-2 required-step2" autocomplete="off" name="confirm_password" placeholder="Confirm Password" required />
            <input type="hidden" name="role" value="customer" />
          </div>

          <div class="m-0 p-0">
            <!-- Buttons -->
            <div class="step-buttons">
              <button type="button" class="btn btn-primary w-100 rounded-3 hidden" id="nextBtn">Next</button>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-3 d-none" id="submitBtn">
              <small>Sign up</small>
            </button>

            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    let currentStep = 1;
    const steps = {
      1: document.getElementById('step-1'),
      2: document.getElementById('step-2')
    };
    const circles = document.querySelectorAll('.circle');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    function showStep(step) {
      Object.values(steps).forEach(s => s.classList.remove('active'));
      steps[step].classList.add('active');

      circles.forEach(c => c.classList.remove('active'));
      document.querySelector(`.circle[data-step="${step}"]`).classList.add('active');

      currentStep = step;

      if (step === 1) {
        nextBtn.classList.remove('hidden');
        submitBtn.classList.add('d-none');
        checkStepInputs(1);
      } else if (step === 2) {
        nextBtn.classList.add('hidden');
        submitBtn.classList.remove('d-none');
      }
    }

    function checkStepInputs(step) {
      const inputs = document.querySelectorAll(`.required-step${step}`);
      let allFilled = true;
      inputs.forEach(input => {
        if (!input.value.trim()) {
          allFilled = false;
        }
      });
      if (step === 1) {
        nextBtn.classList.toggle('hidden', !allFilled);
      }
    }

    // Make step numbers clickable
    circles.forEach(circle => {
      circle.addEventListener('click', () => {
        const step = parseInt(circle.getAttribute('data-step'));
        if (step <= currentStep) {
          showStep(step);
        }
      });
    });

    // Input validation to show next button
    document.querySelectorAll('.required-step1').forEach(input => {
      input.addEventListener('input', () => checkStepInputs(1));
    });

    // Next Button
    nextBtn.addEventListener('click', () => {
      showStep(2);
    });

    // Form Submission
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const form = this;
      const formData = new FormData(form);

      fetch('register.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          Toastify({
            text: data.message,
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: data.success ? "#28a745" : "red"
          }).showToast();

          if (data.success) {
            form.reset();
            setTimeout(() => {
              window.location.href = "customer_login.php";
            }, 3000);
          }
        })
        .catch(err => {
          console.error('Registration failed:', err);
          Toastify({
            text: "Something went wrong! " + err.message,
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "red"
          }).showToast();
        });
    });

    showStep(currentStep);
  </script>
</body>

<?php include 'footer.php'; ?>

</html>