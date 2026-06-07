<?php
include 'header.php';
require_once 'classes/PackageRoom.php';
require_once 'classes/User.php';

$packageRoom = new PackageRoom();
$rooms = $packageRoom->getAllPackageRooms();
$customerDetails = $packageRoom->getPackageReservation();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/js/swiffy-slider.min.js" defer></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/css/swiffy-slider.min.css">

<style>
  .card {
    transition: transform 0.3s ease-in-out;
  }

  .card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  }

  .slider-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .action-icons a {
    color: #000;
    margin: 0 5px;
  }

  .action-icons a:hover {
    color: #0d6efd;
  }

  .card {
    font-size: 0.9rem;
    border-radius: 1rem;
    transition: transform 0.2s ease-in-out;
  }

  .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
  }

  .card-body h5 {
    font-size: 1.1rem;
  }

  .slider-container img {
    object-fit: cover;
    width: 100%;
    height: 100%;
    border-radius: 0.5rem 0.5rem 0 0;
  }

  .custom-badge-success {
    background-color: #28a745 !important;
  }

  .custom-badge-danger {
    background-color: #dc3545 !important;
  }

  .custom-badge-warning {
    background-color: #ffc107 !important;
  }

  .hotel-header {
    margin-bottom: 20px;
  }

  .hotel-name {
    font-size: 24px;
    font-weight: bold;
    color: #333;
  }

  .receipt-title {
    font-size: 16px;
    color: #666;
  }

  .table th {
    background-color: #f2f2f2;
    color: #333;
  }

  .table td {
    font-size: 14px;
    color: #555;
  }

  .modal-footer {
    border-top: 1px solid #ddd;
  }
</style>

