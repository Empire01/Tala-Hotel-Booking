<?php
include 'header.php';
require_once 'classes/PackageRoom.php';

$packageRoom = new PackageRoom();
// Fetch only packages visible to customers (hide packages with expired promos)
$rooms = $packageRoom->getCustomerVisiblePackageRooms();
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

  .card-body {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .card-body>div {
    flex-grow: 1;
  }

  .card-body .description {
    min-height: 50px;
    height: auto;
    overflow: hidden;
  }

  .card-body .btn {
    margin-top: auto;
  }

  .slider-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .promo-icon-wrapper {
    position: relative;
    width: 48px;
    height: 48px;
  }

  .promo-percentage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/js/swiffy-slider.min.js" crossorigin="anonymous" defer></script>
<link href="https://cdn.jsdelivr.net/npm/swiffy-slider@1.6.0/dist/css/swiffy-slider.min.css" rel="stylesheet" crossorigin="anonymous">

<div class="container mt-5" style="min-height: 100vh;">
  <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h2 class="m-0">Package Rooms</h2>
  </div>

  <div class="row">
    <?php if (!empty($rooms)): ?>
      <?php foreach ($rooms as $room): ?>
        <div class="col-md-3 mb-4 d-flex align-items-stretch">
          <div class="card shadow border-0 rounded w-100 overflow-hidden">
            <!-- Swiffy Slider -->
            <div class="swiffy-slider slider-item-ratio slider-item-ratio-16x9 slider-nav-animation slider-nav-animation-fadein slider-item-first-visible" id="swiffy-animation">
              <ul class="slider-container" id="container1" style="height: 200px;">
                <?php
                $imagePaths = [
                  $room['package_image'],
                  $room['attachment_1'],
                  $room['attachment_2'],
                  $room['attachment_3']
                ];
                foreach ($imagePaths as $index => $imagePath):
                  if (!empty($imagePath)):
                ?>
                    <li id="slide<?= $index + 1 ?>" class="slide-visible">
                      <img src="<?= htmlspecialchars($imagePath) ?>" alt="Package Image <?= $index + 1 ?>" loading="lazy">
                    </li>
                <?php endif;
                endforeach;
                ?>
              </ul>

              <button type="button" class="slider-nav" aria-label="Go to previous" style="width: 40px;"></button>
              <button type="button" class="slider-nav slider-nav-next" aria-label="Go to next" style="width: 40px;"></button>

              <div class="slider-indicators">
                <?php foreach ($imagePaths as $index => $imagePath): ?>
                  <?php if (!empty($imagePath)): ?>
                    <button aria-label="Go to slide" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="card-body">
              <div class="m-0 p-0">
                <div class="container-fluid m-0 p-0 d-flex justify-content-between align-items-start">
                  <div class="m-0 p-0 d-flex flex-column">
                    <span class="fw-bold fs-5"><?= htmlspecialchars($room['package_name']) ?></span>
                    <small class="text-muted">Rooms: <?= htmlspecialchars($room['included_rooms']) ?></small>
                  </div>
                  <!-- Promo Icon -->
                  <?php if (!empty($room['package_discount']) && $room['package_discount'] > 0): ?>
                    <div class="promo-icon-wrapper position-relative d-inline-block" data-bs-toggle="popover"
                      data-bs-title="Promo Details"
                      data-bs-content="Starts: <?= date('F j, Y', strtotime($room['package_start'])) ?><br>Ends: <?= date('F j, Y', strtotime($room['package_end'])) ?>"
                      data-bs-html="true"
                      style="width: 70px; height: 70px;">

                      <!-- Bookmark Icon -->
                      <i class="bi bi-bookmark-fill text-danger display-3 position-absolute top-0 start-0 w-100 h-100"></i>

                      <!-- Promo Percentage Centered -->
                      <div class="promo-percentage position-absolute top-50 start-50 translate-middle text-center">
                        <small class="text-light fw-bold d-flex flex-column align-items-center justify-content-center me-1" style="font-size: 12px; line-height: 1;">
                          <span>Promo</span>
                          <?= htmlspecialchars(round($room['package_discount'])) ?>%
                        </small>
                      </div>
                    </div>

                  <?php endif; ?>
                </div>

                <div class="description my-2">
                  <small class="text-muted">
                    <?php
                      $desc = $room['package_description'] ?? '';
                      // Remove phrases like "Hanggang May 11 lang" from public listing
                      $desc = preg_replace('/Hanggang\s+.*?\s+lang/i', '', $desc);
                      $desc = trim($desc);
                      echo !empty($desc) ? htmlspecialchars($desc) : 'No description.';
                    ?>
                  </small>
                </div>

              </div>

              <!-- Status & Upload Time -->
              <div class="m-0 p-0 mt-3 d-flex justify-content-between align-items-center">
                <small class="d-flex align-items-center gap-1">
                  <span class="text-muted">Status:</span>
                  <?php
                  $status = $room['room_status'];
                  $badge = match ($status) {
                    'available' => 'success',
                    'booked' => 'danger',
                    'maintenance' => 'warning',
                    default => 'secondary'
                  };
                  ?>
                  <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                </small>

                <div class="d-flex flex-column align-items-end">
                  <?php
                  date_default_timezone_set('Asia/Manila');
                  if (!empty($room['created_at'])) {
                    $diff = time() - strtotime($room['created_at']);
                    $uploadedMessage = match (true) {
                      $diff < 60 => "Uploaded just now",
                      $diff < 3600 => floor($diff / 60) . " minutes ago",
                      $diff < 86400 => floor($diff / 3600) . " hours ago",
                      default => floor($diff / 86400) . " days ago"
                    };
                    echo "<small class='text-muted'>$uploadedMessage</small>";
                  }
                  ?>
                </div>
              </div>

              <div class="mt-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">Up to <?= (int)($room['max_pax'] ?? 2) ?> pax</small>
                <small class="text-muted">₱1,000/additional pax</small>
              </div>

              <!-- Book Button and Price -->
              <div class="m-0 mt-3 d-flex justify-content-between align-items-center">
                <p class="card-text m-0 fs-5">
                  <strong style="letter-spacing: 1px;">₱<?= number_format((float)$room['package_price'], 0, '.', ',') ?></strong>
                </p>
                <?php
                $btnClass = 'btn-success';
                $btnText = 'Book Now';
                $link = 'reserve_package.php?package_id=' . htmlspecialchars($room['id']);

                if ($status === 'booked') {
                  $btnClass = 'btn-secondary disabled';
                  $btnText = 'Unavailable';
                  $link = '#';
                } elseif ($status === 'maintenance') {
                  $btnClass = 'btn-secondary disabled';
                  $btnText = 'Maintenance';
                  $link = '#';
                } elseif ($status !== 'available') {
                  $btnClass = 'btn-secondary disabled';
                  $btnText = 'Not Available';
                  $link = '#';
                }
                ?>
                <a href="<?= $link ?>" class="btn <?= $btnClass ?>"><?= $btnText ?></a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-info text-center reserve-room">No rooms available at the moment.</div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(el => {
      new bootstrap.Popover(el, {
        trigger: 'hover',
        html: true,
        template: `
          <div class="popover custom-popover" role="tooltip">
            <div class="popover-arrow"></div>
            <h3 class="popover-header bg-primary text-light text-start"></h3>
            <div class="popover-body text-start py-2"></div>
          </div>`
      });
    });
  });
</script>


<?php include 'footer.php'; ?>