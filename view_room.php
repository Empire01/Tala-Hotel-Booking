<?php
include 'header.php';
require_once 'classes/Room.php';
require_once 'classes/Reservation.php';
require_once "config/config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$database = new Database();
$conn = $database->connect();

$room = new Room();
$rooms = $room->getAllRooms();

$reserveRoom = new Reservation();
$getReserve = $reserveRoom->getReservation();
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Toastify JS CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<style>
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

  .card {
    font-size: 0.9rem;
    border-radius: 1rem;
    transition: transform 0.2s ease-in-out;
  }

  .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
  }
</style>
<div class="container mt-5" style="min-height: 100vh;">
  <h2 class="mb-4">Room Management</h2>

  <div class="row" id="room-container">
    <?php if (!empty($rooms)): ?>
      <?php foreach ($rooms as $row): ?>
        <div class="col-md-3 mb-5 d-flex align-items-stretch room-card" id="room-card-<?= $row['id'] ?>">
          <div class="card shadow border-0 rounded w-100">
            <img src="<?= htmlspecialchars($row['image_path']) ?>" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-5"><?= isset($row['room_name']) ? ucfirst(htmlspecialchars($row['room_name'])) : "N/A" ?></span>
                <span>
                  <?php if ($row['is_available'] == 0): ?>
                    <?php foreach ($getReserve as $getReserveRow) ?>
                    <a href="#" title="Room Details" class="text-dark room-info-icon info-dark-mode"
                      data-room-id="<?= $row['id'] ?? $getReserveRow['id'] ?>"
                      data-customer-name="<?= htmlspecialchars($row['customer_name']) ?>"
                      data-amount-paid="<?= htmlspecialchars($getReserveRow['amount_paid']) ?>"
                      data-downpayment="<?= htmlspecialchars($getReserveRow['downpayment']) ?>"
                      data-room-price="<?= htmlspecialchars($row['price']) ?>"
                      data-room-promo-price="<?= htmlspecialchars($row['discounted_price']) ?>"
                      data-check-in="<?= isset($getReserveRow['check_in']) ? htmlspecialchars($getReserveRow['check_in']) : '' ?>"
                      data-check-out="<?= isset($getReserveRow['check_out']) ? htmlspecialchars($getReserveRow['check_out']) : '' ?>">
                      <i class="bi bi-info-square"></i>
                    </a>
                  <?php else: ?>
                    <i class="bi bi-info-square" style="color: grey;"></i>
                  <?php endif; ?>
                </span>
              </h5>
              <div class="mb-1 d-flex justify-content-between align-items-center">
                <small class="fw-bold text-muted fs-6"><?= ucfirst(htmlspecialchars($row['room_type'])) ?> Bedroom</small>
                <?php if (!empty($row['promo_discount']) && $row['promo_discount'] > 0): ?>
                  <span class="badge bg-primary">Promo</span>
                <?php endif; ?>
              </div>
              <div class="m-0 p-0 d-flex justify-content-between align-items-center">
                <small class="m-0 d-flex align-items-center gap-1">
                  <span class="text-muted">Status:</span>
                  <?php if ($row['is_available'] == 1): ?>
                    <span class="badge bg-success">Available</span>
                  <?php elseif ($row['is_available'] == 0): ?>
                    <span class="badge bg-danger">Booked</span>
                  <?php elseif ($row['is_available'] == 2): ?>
                    <span class="badge bg-warning">Maintenance</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Not Available</span>
                  <?php endif; ?>
                </small>
                <small class="text-muted">Up to <?= (int)($row['max_pax'] ?? 2) ?> pax</small>
                <small class="m-0 text-muted fs-7">
                  <?php
                  date_default_timezone_set('Asia/Manila');
                  $timestampMessage = "No timestamp available";
                  if (!empty($row['created_at'])) {
                    $created_at = strtotime($row['created_at']);
                    if ($created_at !== false) {
                      $diff = time() - $created_at;
                      if ($diff < 60) $timestampMessage = "Just now";
                      elseif ($diff < 3600) $timestampMessage = floor($diff / 60) . " minutes ago";
                      elseif ($diff < 86400) $timestampMessage = floor($diff / 3600) . " hours ago";
                      else $timestampMessage = floor($diff / 86400) . " days ago";
                    }
                  }
                  echo htmlspecialchars($timestampMessage);
                  ?>
                </small>
              </div>
              <div class="m-0 p-0 d-flex justify-content-between align-items-center gap-1 mt-2">
                <p class="card-text m-0 fs-5">
                  <strong>
                    <?php if (!empty($row['promo_discount']) && $row['promo_discount'] > 0): ?>
                      <span class="text-decoration-line-through text-muted" style="font-size: 14px;">₱<?= number_format((float)$row['price'], 0, '.', ',') ?></span>
                      <span class="ms-2">₱<?= number_format((float)$row['discounted_price'], 0, '.', ',') ?></span>
                    <?php else: ?>
                      ₱<?= number_format((float)$row['price'], 0, '.', ',') ?>
                    <?php endif; ?>
                  </strong>
                </p>
                <div class="m-0 p-0">
                  <a href="edit_room.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                  <button class="btn btn-danger btn-sm delete-room-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-dark text-center w-100 top-profile">No rooms available.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Room Details Modal -->
