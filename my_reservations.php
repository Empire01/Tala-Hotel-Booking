<?php
include 'header.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

// Fetch room reservations
$sql = "SELECT r.id, r.booking_id, rm.room_name, rm.room_type, rm.room_number, r.check_in, r.check_in_time, r.check_out, r.check_out_time, r.guest_count, r.status, r.downpayment, r.remaining_balance 
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        WHERE r.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$roomReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch package reservations
$sql = "SELECT pr.id, pr.booking_id, p.package_name, pr.guest_name, pr.check_in, pr.check_in_time, pr.check_out, pr.check_out_time, pr.guest_count, pr.amount_paid, pr.downpayment, pr.status
        FROM package_reservation pr
        JOIN packages p ON pr.package_id = p.id
        WHERE pr.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$packageReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Reservations</title>

  <style>
    .scrollable-table-wrapper {
      max-height: 400px;
      overflow-y: auto;
      border-radius: 12px;
    }

    thead th {
      position: sticky;
      top: 0;
      background-color: #212529;
      color: white;
      z-index: 2;
    }

    /* Optional scrollbar style */
    .scrollable-table-wrapper::-webkit-scrollbar {
      width: 6px;
    }

    .scrollable-table-wrapper::-webkit-scrollbar-thumb {
      background-color: #999;
      border-radius: 3px;
    }

    .table td,
    .table th {
      vertical-align: middle;
      padding: 15px 16px;
    }
  </style>
</head>

<body>
  <div class="container mt-5" style="min-height: 100vh;">
    <h2 class="mb-4">My Room Reservations</h2>

    <?php if (count($roomReservations) > 0): ?>
      <div class="scrollable-table-wrapper mb-5 shadow rounded-4">
        <table class="table table-striped mb-0">
          <thead class="table-dark">
            <tr>
              <th>Room #</th>
              <th>Room Name</th>
              <th>Room Type</th>
              <th>Check-in Date</th>
              <th>Check-in Time</th>
              <th>Check-out Date</th>
              <th>Check-out Time</th>
              <th>Guests</th>
              <th>Downpayment</th>
              <th>Remaining Balance</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($roomReservations as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['room_number']) ?></td>
                <td><?= htmlspecialchars($row['room_name']) ?></td>
                <td><?= ucfirst(htmlspecialchars($row['room_type'])) ?></td>
                <td><?= htmlspecialchars($row['check_in']) ?></td>
                <td><?= !empty($row['check_in_time']) ? htmlspecialchars($row['check_in_time']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($row['check_out']) ?></td>
                <td><?= !empty($row['check_out_time']) ? htmlspecialchars($row['check_out_time']) : 'N/A' ?></td>
                <td><?= !empty($row['guest_count']) ? (int)$row['guest_count'] : 1 ?></td>
                <td>₱<?= number_format((float)$row['downpayment'], 2) ?></td>
                <td>₱<?= number_format((float)$row['remaining_balance'], 2) ?></td>
                <td>
                  <?php
                    $roomStatus = strtolower($row['status'] ?? 'pending');
                    $roomBadgeClass = match ($roomStatus) {
                      'cancelled' => 'bg-danger',
                      'canceled' => 'bg-danger',
                      'pending' => 'bg-warning text-dark',
                      'confirmed' => 'bg-success',
                      default => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?= $roomBadgeClass ?>"><?= htmlspecialchars(ucfirst($roomStatus)) ?></span>
                </td>
                <td>
                  <?php if (!in_array($roomStatus, ['cancelled', 'canceled'], true)): ?>
                    <form method="post" action="cancel_room_reservation.php" onsubmit="return confirm('Cancel this room reservation?');" class="d-inline">
                      <input type="hidden" name="reservation_id" value="<?= (int)$row['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">Cancelled</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center reserve-room">No room reservations found.</div>
    <?php endif; ?>

    <h2 class="mb-4">My Package Reservations</h2>

    <?php if (count($packageReservations) > 0): ?>
      <div class="scrollable-table-wrapper shadow rounded-4">
        <table class="table table-striped mb-0">
          <thead class="table-dark">
            <tr>
              <th>Package Name</th>
              <th>Guest Name</th>
              <th>Check-in</th>
              <th>Check-in Time</th>
              <th>Check-out</th>
              <th>Check-out Time</th>
              <th>Guests</th>
              <th>Amount Paid</th>
              <th>Downpayment</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($packageReservations as $pkg): ?>
              <tr>
                <td><?= htmlspecialchars($pkg['package_name']) ?></td>
                <td><?= htmlspecialchars($pkg['guest_name']) ?></td>
                <td><?= htmlspecialchars($pkg['check_in']) ?></td>
                <td><?= !empty($pkg['check_in_time']) ? htmlspecialchars($pkg['check_in_time']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($pkg['check_out']) ?></td>
                <td><?= !empty($pkg['check_out_time']) ? htmlspecialchars($pkg['check_out_time']) : 'N/A' ?></td>
                <td><?= !empty($pkg['guest_count']) ? (int)$pkg['guest_count'] : 1 ?></td>
                <td>₱<?= number_format((float)$pkg['amount_paid'], 2) ?></td>
                <td>₱<?= number_format((float)$pkg['downpayment'], 2) ?></td>
                <td>
                  <?php
                    $status = ucfirst(strtolower($pkg['status'] ?? 'Pending'));
                    $badgeClass = match (strtolower($pkg['status'] ?? 'pending')) {
                      'cancelled' => 'bg-danger',
                      'pending' => 'bg-warning text-dark',
                      'completed' => 'bg-success',
                      default => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                </td>
                <td>
                  <?php if (strtolower($pkg['status'] ?? '') !== 'cancelled'): ?>
                    <form method="post" action="cancel_package_reservation.php" onsubmit="return confirm('Cancel this package reservation?');" class="d-inline">
                      <input type="hidden" name="reservation_id" value="<?= (int)$pkg['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">Cancelled</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center reserve-room">No package reservations found.</div>
    <?php endif; ?>
  </div>

  <?php include 'footer.php'; ?>

</body>

</html>