<div class="container mt-5" style="min-height: 100vh;">
  <h2 class="mb-4">Room Management</h2>

  <div class="row" id="room-container">
    <?php if (!empty($rooms)): ?>
      <?php foreach ($rooms as $room): ?>
        <div class="col-md-3 mb-5 d-flex align-items-stretch room-card" id="room-card-<?= $room['id'] ?>">
          <div class="card shadow border-0 rounded w-100">
            <!-- Swiffy Slider -->
            <div class="swiffy-slider slider-item-ratio slider-item-ratio-4x3 slider-nav-animation slider-nav-animation-fadein">
              <ul class="slider-container" style="height: 180px;">
                <?php
                $imagePaths = [
                  $room['package_image'],
                  $room['attachment_1'],
                  $room['attachment_2'],
                  $room['attachment_3']
                ];
                foreach ($imagePaths as $index => $imagePath) {
                  if (!empty($imagePath)) {
                    echo '<li><img src="' . htmlspecialchars($imagePath) . '" alt="Image ' . ($index + 1) . '" loading="lazy"></li>';
                  }
                }
                ?>
              </ul>
              <button type="button" class="slider-nav" aria-label="Previous"></button>
              <button type="button" class="slider-nav slider-nav-next" aria-label="Next"></button>

              <div class="slider-indicators">
                <?php foreach ($imagePaths as $index => $imagePath): ?>
                  <?php if (!empty($imagePath)): ?>
                    <button class="<?= ($index == 0) ? 'active' : '' ?>"></button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="card-body">
              <div class="m-0 p-0 d-flex align-items-center justify-content-between mb-2">
                <!-- promo badge -->
                <div class="m-0 p-0">
                  <?php if (!empty($room['package_discount']) && $room['package_discount'] > 0): ?>
                    <span class="badge bg-primary">Promo</span>
                  <?php endif; ?>
                </div>
                <?php if ($room['room_status'] == "booked"): ?>
                  <?php
                  $foundDetail = null;
                  foreach ($customerDetails as $details) {
                    if ($details['package_id'] == $room['id']) {
                      $foundDetail = $details;
                      break; // stop after finding the first match
                    }
                  }
                  ?>

                  <?php if ($foundDetail): ?>
                    <a href="#" class="text-dark package-info-icon info-dark-mode" title="Package Details"
                      data-package-id="<?= $foundDetail['id'] ?>"
                      data-customer-name="<?= htmlspecialchars($foundDetail['guest_name']) ?>"
                      data-amount-paid="<?= htmlspecialchars($foundDetail['amount_paid']) ?>"
                      data-downpayment="<?= htmlspecialchars($foundDetail['downpayment']) ?>"
                      data-package-price="<?= htmlspecialchars($room['package_price']) ?>"
                      data-package-discount="<?= htmlspecialchars($room['package_discount']) ?>"
                      data-check-in="<?= isset($foundDetail['check_in']) ? htmlspecialchars($foundDetail['check_in']) : '' ?>"
                      data-check-out="<?= isset($foundDetail['check_out']) ? htmlspecialchars($foundDetail['check_out']) : '' ?>">
                      <i class="bi bi-info-square fs-5"></i>
                    </a>
                  <?php else: ?>
                    <i class="bi bi-info-square fs-5" style="color: grey;"></i>
                  <?php endif; ?>
                <?php else: ?>
                  <i class="bi bi-info-square fs-5" style="color: grey;"></i>
                <?php endif; ?>
              </div>
              <h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-5"><?= isset($room['package_name']) ? ucfirst(htmlspecialchars($room['package_name'])) : "N/A" ?></span>
                <div class="mb-1 text-muted small included-package-dark-mode"><?= isset($room['included_rooms']) ? htmlspecialchars($room['included_rooms']) . " Room(s)" : "N/A" ?></div>

              </h5>

              <div class="mb-2 text-muted small">Up to <?= (int)($room['max_pax'] ?? 2) ?> pax</div>

              <?php if (!empty($room['package_description'])): ?>
                <p class="text-muted small mb-2">
                  <?php
                    $desc = $room['package_description'] ?? '';
                    // Remove phrases like "Hanggang May 11 lang" from listings
                    $desc = preg_replace('/Hanggang\s+.*?\s+lang/i', '', $desc);
                    $desc = trim($desc);
                    echo !empty($desc) ? htmlspecialchars($desc) : '';
                  ?>
                </p>
              <?php endif; ?>

              <div class="m-0 p-0 d-flex justify-content-between align-items-center">
                <span class="m-0 p-0 d-flex align-items-center gap-1">
                  Status:
                  <?php
                  $badgeClass = '';
                  $badgeText = '';

                  if ($room['room_status'] == 'available') {
                    $badgeClass = 'custom-badge-success';
                    $badgeText = 'Available';
                  } elseif ($room['room_status'] == 'booked') {
                    $badgeClass = 'custom-badge-danger';
                    $badgeText = 'Booked';
                  } elseif ($room['room_status'] == 'maintenance') {
                    $badgeClass = 'custom-badge-warning';
                    $badgeText = 'Maintenance';
                  }

                  if (!function_exists('timeAgo')) {
                    function timeAgo($datetime)
                    {
                      $timestamp = strtotime($datetime);
                      $difference = time() - $timestamp;

                      if ($difference < 60) {
                        return 'Just now';
                      } elseif ($difference < 3600) {
                        $minutes = floor($difference / 60);
                        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
                      } elseif ($difference < 86400) {
                        $hours = floor($difference / 3600);
                        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                      } else {
                        $days = floor($difference / 86400);
                        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                      }
                    }
                  }
                  ?>
                  <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                </span>

                <span class="text-muted small">
                  <?= isset($room['created_at']) ? timeAgo($room['created_at']) : 'N/A' ?>
                </span>
              </div>

              <div class="m-0 p-0 d-flex justify-content-between align-items-center gap-1 mt-2">
                <p class="card-text m-0 fs-5">
                  <strong>
                    ₱<?= number_format((float)$room['package_price'], 0, '.', ',') ?>
                  </strong>
                </p>
                <div class="m-0 p-0">
                  <a href="edit_package_room.php?id=<?= $room['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                  <button class="btn btn-danger btn-sm delete-room-btn" data-id="<?= $room['id'] ?>"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-dark text-center w-100 top-profile">No package rooms found.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Package Details Modal -->
