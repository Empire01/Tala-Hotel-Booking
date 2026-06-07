<?php
include 'header.php';
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

$packageId = $_GET['id'] ?? null;
if (!$packageId) {
  echo "<script>alert('Package ID missing.'); window.location.href='view_package_rooms.php';</script>";
  exit();
}

$stmt = $conn->prepare("SELECT * FROM packages WHERE id = :id");
$stmt->bindParam(':id', $packageId);
$stmt->execute();
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
  echo "<script>alert('Package not found.'); window.location.href='view_package_rooms.php';</script>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Package Room</title>
  <script src="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/js/swiffy-slider.min.js" defer></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/css/swiffy-slider.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    .promo-section {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s ease, padding 0.4s ease;
    }

    .promo-section.show {
      max-height: 500px;
      padding-top: 15px;
    }

    .img-preview {
      max-width: 100%;
      max-height: 200px;
      border-radius: 0.5rem;
      object-fit: cover;
    }

    .toggle-btn {
      background: none;
      border: none;
      color: #0d6efd;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
    }

    .toggle-btn:hover {
      color: #0a58ca;
    }

    .toggle-icon {
      transition: transform 0.3s ease;
    }

    .promo-section.show+.toggle-icon {
      transform: rotate(180deg);
    }
  </style>
</head>

<body>
  <div class="container-fluid p-0 px-5 my-5">
    <div class="row justify-content-center">
      <div class="col-md-10">
        <form id="editPackageForm" method="post" action="update_package_room.php" enctype="multipart/form-data" class="card p-4 shadow-lg rounded-4">
          <h2 class="mb-4">Edit Package Room</h2>
          <input type="hidden" name="id" value="<?= $package['id']; ?>">

          <!-- IMAGE SLIDER SECTION -->
          <div class="row mb-3 align-items-center flex-column gap-3">
            <div class="col-md-4 text-center w-100 h-50">
              <div class="swiffy-slider slider-item-ratio slider-item-ratio-21x9 slider-nav-animation-fadein slider-indicators-square slider-indicators-dark slider-nav-dark mb-4">
                <ul class="slider-container" id="packageImageSlider">
                  <?php
                  $images = [
                    'package_image' => 'Main Package Image',
                    'attachment_1' => 'Attachment 1',
                    'attachment_2' => 'Attachment 2',
                    'attachment_3' => 'Attachment 3',
                  ];

                  foreach ($images as $key => $label):
                    if (!empty($package[$key])): ?>
                      <li>
                        <img src="<?= htmlspecialchars($package[$key]) ?>" alt="<?= $label ?>" class="w-100" style="object-fit: cover;">
                      </li>
                  <?php endif;
                  endforeach; ?>
                </ul>
                <button type="button" class="slider-nav"></button>
                <button type="button" class="slider-nav slider-nav-next"></button>
                <div class="slider-indicators"></div>
              </div>
            </div>

            <!-- IMAGE FILE UPLOADS FIXED WRAPPING -->
            <div class="row g-3 mb-4">
              <?php foreach ($images as $key => $label): ?>
                <div class="col-md-6">
                  <label class="form-label"><?= $label ?></label>
                  <input type="file" name="<?= $key ?>" class="form-control">
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- BASIC FIELDS -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="package_name" class="form-label">Package Name</label>
              <input type="text" name="package_name" class="form-control" value="<?= htmlspecialchars($package['package_name']); ?>" required>
            </div>

            <div class="col-md-6">
              <label for="included_rooms" class="form-label">Included Rooms</label>
              <input type="text" name="included_rooms" class="form-control" value="<?= htmlspecialchars($package['included_rooms']); ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="max_pax" class="form-label">Max Pax</label>
              <input type="number" name="max_pax" class="form-control" min="1" step="1" value="<?= htmlspecialchars($package['max_pax'] ?? 2); ?>" required>
              <small class="text-muted">Extra pax: ₱1,000 each</small>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="package_price" class="form-label">Package Price</label>
              <input type="text" name="package_price" class="form-control" value="<?= number_format($package['package_price'], 0, '.', ','); ?>" oninput="formatNumber(this)" required>
            </div>

            <div class="col-md-6">
              <label for="room_status" class="form-label">Room Status</label>
              <select name="room_status" class="form-select" required>
                <option value="available" <?= ($package['room_status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="unavailable" <?= ($package['room_status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                <option value="maintenance" <?= ($package['room_status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
              </select>
            </div>
          </div>

          <!-- Toggle Promo Fields -->
          <div class="mb-3">
            <button type="button" id="togglePromoBtn" class="btn btn-outline-primary btn-sm d-flex align-items-center">
              <i class="bi bi-box-arrow-down me-2 fs-5"></i>Toggle Promo Fields
            </button>
          </div>

          <!-- Promo Fields (Hidden by default, now appear below the button) -->
          <div class="promo-section mb-3">
            <div class="row g-3">
              <div class="col-md-3">
                <label for="promo_discount" class="form-label">Promo Discount (%)</label>
                <input type="number" name="promo_discount" class="form-control" value="<?= isset($package['package_discount']) && $package['package_discount'] !== null ? $package['package_discount'] : '10'; ?>" placeholder="e.g. 20" min="0" max="100" step="0.01">
              </div>
              <div class="col-md-3">
                <label for="discounted_price" class="form-label">Discounted Price</label>
                <input type="text" name="discounted_price" class="form-control" value="<?= isset($package['package_price']) ? number_format($package['package_price'], 0, '.', ',') : ''; ?>" placeholder="0.00" oninput="formatNumber(this)">
              </div>
              <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= !empty($package['package_start']) ? $package['package_start'] : date('Y-m-d'); ?>">
              </div>
              <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= !empty($package['package_end']) ? $package['package_end'] : '2026-12-25'; ?>">
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="d-flex justify-content-end mt-4">
            <a href="view_package_rooms.php" class="btn btn-dark me-2">Back</a>
            <button type="submit" class="btn btn-primary">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script>
    // Handle form submit with AJAX
    document.getElementById('editPackageForm').addEventListener('submit', function(e) {
      e.preventDefault(); // Stop default submit

      const form = e.target;
      const formData = new FormData(form);

      axios.post('update_package_room.php', formData)
        .then(function(response) {
          const res = response.data;

          if (res.status === 'success') {
            Toastify({
              text: res.message,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "green",
            }).showToast();

            // Optional: Redirect after 1.5s
            setTimeout(() => {
              window.location.href = "view_package_rooms.php";
            }, 1500);
          } else {
            Toastify({
              text: res.message,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "red",
            }).showToast();
          }
        })
        .catch(function(error) {
          Toastify({
            text: "An error occurred.",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
          }).showToast();
        });
    });

    document.getElementById("togglePromoBtn").addEventListener("click", function() {
      const promoSection = document.querySelector(".promo-section");
      promoSection.classList.toggle("show");
    });

    function formatNumber(input) {
      let value = input.value.replace(/,/g, '');
      if (!isNaN(value)) {
        input.value = parseFloat(value).toLocaleString('en-US');
      }
    }
  </script>
</body>

</html>