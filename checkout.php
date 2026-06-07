<?php
include 'header.php';
require_once 'config/config.php';
require_once 'classes/Room.php';
require_once 'classes/PackageRoom.php';

$database = new Database();
$conn = $database->connect();

$regularRooms = new Room($conn);
$rooms = $regularRooms->getBookedRooms();

$packageRooms = new PackageRoom($conn);
$packages = $packageRooms->getBookedPackageRooms();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout</title>
</head>

<body>
  <div class="container mt-5 mb-5" style="min-height: 100vh;">
    <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
      <h2 class="fw-bold mb-0">Checkout</h2>
      <div class="flex-grow-1" style="max-width: 300px;">
        <input type="search" name="" id="" class="form-control" placeholder="Search Guest">
      </div>
    </div>

    <!-- Regular Rooms Table -->
    <div class="card shadow-sm mb-5">
      <div class="card-header bg-primary text-white fw-semibold p-2">
        Regular Rooms
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th class="text-center">#</th>
                <th>Room Name</th>
                <th>Price</th>
                <th class="text-center" style="width: 150px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($rooms)) : ?>
                <?php foreach ($rooms as $index => $room) : ?>
                  <tr data-id="<?= $room['id'] ?>" data-type="room">
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($room['room_name']) ?></td>
                    <td>₱<?= number_format($room['price'], 2) ?></td>
                    <td class="text-center">
                      <button class="btn btn-danger btn-sm checkout-btn"
                        data-type="room"
                        data-id="<?= $room['id'] ?>">
                        Checkout <i class="bi bi-box-arrow-right ms-2"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="5" class="text-center">No regular rooms available.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Package Rooms Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-success text-white fw-semibold p-2">
        Package Rooms
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th class="text-center">#</th>
                <th>Package Name</th>
                <th>Price</th>
                <th class="text-center" style="width: 150px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($packages)) : ?>
                <?php foreach ($packages as $index => $package) : ?>
                  <tr data-id="<?= $package['id'] ?>" data-type="package">
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($package['package_name']) ?></td>
                    <td>₱<?= number_format($package['package_price'], 2) ?></td>
                    <td class="text-center">
                      <button class="btn btn-danger btn-sm checkout-btn"
                        data-type="package"
                        data-id="<?= $package['id'] ?>">
                        Checkout <i class="bi bi-box-arrow-right ms-2"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="5" class="text-center">No package rooms available.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll(".checkout-btn").forEach(button => {
      button.addEventListener("click", function(e) {
        e.preventDefault();
        const btn = this;
        const type = btn.dataset.type;
        const id = btn.dataset.id;

        Swal.fire({
          title: 'Are you sure?',
          text: "This will checkout the selected room.",
          icon: 'warning',
          showCancelButton: true,
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch("process_checkout.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({
                  type,
                  id
                })
              })
              .then(response => response.json())
              .then(data => {
                Toastify({
                  text: data.message,
                  duration: 3000,
                  close: false,
                  gravity: "top",
                  position: "right",
                  backgroundColor: data.status === "success" ? "#28a745" : "#dc3545",
                }).showToast();

                if (data.status === "success") {
                  const row = btn.closest("tr");
                  const tbody = row.closest("tbody");
                  row.remove();

                  if (tbody.querySelectorAll("tr").length === 0) {
                    const colspan = type === "room" ? 5 : 5;
                    const emptyRow = document.createElement("tr");
                    emptyRow.innerHTML = `
                      <td colspan="${colspan}" class="text-center">
                        No ${type === "room" ? "regular rooms" : "package rooms"} available.
                      </td>
                    `;
                    tbody.appendChild(emptyRow);
                  }
                }
              })
              .catch(error => {
                Toastify({
                  text: "Something went wrong.",
                  duration: 3000,
                  backgroundColor: "#dc3545",
                }).showToast();
              });
          }
        });
      });
    });
  </script>
</body>

</html>