<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-labelledby="roomDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body">
        <!-- Custom Hotel Header -->
        <div class="hotel-header text-center">
          <h2 class="hotel-name m-0">Tala Hotel</h2>
          <p class="receipt-title mb-3">Official Receipt / Booking Details</p>
        </div>
        <div class="receipt-container">
          <span id="printCustomerName" style="display: none;"></span>
          <table class="table table-bordered">
            <tbody>
              <tr>
                <th>Customer Name</th>
                <td id="customerName"></td>
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
                <th>Remaining Balance</th>
                <td id="remainingBalance" style="color: red;"></td>
              </tr>
              <tr>
                <th>Room Price</th>
                <td id="roomPrice"></td>
              </tr>
              <tr>
                <th>Promo Price</th>
                <td id="promoPrice"></td>
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
<script src="print/view_room_print.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Show room info
    document.querySelectorAll('.room-info-icon').forEach(icon => {
      icon.addEventListener('click', function(e) {
        e.preventDefault();
        const customerName = this.getAttribute('data-customer-name');
        const amountPaid = parseFloat(this.getAttribute('data-amount-paid')) || 0;
        const downpayment = parseFloat(this.getAttribute('data-downpayment')) || 0;
        const roomPrice = parseFloat(this.getAttribute('data-room-price')) || 0;
        const promoPrice = parseFloat(this.getAttribute('data-room-promo-price')) || 0;
        const checkInDate = this.getAttribute('data-check-in');
        const checkOutDate = this.getAttribute('data-check-out');
        const remainingBalance = (promoPrice > 0 ? promoPrice : roomPrice) - downpayment;

        document.getElementById('printCustomerName').textContent = customerName || "N/A";
        document.getElementById('customerName').textContent = customerName || "N/A";
        document.getElementById('amountPaid').textContent = amountPaid.toLocaleString('en-PH', {
          style: 'currency',
          currency: 'PHP'
        });
        document.getElementById('downpayment').textContent = downpayment.toLocaleString('en-PH', {
          style: 'currency',
          currency: 'PHP'
        });
        document.getElementById('remainingBalance').textContent = remainingBalance.toLocaleString('en-PH', {
          style: 'currency',
          currency: 'PHP'
        });
        document.getElementById('roomPrice').textContent = roomPrice.toLocaleString('en-PH', {
          style: 'currency',
          currency: 'PHP'
        });
        document.getElementById('promoPrice').textContent = promoPrice.toLocaleString('en-PH', {
          style: 'currency',
          currency: 'PHP'
        });
        document.getElementById('checkInDate').textContent = checkInDate || "N/A";
        document.getElementById('checkOutDate').textContent = checkOutDate || "N/A";

        new bootstrap.Modal(document.getElementById('roomDetailsModal')).show();
      });
    });
  });

  // AJAX Delete
  document.querySelectorAll('.delete-room-btn').forEach(button => {
    button.addEventListener('click', function() {
      const roomId = this.getAttribute('data-id');
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Delete'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('delete_room.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'id=' + roomId
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                document.getElementById('room-card-' + roomId).remove();
                Toastify({
                  text: data.message,
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "#28a745"
                }).showToast();
              } else {
                Toastify({
                  text: data.message,
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "#dc3545"
                }).showToast();
              }
            });
        }
      });
    });
  });
</script>