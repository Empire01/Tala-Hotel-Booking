<?php
include 'header.php';
require_once "config/config.php";

$database = new Database();
$conn = $database->connect();

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) {
  die("Room ID is missing.");
}

$sql = "SELECT price, image_path, promo_discount, discounted_price, end_date, max_pax FROM rooms WHERE id = :room_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
$stmt->execute();
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$original_price = $room ? $room['price'] : 0;
$promo_price = $original_price;
$max_pax = max(1, (int)($room['max_pax'] ?? 2));

$image_path = $room['image_path'] ?? 'default.jpg';

if (!empty($room['promo_discount']) && strtotime($room['end_date']) > time()) {
  $promo_discount = $room['promo_discount'];
  $promo_price = $original_price - ($original_price * ($promo_discount / 100));
}

$formatted_price = number_format($original_price, 2, '.', ',');
$formatted_promo_price = number_format($promo_price, 2, '.', ',');
$extra_pax_fee = 1000;
$extra_pax_fee_formatted = number_format($extra_pax_fee, 2, '.', ',');

$guest_name = $_SESSION['fullname'] ?? "Guest";
?>

<style>
  body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', sans-serif;
  }

  .reservation-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    background-color: #ffffff;
  }

  .room-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .form-label {
    font-weight: 600;
    color: #343a40;
  }

  .highlight {
    color: #0d6efd;
    font-weight: 700;
  }

  .info-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .info-group .form-group {
    flex: 1;
    min-width: 200px;
  }

  .form-control:focus {
    box-shadow: none;
    border-color: #0d6efd;
  }

  @media (max-width: 768px) {
    .info-group {
      flex-direction: column;
    }
  }
</style>

