<?php
include 'header.php';
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$database = new Database();
$conn = $database->connect();

if (!isset($_GET['id']) || empty($_GET['id'])) {
  echo "<script>alert('Room ID is missing.'); window.location.href = 'view_room.php';</script>";
  exit();
}

$room_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = :room_id");
$stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
$stmt->execute();
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
  echo "<script>alert('Room not found.'); window.location.href = 'edit_room.php';</script>";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Room</title>
  <style>
    .promo-fields {
      overflow: hidden;
      max-height: 0;
      opacity: 0;
      transition: max-height 0.5s ease, opacity 0.5s ease;
    }

    .promo-fields.show {
      max-height: 500px;
      opacity: 1;
    }
  </style>
</head>

<body>
  <div class="container-fluid p-0 px-5 my-5">
    <div class="row justify-content-center">
      <div class="col-md-10">
        <form id="editRoomForm" enctype="multipart/form-data" class="card p-4 shadow-lg rounded-4">
          <h2 class="mb-4">Edit Room</h2>
          <input type="hidden" name="room_id" value="<?= $room['id']; ?>">

          <div class="row mb-3 align-items-center flex-column gap-3">
            <div class="col-md-4 text-center w-100">
              <img id="previewImage" src="<?= !empty($room['image_path']) ? $room['image_path'] : 'img/default.jpg'; ?>" alt="Room Image" class="img-fluid shadow" style="min-height: 15rem; object-fit: cover;">
            </div>
            <div class="col-md-8">
              <label for="formFile" class="form-label">Upload New Image</label>
              <input class="form-control" type="file" id="formFile" name="room_image" onchange="previewFile()">
              <input type="hidden" name="existing_image" value="<?= $room['image_path']; ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="room_name" class="form-label">Room Name</label>
              <input type="text" name="room_name" class="form-control" placeholder="Room Name" required value="<?= htmlspecialchars($room['room_name']); ?>">
            </div>
            <div class="col-md-6">
              <label for="max_pax" class="form-label">Max Pax</label>
              <input type="number" name="max_pax" class="form-control" id="max_pax" min="1" step="1" required value="<?= htmlspecialchars($room['max_pax'] ?? 2); ?>">
              <small class="text-muted">Extra pax: ₱1,000 each</small>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="price" class="form-label">Room Price</label>
              <input type="text" name="price" id="price" class="form-control" placeholder="Enter price" required value="<?= number_format((float)$room['price'], 0, '.', ','); ?>" oninput="formatNumber(this)" inputmode="numeric">
            </div>
            <div class="col-md-6">
              <label for="room_type" class="form-label">Room Type</label>
              <select name="room_type" class="form-select" required>
                <option value="Standard" <?= ($room['room_type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                <option value="Delux" <?= ($room['room_type'] == 'Delux') ? 'selected' : ''; ?>>Delux</option>
                <option value="Superior" <?= ($room['room_type'] == 'Superior') ? 'selected' : ''; ?>>Superior</option>
                <option value="King Room" <?= ($room['room_type'] == 'King Room') ? 'selected' : ''; ?>>King Room</option>
                <option value="Family" <?= ($room['room_type'] == 'Family') ? 'selected' : ''; ?>>Family</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="availability" class="form-label">Availability</label>
              <select name="availability" class="form-select">
                <option value="1" <?= ($room['is_available'] == 1) ? 'selected' : ''; ?>>Available</option>
                <option value="0" <?= ($room['is_available'] == 0) ? 'selected' : ''; ?>>Not Available</option>
                <option value="2" <?= ($room['is_available'] == 2) ? 'selected' : ''; ?>>Maintenance</option>
              </select>
            </div>
          </div>

          <!-- Toggle Promo Button -->
          <div class="mb-3">
            <button type="button" id="togglePromoBtn" class="btn btn-outline-primary btn-sm d-flex align-items-center">
              <i class="bi bi-box-arrow-down me-2 fs-5"></i>Toggle Promo Fields
            </button>
          </div>

          <!-- Promo Fields (Initially Hidden) -->
          <div id="promoFields" class="promo-fields mb-3">
            <div class="row g-3">
              <div class="col-md-3">
                <label for="promo_discount" class="form-label">Promo Discount (%)</label>
                <input type="number" name="promo_discount" class="form-control" value="<?= $room['promo_discount']; ?>" placeholder="e.g. 20">
              </div>
              <div class="col-md-3">
                <label for="discounted_price" class="form-label">Discounted Price</label>
                <input type="text" name="discounted_price" class="form-control" value="<?= number_format($room['discounted_price'], 0, '.', ','); ?>" placeholder="0.00" oninput="formatNumber(this)">
              </div>
              <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $room['start_date']; ?>">
              </div>
              <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $room['end_date']; ?>">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <a href="view_room.php" class="btn btn-dark me-2">Back</a>
            <button type="submit" class="btn btn-primary">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("togglePromoBtn").addEventListener("click", function() {
      const promoFields = document.getElementById("promoFields");
      promoFields.classList.toggle("show");
    });

    function formatNumber(input) {
      let value = input.value.replace(/,/g, '');
      if (!isNaN(value)) {
        input.value = parseFloat(value).toLocaleString('en-US');
      }
    }

    function previewFile() {
      const preview = document.getElementById("previewImage");
      const file = document.getElementById("formFile").files[0];
      const reader = new FileReader();
      reader.onloadend = function() {
        preview.src = reader.result;
      }
      if (file) {
        reader.readAsDataURL(file);
      }
    }

    // SweetAlert + Toastify on submit
    let originalValues = {
      name: "<?= htmlspecialchars($room['room_name']); ?>",
      maxPax: "<?= htmlspecialchars($room['max_pax'] ?? 2); ?>",
      price: "<?= number_format((float)$room['price'], 0, '.', ','); ?>",
      type: "<?= $room['room_type']; ?>",
      availability: "<?= $room['is_available']; ?>",
      image: "<?= $room['image_path']; ?>"
    };

    document.getElementById("editRoomForm").addEventListener("submit", function(e) {
      e.preventDefault();

      const currentName = document.querySelector("[name='room_name']").value.trim();
      const currentMaxPax = document.querySelector("[name='max_pax']").value.trim();
      const currentPrice = document.getElementById("price").value.trim();
      const currentType = document.querySelector("[name='room_type']").value;
      const currentAvailability = document.querySelector("[name='availability']").value;
      const newImage = document.getElementById("formFile").files[0];

      const isChanged = currentName !== originalValues.name ||
        currentMaxPax !== originalValues.maxPax ||
        currentPrice !== originalValues.price ||
        currentType !== originalValues.type ||
        currentAvailability !== originalValues.availability ||
        newImage;

      if (!isChanged) {
        Swal.fire({
          icon: 'info',
          title: 'No changes detected',
          text: 'You haven’t made any changes to update.',
          confirmButtonColor: '#0d6efd'
        });
        return;
      }

      let formData = new FormData(this);

      fetch("update_room.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          Toastify({
            text: data.message,
            duration: 3000,
            close: false,
            gravity: "top",
            position: "right",
            backgroundColor: data.success ? "#28a745" : "#dc3545"
          }).showToast();

          if (data.success) {
            setTimeout(() => {
              location.reload();
            }, 1500);
          }
        })
        .catch(err => {
          console.error(err);
          Toastify({
            text: "An error occurred while updating.",
            duration: 3000,
            close: false,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc3545"
          }).showToast();
        });
    });
  </script>
</body>

</html>