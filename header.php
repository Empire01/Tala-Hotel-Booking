<?php
session_start();
require_once 'config/config.php';
require_once 'classes/User.php';
$currentPage = basename($_SERVER['PHP_SELF']);

$database = new Database();
$conn = $database->connect();
$user = new User();

if (isset($_SESSION['user_id'])) {
  $userId = $_SESSION['user_id'];
  $stmt = $conn->prepare("SELECT fullname, profile_image FROM users WHERE id = :id");
  $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
  $stmt->execute();
  $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <!-- Toastify CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

  <!-- Toastify JS -->
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

  <link rel="stylesheet" href="src/style.css">
  <link href="src/settings.css" rel="stylesheet">
  <script src="settings/settings.js" defer></script>
  <style>
    .navbar-nav .nav-link {
      position: relative;
      color: black;
      transition: color 0.3s ease;
    }

    .navbar-nav .nav-link::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: -2px;
      width: 0%;
      height: 3px;
      background-color: blue;
      transition: width 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
      color: blue !important;
    }

    .navbar-nav .nav-link:hover::after {
      width: 100%;
    }

    .navbar-nav .nav-link.active {
      color: blue !important;
    }

    .navbar-nav .nav-link.active::after {
      width: 100%;
    }

    .carousel-inner img {
      object-fit: cover;
      height: 400px;
    }

    .custom-input {
      color: red !important;
    }

    .custom-input:focus {
      color: #fff !important;
      background-color: red !important;
    }
  </style>
  <title>Tala Hotel Reservation</title>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary px-4 py-3 fixed-top shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand text-uppercase fw-bold" href="index.php">
        <i class="bi bi-gem me-3"></i>Tala</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav m-auto mb-2 mb-lg-0 gap-lg-4">
          <?php if (!isset($_SESSION['role']) || $_SESSION['role'] == 'customer'): ?>
            <li class="nav-item">
              <a class="nav-link fw-bold <?= ($currentPage == 'index.php') ? 'active' : '' ?>" href="index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link fw-bold <?= ($currentPage == 'rooms.php' || $currentPage == 'reserve.php') ? 'active' : '' ?>" href="rooms.php">Rooms</a>
            </li>
            <li class="nav-item">
              <a href="package_rooms.php" class="nav-link fw-bold <?= ($currentPage == 'package_rooms.php'  || $currentPage == 'reserve_package.php') ? 'active' : '' ?>">Packages</a>
            </li>
            <?php if (!isset($_SESSION['role'])): ?>
              <li class="nav-item">
                <a href="help.php" class="nav-link fw-bold <?= ($currentPage == 'help.php'  || $currentPage == 'help.php') ? 'active' : '' ?>">Help</a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
          <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] == 'admin'): ?>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'admin_dashboard.php') ? 'active' : '' ?>" href="admin_dashboard.php">Dashboard</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'view_room.php' || $currentPage == 'edit_room.php') ? 'active' : '' ?>" href="view_room.php">View Rooms</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'view_package_rooms.php' || $currentPage == 'edit_package_room.php') ? 'active' : '' ?>" href="view_package_rooms.php">Package Rooms</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'users_table.php') ? 'active' : '' ?>" href="users_table.php">Users</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'charts.php') ? 'active' : '' ?>" href="charts.php">Charts</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'calendar.php') ? 'active' : '' ?>" href="calendar.php">Calendar</a>
              </li>
              <li class="nav-item">

                <a class="nav-link fw-bold <?= ($currentPage == 'checkout.php') ? 'active' : '' ?>" href="checkout.php">Checkout</a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link fw-bold <?= ($currentPage == 'my_reservations.php') ? 'active' : '' ?>" href="my_reservations.php">My Reservations</a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>

        <?php if (isset($_SESSION['role'])): ?>
          <div class="dropdown d-flex align-items-center ms-2"> <!--fix the sizing-->
            <!-- User's Full Name -->
            <span class="mx-2 fw-bold" id="userFullName"><?= htmlspecialchars($userData['fullname']) ?></span>

            <!-- Profile Image with Dropdown -->
            <div class="position-relative" style="width: 40px; height: 40px;">
              <button class="btn p-0 border-0 rounded-circle overflow-hidden" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px;">
                <!-- Profile Image with Border -->
                <img src="profile/<?= htmlspecialchars(!empty($userData['profile_image']) ? $userData['profile_image'] : '60111.jpg') ?>"
                  alt="Profile Image"
                  class="img-fluid rounded-circle w-100 h-100 object-fit-cover border border-3 shadow border-success"
                  id="userProfileImage">

                <!-- Dropdown Icon -->
                <i class="bi bi-caret-down-fill text-white bg-dark"
                  style="position: absolute; bottom: 0; right: 0; width: 16px; height: 16px; font-size: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
                </i>
              </button>

              <!-- Dropdown Menu -->
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item fw-bold d-flex justify-content-between align-items-center" href="profile.php">Profile<i class="bi bi-person-check fs-5"></i></a></li>
                <li><a class="dropdown-item fw-bold d-flex justify-content-between align-items-center" href="settings.php">Settings<i class="bi bi-gear fs-5"></i></a></li>
                <?php
                if (isset($_SESSION['role'])) :
                ?>
                  <?php if ($_SESSION['role'] == 'customer'): ?>
                    <li><a class="dropdown-item fw-bold d-flex justify-content-between align-items-center" href="help.php">Help<i class="bi bi-question-circle fs-5"></i></a></li>
                  <?php endif; ?>
                <?php endif; ?>
                <li>
                  <a id="logoutBtn" class="dropdown-item fw-bold d-flex justify-content-between align-items-center custom-input" href="#">
                    Logout <i class="bi bi-box-arrow-in-right fs-5"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>

        <?php else: ?>
          <a href="customer_login.php" class="ms-2 btn btn-primary">
            Login <i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
  <?php
  if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') :
    if ($currentPage !== 'calendar.php' && $currentPage !== 'calendar.php') : ?>
      <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content rounded-4">
            <div class="modal-body text-center p-5">
              <h3 class="fw-bold">Rate us!</h3>
              <p class="text-muted">
                Your input is super important in helping us understand your needs better, so we can customize our services to suit you perfectly.
              </p>
              <h5 class="fw-bold">How would you rate our website?</h5>
              <div id="starRating" class="d-flex justify-content-center fs-3 text-warning gap-3 my-3">
                <i class="bi bi-star" data-value="1"></i>
                <i class="bi bi-star" data-value="2"></i>
                <i class="bi bi-star" data-value="3"></i>
                <i class="bi bi-star" data-value="4"></i>
                <i class="bi bi-star" data-value="5"></i>
              </div>
              <textarea class="form-control mb-3" placeholder="Add a comment" rows="3" style="resize: none;" name="users_comment"></textarea>
              <div class="m-0 p-0 d-flex gap-2">
                <button class="btn btn-outline-dark w-100 py-2" data-bs-dismiss="modal">Not now</button>
                <button id="submitRating" class="btn btn-primary w-100 py-2">Submit</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Loader with Progress and Percentage -->
  <div id="global-loader" style="position: fixed; z-index: 9999; background: rgba(255, 255, 255, 0.9); inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column;">
    <img src="loader/cleaning-cart-unscreen.gif" alt="Loading..." style="width: 85px; height: 85px; margin-bottom: 1rem;">
    <h5 class="mb-1">Tala Hotel</h5>

    <!-- Container for progress bar and percentage -->
    <div style="width: 30%; position: relative; display: flex; align-items: center;">
      <div style="width: 80%; position: relative; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
        <div id="progress-bar" style="width: 0%; height: 100%; background: #0d6efd; transition: width 0.3s;"></div>
      </div>
      <span id="progress-percent" class="text-danger" style="font-size: 14px; line-height: 8px; font-weight: 600; color: red; margin-left: 10px;">0%</span>
    </div>
  </div>

  <script src="toastify/toastify.js"></script>
  <!-- SweetAlert2 CSS & JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Toastify -->
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const loader = document.getElementById('global-loader');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');
    const minTime = 1500;
    const start = Date.now();

    let progress = 0;
    let interval = setInterval(() => {
      progress += Math.random() * 10;
      if (progress > 90) progress = 90;
      progressBar.style.width = `${progress}%`;
      progressPercent.innerText = `${Math.floor(progress)}%`;
    }, 150);

    window.addEventListener('load', function() {
      const elapsed = Date.now() - start;
      const remaining = Math.max(0, minTime - elapsed);

      setTimeout(() => {
        clearInterval(interval);
        progress = 100;
        progressBar.style.width = `100%`;
        progressPercent.innerText = `100%`;

        setTimeout(() => {
          loader.style.opacity = '0';
          loader.style.transition = 'opacity 0.4s ease';
          setTimeout(() => loader.style.display = 'none', 400);
        }, 300);
      }, remaining);
    });

    document.addEventListener("DOMContentLoaded", function() {
      let selectedRating = 0;
      let ratingModal;

      if (!sessionStorage.getItem('ratingModalShown')) {
        setTimeout(() => {
          ratingModal = new bootstrap.Modal(document.getElementById('exampleModal'));
          ratingModal.show();
          sessionStorage.setItem('ratingModalShown', 'true');
        }, 5000);
      }

      const stars = document.querySelectorAll('#starRating i');
      stars.forEach(star => {
        star.addEventListener('mouseover', () => {
          const val = parseInt(star.getAttribute('data-value'));
          stars.forEach(s => {
            const sVal = parseInt(s.getAttribute('data-value'));
            s.classList.remove('bi-star', 'bi-star-fill');
            if (sVal <= val) {
              s.classList.add('bi-star-fill', 'text-warning');
            } else {
              s.classList.add('bi-star', 'text-secondary');
            }
          });
        });

        star.addEventListener('click', () => {
          selectedRating = parseInt(star.getAttribute('data-value'));
          stars.forEach(s => {
            const sVal = parseInt(s.getAttribute('data-value'));
            s.classList.remove('bi-star', 'bi-star-fill');
            if (sVal <= selectedRating) {
              s.classList.add('bi-star-fill', 'text-warning');
            } else {
              s.classList.add('bi-star', 'text-secondary');
            }
          });
        });
      });

      document.getElementById('starRating').addEventListener('mouseleave', () => {
        if (selectedRating === 0) {
          stars.forEach(s => {
            s.classList.remove('bi-star-fill', 'text-warning', 'text-secondary');
            s.classList.add('bi-star');
          });
        }
      });

      document.getElementById('submitRating').addEventListener('click', function() {
        const comment = document.querySelector('textarea[name="users_comment"]').value.trim();

        if (selectedRating === 0) {
          Swal.fire('Oops!', 'Please select a rating before submitting.', 'warning');
          return;
        }

        fetch('save_rating.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `rating=${selectedRating}&users_comment=${encodeURIComponent(comment)}`
          })
          .then(res => res.text())
          .then(response => {
            if (response === 'success') {
              Swal.fire('Thank you!', 'We appreciate your feedback.', 'success');
              if (ratingModal) ratingModal.hide();
            } else {
              Swal.fire('Error', 'Something went wrong. Please try again later.', 'error');
            }
          });
      });
    });
  </script>
</body>

</html>