<div class="container py-5">
  <div class="reservation-card row g-0 reserve-dark-mode">
    <div class="col-md-6">
      <img src="<?= htmlspecialchars($image_path); ?>" alt="Room Image" class="room-image h-100">
    </div>
    <div class="col-md-6 p-5">
      <h2 class="mb-4">Reserve Your Stay</h2>

      <form id="bookingForm">
        <input type="hidden" name="room_id" value="<?= $room_id; ?>">
        <input type="hidden" id="original_price" value="<?= $formatted_price; ?>">
        <input type="hidden" id="promo_price" value="<?= $formatted_promo_price; ?>">
        <input type="hidden" id="max_pax" value="<?= $max_pax; ?>">
        <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest_name); ?>">

        <div class="mb-3">
          <label class="form-label">Check-in Date</label>
          <input type="date" class="form-control" name="check_in" id="check_in" required>
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
          <label class="form-label">Check-out Date</label>
          <input type="date" class="form-control" name="check_out" id="check_out" required>
        </div>

        <div class="mb-4">
          <label class="form-label">Number of Guests</label>
          <input type="number" class="form-control" name="guest_count" id="guest_count" min="1" step="1" value="1" required>
          <small class="text-muted d-block mt-2">Max pax: <?= $max_pax ?> | Extra pax fee: ₱<?= $extra_pax_fee_formatted ?> each</small>
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

        <div class="info-group mb-4">
          <div class="form-group">
            <label class="form-label">Number of Nights</label>
            <input type="text" class="form-control" id="nights" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Total Price (₱)</label>
            <input type="text" class="form-control" id="total_price_display" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Downpayment (₱)</label>
            <input type="text" class="form-control" name="downpayment" id="downpayment" readonly>
            <small class="text-muted d-block mt-2">Required: <span id="downpayment_display" class="highlight">₱0.00</span></small>
          </div>
        </div>

        <?php if ($promo_price < $original_price): ?>
          <div class="alert alert-info reserve-room">
            <p class="mb-1">Promo Price per Night: <span class="fw-bold">₱<?= $formatted_promo_price ?></span></p>
            <p class="mb-0">Original Price per Night: <del>₱<?= $formatted_price ?></del></p>
          </div>
        <?php else: ?>
          <div class="alert alert-primary">
            <p class="mb-0">Price Per Night: <span class="fw-bold">₱<?= $formatted_price ?></span></p>
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
          <a href="rooms.php" class="btn btn-outline-dark">← Back</a>
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

  document.getElementById("bookingForm").addEventListener("submit", function(e) {
    e.preventDefault();

    if (!validateDates()) {
      Toastify({
        text: "Check-out date must be later than check-in date.",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#dc3545"
      }).showToast();
      return;
    }

    const formData = new FormData(this);

    fetch("ajax_reserve.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.json())
      .then(result => {
        Toastify({
          text: result.message,
          duration: 4000,
          gravity: "top",
          position: "right",
          backgroundColor: result.status === "success" ? "#28a745" : "#dc3545"
        }).showToast();

        if (result.status === "success") {
          setTimeout(() => window.location.href = "my_reservations.php", 2000);
        } else if (result.status === "unauthorized") {
          setTimeout(() => window.location.href = "customer_login.php", 2000);
        }
      })
      .catch(error => {
        console.error("Error:", error);
        Toastify({
          text: "Something went wrong!",
          duration: 4000,
          gravity: "top",
          position: "right",
          backgroundColor: "#dc3545"
        }).showToast();
      });
  });

  function validateDates() {
    const checkInValue = document.getElementById("check_in").value;
    const checkOutValue = document.getElementById("check_out").value;
    const checkInTimeValue = document.getElementById("check_in_time").value;

    if (!checkInValue || !checkOutValue) {
      return false;
    }

    const checkIn = toUtcDate(checkInValue);
    const checkOut = toUtcDate(checkOutValue);
    if (!(checkOut > checkIn)) {
      return false;
    }

    const current = (() => {
      const now = new Date();
      return {
        date: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`,
        time: `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
      };
    })();

    if (checkInValue === current.date && checkInTimeValue && checkInTimeValue < current.time) {
      return false;
    }

    return true;
  }

  document.addEventListener("DOMContentLoaded", () => {
    const originalPrice = parseFloat(document.getElementById("original_price").value.replace(/,/g, '')) || 0;
    const promoPrice = parseFloat(document.getElementById("promo_price").value.replace(/,/g, '')) || originalPrice;

    const checkInInput = document.getElementById("check_in");
    const checkOutInput = document.getElementById("check_out");
    const nightsInput = document.getElementById("nights");
    const totalPriceInput = document.getElementById("total_price_display");
    const downpaymentField = document.getElementById("downpayment");
    const downpaymentDisplay = document.getElementById("downpayment_display");
    const maxPax = parseInt(document.getElementById("max_pax").value, 10) || 1;
    const extraPaxFee = 1000;
    const checkInTimeInput = document.getElementById("check_in_time");
    const checkOutTimeInput = document.getElementById("check_out_time");

    function getCurrentLocalDateTimeParts() {
      const now = new Date();
      return {
        date: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`,
        time: `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
      };
    }

    function syncCheckInTimeMin() {
      if (!checkInInput.value || !checkInTimeInput) {
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

    function calculateAndDisplayPrice() {
      if (!checkInInput.value || !checkOutInput.value) {
        nightsInput.value = "";
        totalPriceInput.value = "";
        downpaymentField.value = "";
        downpaymentDisplay.textContent = "₱0.00";
        return;
      }

      const checkIn = toUtcDate(checkInInput.value);
      const checkOut = toUtcDate(checkOutInput.value);

      if (checkIn && checkOut && checkOut > checkIn) {
        const nights = Math.round((checkOut - checkIn) / (1000 * 3600 * 24));
        const guestCount = parseInt(document.getElementById("guest_count").value, 10) || 1;
        const extraGuests = Math.max(0, guestCount - maxPax);
        const extraFee = extraGuests * extraPaxFee;
        const total = (promoPrice * nights) + extraFee;
        const downpayment = total / 2;

        nightsInput.value = nights;
        totalPriceInput.value = formatCurrency(total);
        downpaymentField.value = formatCurrency(downpayment);
        downpaymentDisplay.textContent = formatCurrency(downpayment);
      } else {
        nightsInput.value = "";
        totalPriceInput.value = "";
        downpaymentField.value = "";
        downpaymentDisplay.textContent = "₱0.00";
      }
    }

    function formatCurrency(amount) {
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
      }).format(amount);
    }

    checkInInput.addEventListener("change", calculateAndDisplayPrice);
    checkInInput.addEventListener("change", syncCheckInTimeMin);
    checkOutInput.addEventListener("change", calculateAndDisplayPrice);
    document.getElementById("guest_count").addEventListener("input", calculateAndDisplayPrice);
    checkInTimeInput.addEventListener("change", calculateAndDisplayPrice);
    checkOutTimeInput.addEventListener("change", calculateAndDisplayPrice);

    syncCheckInTimeMin();
    calculateAndDisplayPrice();
    setInterval(syncCheckInTimeMin, 30000);
  });
</script>