<?php
include 'header.php';
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

// Clear expired promos so reservation calculations use current pricing
require_once 'classes/PackageRoom.php';
$packageCleaner = new PackageRoom();
$packageCleaner->removeExpiredPromos();

$reserve_id = $_GET['package_id'] ?? null;
if (!$reserve_id) {
  die("Package ID is missing.");
}

// Fetch the package details
$sql = "SELECT package_price, package_discount, package_end, package_image, attachment_1, attachment_2, attachment_3, max_pax FROM packages WHERE id = :package_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":package_id", $reserve_id, PDO::PARAM_INT);
$stmt->execute();
$package = $stmt->fetch(PDO::FETCH_ASSOC);

$original_price = $package ? $package['package_price'] : 0;
$promo_price = $original_price;
$max_pax = max(1, (int)($package['max_pax'] ?? 2));

if (!empty($package['package_discount']) && strtotime($package['package_end']) > time()) {
  $promo_price = $original_price - ($original_price * ($package['package_discount'] / 100));
}

$formatted_price = number_format($original_price, 2, '.', ',');
$formatted_promo_price = number_format($promo_price, 2, '.', ',');
$extra_pax_fee = 1000;

$guest_name = $_SESSION['fullname'] ?? "Guest";
$user_id = $_SESSION['user_id'] ?? null;
?>

<!-- Swiffy Slider -->
<script src="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/js/swiffy-slider.min.js" crossorigin="anonymous" defer></script>
<link href="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/css/swiffy-slider.min.css" rel="stylesheet" crossorigin="anonymous">

<style>
  .info-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .info-group .form-group {
    flex: 1;
    min-width: 200px;
  }

  .reservation-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    background-color: #ffffff;
  }

  .form-label {
    font-weight: 600;
    color: #343a40;
  }
</style>

<div class="container mt-5 py-0 reservation-card ps-0">
  <div class="row reserve-dark-mode">
    <div class="col-md-6">
      <div class="swiffy-slider slider-item-ratio slider-item-ratio-16x9 slider-nav-animation slider-item-first-visible h-100">
        <ul class="rounded-start shadow slider-container h-100">
          <?php
          $images = [$package['package_image'], $package['attachment_1'], $package['attachment_2'], $package['attachment_3']];
          foreach ($images as $i => $img) {
            if (!empty($img)) {
              echo "<li><img src='" . htmlspecialchars($img) . "' alt='Image $i' class='w-100'></li>";
            }
          }
          ?>
        </ul>
        <button class="slider-nav" aria-label="Previous"></button>
        <button class="slider-nav slider-nav-next" aria-label="Next"></button>
        <div class="slider-indicators">
          <?php foreach ($images as $index => $img): ?>
            <?php if (!empty($img)): ?>
              <button class="<?= $index === 0 ? 'active' : '' ?>"></button>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6 p-5">
      <h2 class="mb-4">Reserve Your Package</h2>
      <form id="packageForm">
        <input type="hidden" name="package_room_id" value="<?= $reserve_id ?>">
        <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest_name) ?>">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
        <input type="hidden" id="original_price" value="<?= $formatted_price ?>">
        <input type="hidden" id="promo_price" value="<?= $formatted_promo_price ?>">
        <input type="hidden" id="max_pax" value="<?= $max_pax ?>">

        <div class="mb-3">
          <label class="form-label">Check-in Date</label>
          <input type="date" class="form-control" name="check_in" id="check_in" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Check-out Date</label>
          <input type="date" class="form-control" name="check_out" id="check_out" required>
        </div>
        <div class="info-group mb-3">
          <div class="form-group">
            <label class="form-label">Check-in Time</label>
            <input type="time" class="form-control" name="check_in_time" id="check_in_time" required>
          </div>
          <div class="form-group">
            <label class="form-label">Check-out Time</label>
            <input type="time" class="form-control" name="check_out_time" id="check_out_time" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Number of Guests</label>
          <input type="number" class="form-control" name="guest_count" id="guest_count" min="1" step="1" value="1" required>
          <small class="text-muted d-block mt-2">Max pax: <?= $max_pax ?> | Extra pax fee: ₱<?= number_format($extra_pax_fee, 0) ?> each</small>
        </div>
        <script>
  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  const today = new Date();
  const checkInEl = document.getElementById('check_in');
  const checkOutEl = document.getElementById('check_out');

  if (checkInEl) {
    checkInEl.min = formatDate(today);
  }

  if (checkOutEl) {
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    checkOutEl.min = formatDate(tomorrow);
  }

  if (document.getElementById('check_in_time')) document.getElementById('check_in_time').value = '';
  if (document.getElementById('check_out_time')) document.getElementById('check_out_time').value = '12:00';