<div class="modal fade" id="packageDetailsModal" tabindex="-1" aria-labelledby="packageDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body">
        <!-- Custom Hotel Header -->
        <div class="hotel-header text-center">
          <h2 class="hotel-name m-0">Tala Hotel</h2>
          <p class="receipt-title mb-3">Official Receipt / Booking Details</p>
        </div>
        <div class="receipt-container">
          <span id="printGuestName" style="display: none;"></span>
          <span id="hotelAddress" style="display: none;"></span>
          <table class="table table-bordered">
            <tbody>
              <tr>
                <th>Customer Name</th>
                <td id="guestName"></td>
              </tr>
              <tr>
                <th>Amount Paid</th>
                <td id="amountPaid"></td>
              </tr>
              <tr>
                <th>Downpayment</th>
                <td id="downpayment"></td>
              </tr>
              <tr>
                <th>Room Price</th>
                <td id="roomPrice"></td>
              </tr>
              <tr>
                <th>Promo Price</th>
                <td id="discountedPrice"></td>
              </tr>
              <tr>
                <th>Check-in Date</th>
                <td id="checkInDate"></td>
              </tr>
              <tr>
                <th>Check-out Date</th>
                <td id="checkOutDate"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
        <button onclick="printRoomDetails()" class="btn btn-success text-light d-flex align-items-center gap-1">
          <i class="bi bi-printer"></i>Print
        </button>
      </div>
    </div>
  </div>
</div>

<script src="print/package_room.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".package-info-icon").forEach((icon) => {
      icon.addEventListener("click", function(e) {
        e.preventDefault();

        const customerName = this.getAttribute("data-customer-name") || "N/A";
        const amountPaid = parseFloat(this.getAttribute("data-amount-paid")) || 0;
        const downpayment = parseFloat(this.getAttribute("data-downpayment")) || 0;
        const packagePrice = parseFloat(this.getAttribute("data-package-price")) || 0;
        const promoPrice = parseFloat(this.getAttribute("data-package-discount")) || 0;
        const checkInDate = this.getAttribute("data-check-in") || "N/A";
        const checkOutDate = this.getAttribute("data-check-out") || "N/A";

        document.getElementById("guestName").innerText = customerName;
        document.getElementById("amountPaid").innerText = `₱${amountPaid.toLocaleString()}`;
        document.getElementById("downpayment").innerText = `₱${downpayment.toLocaleString()}`;
        document.getElementById("roomPrice").innerText = `₱${packagePrice.toLocaleString()}`;
        document.getElementById("discountedPrice").innerText = `₱${promoPrice.toLocaleString()}`;
        document.getElementById("checkInDate").innerText = checkInDate;
        document.getElementById("checkOutDate").innerText = checkOutDate;

        const modal = new bootstrap.Modal(document.getElementById("packageDetailsModal"));
        modal.show();
      });
    });
  });

  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".delete-room-btn").forEach(button => {
      button.addEventListener("click", function() {
        const roomId = this.getAttribute("data-id");

        Swal.fire({
          title: 'Are you sure?',
          text: "This action cannot be undone!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Delete'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('delete_package_room.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + roomId
              })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  Toastify({
                    text: "Package room deleted successfully!",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#28a745",
                  }).showToast();
                  document.getElementById("room-card-" + roomId).remove();
                } else {
                  Swal.fire("Error", data.message || "Deletion failed.", "error");
                }
              })
              .catch(error => {
                Swal.fire("Error", "An error occurred.", "error");
                console.error("Error:", error);
              });
          }
        });
      });
    });
  });
</script>