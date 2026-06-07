<?php
include 'header.php';
require_once 'classes/Room.php';
require_once "config/config.php";

$database = new Database();
$conn = $database->connect();

$room = new Room();
$availableRooms = $room->getAvailableRooms();
$room->updateRoomAvailability();
$room->removeExpiredPromos();

$bookableRooms = array_values(array_filter($availableRooms, function ($row) {
  return isset($row['is_available']) && (int)$row['is_available'] === 1;
}));
?>

<style>
  * {
    user-select: none;
  }

  .card {
    transition: transform 0.3s ease-in-out;
  }

  .card:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  }
</style>

<div class="container mt-5" style="min-height: 100vh;">
  <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h2 class="m-0">Available Rooms</h2>
  </div>

  <div class="row">
    <?php if (!empty($bookableRooms)): ?>
      <?php foreach ($bookableRooms as $row): ?>
        <div class="col-md-3 mb-4 d-flex align-items-stretch">
          <div class="card shadow border-0 rounded w-100">
            <img src="<?= htmlspecialchars($row['image_path']) ?>" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
            <div class="card-body">
              <div class="m-0 p-0 d-flex justify-content-between">
                <div class="m-0 p-0">
                  <h5 class="card-title mb-3">
                    <span class="fw-bold fs-5">
                      <?= isset($row['room_name']) ? ucfirst(htmlspecialchars($row['room_name'])) : "N/A" ?>
                    </span>
                  </h5>
                  <div class="mb-1">
                    <small class="fw-bold text-muted fs-6">
                      <?= ucfirst(htmlspecialchars($row['room_type'])) ?>
                      Bedroom
                    </small>
                  </div>
                </div>
                <!-- Check for Promo Discount -->
                <?php if (isset($row['promo_discount']) && !empty($row['promo_discount']) && strtotime($row['end_date']) > time()): ?>
                  <div class="position-relative d-inline-block">
                    <i class="bi bi-bookmark-fill display-3 text-danger"></i>
                    <span class="position-absolute top-50 start-50 translate-middle rounded-circle p-3"
                      data-bs-toggle="popover"
                      data-bs-title="Promo Details"
                      data-bs-content="Starts: <?= date('F j, Y', strtotime($row['start_date'])) ?><br>Ends: <?= date('F j, Y', strtotime($row['end_date'])) ?>"
                      data-bs-html="true">
                      <small class="text-light fw-bold d-flex flex-column align-items-center justify-content-center mb-2" style="font-size: 13px; user-select: none;">
                        <small style="font-size: 12px;">Promo</small>
                        <?= htmlspecialchars(round($row['promo_discount'])) ?>%
                      </small>
                    </span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Display Availability Status -->
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

                <!-- Display When the Room Was Posted -->
                <small class="m-0 text-muted fs-7">
                  <?php
                  date_default_timezone_set('Asia/Manila'); // Set the correct timezone

                  if (!empty($row['created_at'])) {
                    $created_at = strtotime($row['created_at']);

                    if ($created_at !== false) {
                      $now = time();
                      $diff = max(0, $now - $created_at);

                      if ($diff < 60) {
                        $timestampMessage = "Just now";
                      } elseif ($diff < 3600) {
                        $timestampMessage = floor($diff / 60) . " minutes ago";
                      } elseif ($diff < 86400) {
                        $timestampMessage = floor($diff / 3600) . " hours ago";
                      } else {
                        $timestampMessage = floor($diff / 86400) . " days ago";
                      }
                    } else {
                      $timestampMessage = "Invalid date";
                    }
                  } else {
                    $timestampMessage = "No timestamp available";
                  }

                  echo htmlspecialchars($timestampMessage);
                  ?>
                </small>
              </div>

              <!-- Show "Book Now" only for rooms that can be booked right now -->
              <div class="m-0 p-0 d-flex justify-content-between align-items-center gap-1 mt-2">
                <?php
                // Check if there is a promo discount and calculate the discounted price
                if (!empty($row['promo_discount']) && strtotime($row['end_date']) > time()) {
                  $discountedPrice = (float)$row['price'] * (1 - (float)$row['promo_discount'] / 100);
                } else {
                  $discountedPrice = (float)$row['price'];
                }
                ?>
                <p class="card-text m-0 fs-5">
                  <strong style="letter-spacing: 1px;">
                    ₱<?= number_format($discountedPrice, 0, '.', ',') ?>
                  </strong>
                </p>

                <div class="m-0 p-0">
                  <a href="reserve.php?room_id=<?= htmlspecialchars($row['id']) ?>"
                    class="btn btn-success">
                    Book Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-info text-center reserve-room">No rooms available for booking right now.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl, {
        trigger: 'hover',
        html: true,
        template: `
        <div class="popover" role="tooltip">
          <div class="popover-arrow"></div>
          <h3 class="popover-header bg-primary text-light text-start"></h3>
          <div class="popover-body text-start py-2"></div>
        </div>`
      });
    });
  });
</script>