</script>

        <div class="mb-3 info-group">
          <div class="form-group">
            <label class="form-label">Number of Nights</label>
            <p id="nights_display" class="form-control-plaintext fw-bold text-dark border border-1 rounded px-2">0</p>
          </div>

          <div class="form-group">
            <label class="form-label">Total Price (₱)</label>
            <input type="text" id="total_price_display" class="form-control" readonly>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Downpayment (₱)</label>
          <input type="text" name="downpayment" id="downpayment" class="form-control" readonly>
          <small class="d-block mt-2">Required: <span id="downpayment_display" class="fw-bold text-primary">₱0.00</span></small>
        </div>

        <?php if ($promo_price < $original_price): ?>
          <div class="alert alert-info reserve-room">
            <p class="mb-1">Promo Price per Night: <span class="fw-bold">₱<?= $formatted_promo_price ?></span></p>
            <p class="mb-0">Original Price per Night: <del>₱<?= $formatted_price ?></del></p>
          </div>
        <?php else: ?>
          <div class="alert alert-primary">
            <p class="mb-0">Price per Night: <span class="fw-bold">₱<?= $formatted_price ?></span></p>
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end gap-2">
          <a href="package_rooms.php" class="btn btn-outline-dark">← Back</a>
          <button type="submit" class="btn btn-primary px-4">Reserve</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
  function toUtcDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
  }

  function getCurrentLocalDateTimeParts() {
    const now = new Date();
    return {
      date: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`,
      time: `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
    };
  }

  document.getElementById("packageForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent the default form submission

    // Validate dates before proceeding
    if (!validateDates()) {
      return;
    }

    // Get form data
    const formData = new FormData(this);

    // Send the data to the server
    fetch('reserve_package_backend.php', {
        method: 'POST',
        body: formData // Send formData as body
      })
      .then(response => response.json()) // Parse the response as JSON
      .then(data => {
        if (data.success) {
          // Show success Toastify message
          Toastify({
            text: data.message,
            duration: 4000,
            gravity: "top",
            position: "right",
            backgroundColor: "#4CAF50",
          }).showToast();

          // Delay redirect by 2 seconds (2000ms) and add amount in the URL
          const downpayment = formData.get("downpayment");

          setTimeout(() => {
            window.location.href = `my_reservations.php?amount=${downpayment}`;
          }, 2000);
        } else {
          // Show error Toastify message
          Toastify({
            text: data.message,
            duration: 4000,
            gravity: "top",
            position: "right",
            backgroundColor: "#f44336",
          }).showToast();
        }
      })
      .catch(error => {
        console.error('Error:', error); // Log the error
        Toastify({
          text: 'An error occurred. Please try again later.',
          duration: 4000,
          gravity: "top",
          position: "right",
          backgroundColor: "#f44336",
        }).showToast();
      });
  });

  function validateDates() {
    const checkInValue = document.getElementById('check_in').value;
    const checkOutValue = document.getElementById('check_out').value;
    const checkInTimeValue = document.getElementById('check_in_time').value;

    if (!checkInValue || !checkOutValue || toUtcDate(checkInValue) >= toUtcDate(checkOutValue)) {
      Toastify({
        text: "Check-out date and time must be later than check-in date and time.",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#f44336",
      }).showToast();
      return false;
    }

    const current = getCurrentLocalDateTimeParts();
    if (checkInValue === current.date && checkInTimeValue && checkInTimeValue < current.time) {
      Toastify({
        text: "Check-in time must be current time or later.",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#f44336",
      }).showToast();
      return false;
    }
    return true;
  }


  // Additional JS for calculating total price and nights
  document.getElementById('check_in').addEventListener('change', function() {
    calculateTotalPrice();
    syncCheckInTimeMin();
  });
  document.getElementById('check_out').addEventListener('change', calculateTotalPrice);
  document.getElementById('check_in_time').addEventListener('change', calculateTotalPrice);
  document.getElementById('check_out_time').addEventListener('change', calculateTotalPrice);
  document.getElementById('guest_count').addEventListener('change', calculateTotalPrice);

  function syncCheckInTimeMin() {
    const checkInInput = document.getElementById('check_in');
    const checkInTimeInput = document.getElementById('check_in_time');
    if (!checkInInput || !checkInTimeInput) {
      return;
    }

    const current = getCurrentLocalDateTimeParts();
    if (checkInInput.value === current.date) {
      checkInTimeInput.min = current.time;
      if (!checkInTimeInput.value || checkInTimeInput.value < current.time) {
        checkInTimeInput.value = current.time;
      }
    } else {
      checkInTimeInput.min = '';
    }
  }

  function calculateTotalPrice() {
    const checkInValue = document.getElementById('check_in').value;
    const checkOutValue = document.getElementById('check_out').value;

    // Remove commas before parsing
    const promoPrice = parseFloat(
      document.getElementById('promo_price').value.replace(/,/g, '')
    ) || 0;
    const maxPax = parseInt(document.getElementById('max_pax').value, 10) || 1;
    const guestCount = parseInt(document.getElementById('guest_count').value, 10) || 1;
    const extraFee = Math.max(0, guestCount - maxPax) * 1000;

    if (checkInValue && checkOutValue) {
      const nights = Math.round((toUtcDate(checkOutValue) - toUtcDate(checkInValue)) / (1000 * 3600 * 24));

      if (isNaN(promoPrice) || nights <= 0) {
        document.getElementById('nights_display').innerText = '0';
        document.getElementById('total_price_display').value = '0.00';
        document.getElementById('downpayment').value = '0.00';
        document.getElementById('downpayment_display').innerText = '0.00';
        return;
      }

      const price = (nights * promoPrice) + extraFee;
      const downpayment = price * 0.3;

      document.getElementById('nights_display').innerText = nights;
      document.getElementById('total_price_display').value = price.toFixed(2);
      document.getElementById('downpayment').value = downpayment.toFixed(2);
      document.getElementById('downpayment_display').innerText = downpayment.toFixed(2);
    }
  }

  // Initialize the calculation on page load
  syncCheckInTimeMin();
  calculateTotalPrice();
  setInterval(syncCheckInTimeMin, 30000);
</script>