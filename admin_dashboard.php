<?php
include 'header.php';
require_once 'classes/User.php';
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$database = new Database();
$conn = $database->connect();
$user = new User();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="src/admin.css">
</head>

<body>

  <div class="container-fluid p-0 px-5">
    <section class="position-relative">
      <!-- Cover Image -->
      <img src="img/img1.jpg" alt="Cover Image"
        class="img-fluid rounded-bottom-4 w-100 admin-front-image"
        style="height: 300px; object-fit: cover;">

      <!-- Top Overlay -->
      <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-start p-4 shadow rounded-bottom-4">
        <div class="w-100 d-flex justify-content-between align-items-center">
          <p class="text-light m-0 fw-bold">
            <i class="bi bi-grid"></i> Dashboard / Tala Hotel Reservation
          </p>
          <a href="#" class="text-light text-decoration-none fw-semibold">
            <i class="bi bi-pencil"></i> Edit Cover
          </a>
        </div>
      </div>

      <!-- Info Card Overlay -->
      <div class="position-absolute bottom-0 start-0 ms-4 mb-4 w-50">
        <div class="card rounded-4 shadow-lg">
          <div class="card-body">
            <div class="row">
              <div class="col-md-9">
                <h5>Tala Hotel Reservation</h5>
              </div>
              <div class="col-md-12 d-flex flex-row gap-1 align-items-center border-bottom border-muted pb-3">
                <?php
                $sql = "SELECT COUNT(*) AS total_customers FROM users WHERE role = 'customer'";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalCustomers = $row['total_customers'] ?? 0;
                ?>
                <span class="badge bg-warning dropdown-toggle">Admin</span>
                <span class="text-muted ms-2" style="letter-spacing: 1px;">ID: 29428398</span>
              </div>
              <div class="col-md-12 pt-2 d-flex flex-row gap-3">
                <div class="col-md-3 d-flex flex-column border-end">
                  <span class="fw-bold" style="letter-spacing: 1px;">4029<i class="ms-2 bi bi-arrow-up-right-square"></i></span>
                  <small class="text-muted">Registry ID</small>
                </div>
                <div class="col-md-3 border-end d-flex flex-column">
                  <span class="fw-bold" style="letter-spacing: 1px;">VM0038</span>
                  <small class="text-muted">Methodology</small>
                </div>
                <div class="col-md-3 d-flex border-end flex-column">
                  <span class="fw-bold">Maceda Sampaloc</span>
                  <small class="text-muted">Location</small>
                </div>
                <div class="col-md-3 d-flex flex-column">
                  <span class="fw-bold" style="letter-spacing: 1px;"><?= htmlspecialchars($totalCustomers) ?></span>
                  <small class="text-muted">Customers</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
      $sql = "SELECT SUM(amount) AS total_income FROM payments";
      $stmt = $conn->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $totalIncome = $row['total_income'] ?? 0;
      ?>

      <div class="container w-auto position-absolute bottom-0 end-0 me-4 mb-4 p-0">
        <div class="card rounded-4 shadow-lg">
          <div class="card-body ps-3">
            <div class="row">
              <div class="col-md-9">
                <h5 class="mb-3">Incomes</h5>
              </div>
              <div class="col-md-12 d-flex flex-column gap-1  pb-0">
                <div class="m-0 p-0 d-flex gap-1 align-items-center">
                  <h6 class="m-0">Total</h6>
                  <i class="bi bi-arrow-up-right-square text-success fs-5 ms-1"></i>
                </div>
                <span class="fs-2 fw-bold text-success" style="letter-spacing: 1px;">₱<?= number_format($totalIncome, 2) ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </section>
    <?php
    try {
      $roomCounts = [
        'Available' => 0,
        'Occupied' => 0,
        'Maintenance' => 0,
        'Reserved' => 0
      ];
      $stmt = $conn->prepare("SELECT is_available, COUNT(*) as count FROM rooms GROUP BY is_available");
      $stmt->execute();
      $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($rooms as $room) {
        switch ($room['is_available']) {
          case 1:
            $roomCounts['Available'] = $room['count'];
            break;
          case 0:
            $roomCounts['Occupied'] = $room['count'];
            break;
          case 2:
            $roomCounts['Maintenance'] = $room['count'];
            break;
          case 3:
            $roomCounts['Reserved'] = $room['count'];
            break;
        }
      }
    } catch (PDOException $e) {
      die("Database error: " . $e->getMessage());
    }

    try {
      $stmt = $conn->prepare("SELECT reservations.*, rooms.room_number, rooms.is_available, rooms.room_name
      FROM reservations 
      JOIN rooms ON reservations.room_id = rooms.id 
      ORDER BY check_in DESC");
      $stmt->execute();
      $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Database error: " . $e->getMessage());
    }
    ?>

    <section class="card rounded-4 bg-light my-4 px-4 py-4 shadow border-0">
      <div class="row">
        <!-- Left Sidebar -->
        <div class="col-md-3">
          <h4 class="fw-bold mb-3">Room Availability</h4>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="?status=1" class="btn btn-primary btn-sm">Available (<?= $roomCounts['Available'] ?>)</a>
            <a href="?status=0" class="btn btn-secondary btn-sm">Occupied (<?= $roomCounts['Occupied'] ?>)</a>
            <a href="?status=2" class="btn btn-danger btn-sm">Maintenance (<?= $roomCounts['Maintenance'] ?>)</a>
            <a href="?" class="btn btn-dark btn-sm"><i class="bi bi-list-task"></i> Show All</a>
          </div>

          <!-- Booking List -->
          <div class="list-group shadow" style="max-height: 300px; overflow-y: auto;">
            <?php
            $statusColors = [
              1 => 'primary',
              0 => 'secondary',
              2 => 'danger',
              3 => 'success'
            ];

            // Get the selected status from the URL
            $selectedStatus = isset($_GET['status']) ? intval($_GET['status']) : null;

            // Adjust the SQL query based on the selected status
            if ($selectedStatus !== null) {
              $stmt = $conn->prepare("SELECT * FROM rooms WHERE is_available = ? ORDER BY room_number ASC");
              $stmt->execute([$selectedStatus]);
            } else {
              $stmt = $conn->prepare("SELECT * FROM rooms ORDER BY room_number ASC");
              $stmt->execute();
            }

            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rooms)) {
              foreach ($rooms as $room) {
                $status = $room['is_available'] ?? -1;
            ?>
                <a href="#" class="py-3 list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                  <span class="fw-bold" style="font-size: 15px;">Room <?= htmlspecialchars($room['room_number']) ?></span>
                  <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?>">
                    <?= $status == 1 ? 'Available' : ($status == 0 ? 'Occupied' : ($status == 2 ? 'Maintenance' : 'Reserved')) ?>
                  </span>
                </a>
              <?php
              }
            } else { ?>
              <div class="list-group-item text-center text-muted top-profile">No rooms available</div>
            <?php } ?>
          </div>
        </div>

        <!-- Right Content -->
        <div class="col-md-9">
          <div class="p-0 m-0">
            <div class="m-0 p-0 d-flex justify-content-between align-content-center">
              <div class="m-0 p-0">
                <h4 class="fw-bold mb-3">Guest Booking Details</h4>
                <span class="badge bg-primary">New</span>
                <span class="text-muted ms-2">Tala Hotel Reservation</span>
              </div>
              <form class="d-flex mb-4" role="search">
                <input class="form-control rounded-0 rounded-start" type="search" placeholder="Search Guest Name" required>
                <button class="btn btn-success rounded-0 rounded-end" type="submit"><i class="bi bi-search"></i></button>
              </form>
            </div>

            <div class="table-responsive mt-3" style="max-height: 550px; overflow-y: auto;">
              <table class="table rounded-3 shadow overflow-hidden table-sm">
                <thead class="table-dark text-small">
                  <tr>
                    <th class="ps-3 py-2" style="font-size: 15px;">Guest Name</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Room Name</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Check-in</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Check-in Time</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Check-out</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Check-out Time</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Guests</th>
                    <th class="ps-3 py-2" style="font-size: 15px;">Downpayment</th>
                    <th class="px-3 py-2" style="font-size: 15px;">Remaining Balance</th>
                  </tr>
                </thead>
                <tbody class="text-small">
                  <?php if (!empty($recentBookings)) : ?>
                    <?php foreach ($recentBookings as $booking) : ?>
                      <tr>
                        <td class="ps-3 py-3">
                          <?= !empty($booking['guest_name']) ? htmlspecialchars($booking['guest_name']) : 'N/A' ?>
                        </td>
                        <td class="ps-3 py-3"><?= htmlspecialchars($booking['room_name']) ?></td>
                        <td class="ps-3 py-3"><?= htmlspecialchars($booking['check_in']) ?></td>
                        <td class="ps-3 py-3"><?= !empty($booking['check_in_time']) ? htmlspecialchars($booking['check_in_time']) : 'N/A' ?></td>
                        <td class="ps-3 py-3"><?= htmlspecialchars($booking['check_out']) ?></td>
                        <td class="ps-3 py-3"><?= !empty($booking['check_out_time']) ? htmlspecialchars($booking['check_out_time']) : 'N/A' ?></td>
                        <td class="ps-3 py-3"><?= !empty($booking['guest_count']) ? (int)$booking['guest_count'] : 1 ?></td>
                        <td class="ps-3 py-3">₱<?= number_format($booking['downpayment'], 2) ?></td>
                        <td class="ps-3 py-3">₱<?= number_format($booking['remaining_balance'], 2) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else : ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted ps-3 py-3">No reservations found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="card rounded-4 bg-light mt-4 px-4 py-4 shadow border-0">
      <div class="row p-0 m-0 g-4">
        <h4 class="fw-bold m-0">Upload New Room</h4>
        <!-- Image Upload Section -->
        <div class="col-md-6 d-flex justify-content-center align-items-center flex-column border border-2 border-dark rounded-4 p-4 position-relative" style="height: 400px;" id="border-image-preview">
          <label for="room_image" class="text-center text-decoration-none text-dark w-100 h-100 d-flex justify-content-center align-items-center flex-column" style="cursor: pointer;">
            <i class="bi bi-images display-1" id="icon"></i>
            <h5 class="text-underlined" id="text">Attach a photo of the room</h5>
            <img id="roomPreview" src="#" alt="Room Image" class="img-fluid d-none rounded-3 shadow position-absolute" style="width: 100%; height: 100%; object-fit: cover;">
          </label>
        </div>

        <!-- Room Details Form -->
        <div class="col-md-6">
          <form id="roomForm" class="px-2" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="room_number" class="form-label">Room Number</label>
              <input type="text" name="room_number" class="form-control" placeholder="Auto Generate Room Number" id="room_number" readonly required style="letter-spacing: 1px;">
            </div>

            <div class="mb-3">
              <label for="room_name" class="form-label">Room Name</label>
              <input type="text" name="room_name" class="form-control" placeholder="Enter Room Name" required autocomplete="off">
            </div>

            <div class="mb-3">
              <label for="room_type" class="form-label">Room Type</label>
              <select name="room_type" class="form-select" required>
                <option selected disabled>Select Room Type</option>
                <option value="Standard">Standard</option>
                <option value="Delux">Delux</option>
                <option value="Superior">Superior</option>
                <option value="King Room">King Room</option>
                <option value="Family">Family</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="max_pax" class="form-label">Max Pax</label>
              <input type="number" name="max_pax" class="form-control" id="max_pax" min="1" step="1" value="2" required>
              <small class="text-muted">Extra pax: ₱1,000 each</small>
            </div>

            <div class="mb-3">
              <label for="price" class="form-label">Room Price</label>
              <input type="text" id="price" name="price" class="form-control" placeholder="Enter Room Price" required autocomplete="off" oninput="formatCurrency(this)">
            </div>

            <div class="mb-3">
              <label for="promo_discount" class="form-label">Promo Discount (%)</label>
              <input type="number" name="promo_discount" class="form-control" id="promo_discount" min="0" max="100" step="0.01" placeholder="Enter promo discount (0-100)" autocomplete="off" style="letter-spacing: 1px;">
            </div>

            <div class="mb-3">
              <label for="start_date" class="form-label">Promo Start Date</label>
              <input type="date" name="start_date" class="form-control" id="start_date">
            </div>

            <div class="mb-3">
              <label for="end_date" class="form-label">Promo End Date</label>
              <input type="date" name="end_date" class="form-control" id="end_date">
            </div>

            <div class="mb-3 d-none">
              <label for="room_image" class="form-label">Room Image</label>
              <input type="file" name="room_image" class="form-control" id="room_image" required autocomplete="off">
            </div>

            <button type="submit" class="btn btn-primary float-end mt-2">Upload Room<i class="bi bi-box-arrow-in-down fw-bold ms-1 fs-5"></i></button>
          </form>
        </div>
      </div>
    </section>

    <section class="card rounded-4 bg-light mt-4 mb-5 px-4 py-4 shadow border-0">
      <div class="row p-0 m-0 g-4">
        <h4 class="fw-bold m-0">Upload Package Room</h4>

        <!-- Image Upload Section -->
        <div class="col-md-6">
          <!-- Main Preview -->
          <div class="border border-2 border-dark rounded-4 p-4 position-relative mb-3" style="height: 400px;" id="border-image-preview-package">
            <label for="package_image" class="text-center text-decoration-none text-dark w-100 h-100 d-flex justify-content-center align-items-center flex-column" style="cursor: pointer;">
              <i class="bi bi-box-seam display-1" id="icon-package"></i>
              <h5 class="text-underlined" id="text-package">Attach a photo of the package room</h5>
              <img id="packagePreview" src="#" alt="Package Image" class="img-fluid d-none rounded-3 shadow position-absolute" style="width: 100%; height: 100%; object-fit: cover;">
            </label>
          </div>
        </div>

        <!-- Package Room Form -->
        <div class="col-md-6">
          <form id="packageForm" class="px-2" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="package_name" class="form-label">Package Name</label>
              <input type="text" name="package_name" class="form-control" placeholder="Enter Package Name" required autocomplete="off">
            </div>

            <div class="mb-3">
              <label for="included_rooms" class="form-label">Included Rooms</label>
              <input type="text" name="included_rooms" class="form-control" placeholder="List of included rooms" required autocomplete="off">
            </div>

            <div class="mb-3">
              <label for="max_pax" class="form-label">Max Pax</label>
              <input type="number" name="max_pax" class="form-control" id="package_max_pax" min="1" step="1" value="2" required>
              <small class="text-muted">Extra pax: ₱1,000 each</small>
            </div>

            <div class="mb-3">
              <label for="package_price" class="form-label">Package Price</label>
              <input type="text" name="package_price" class="form-control" placeholder="Enter Package Price" required autocomplete="off" oninput="formatCurrency(this)">
            </div>

            <div class="mb-3">
              <label for="package_discount" class="form-label">Discount (%)</label>
              <input type="number" name="package_discount" class="form-control" min="0" max="100" step="0.01" placeholder="Enter discount (0-100)" autocomplete="off">
            </div>

            <div class="mb-3">
              <label for="package_start" class="form-label">Promo Start Date</label>
              <input type="date" name="package_start" class="form-control">
            </div>

            <div class="mb-3">
              <label for="package_end" class="form-label">Promo End Date</label>
              <input type="date" name="package_end" class="form-control">
            </div>

            <div class="mb-3">
              <label for="package_description" class="form-label">Package Description</label>
              <textarea name="package_description" class="form-control" placeholder="Enter package description" required style="resize: none; height: 100px;"></textarea>
            </div>

            <div class="mb-3 d-none">
              <label for="package_image" class="form-label">Package Image</label>
              <input type="file" name="package_image" class="form-control" id="package_image" required>
            </div>

            <button type="submit" class="btn btn-primary float-end mt-2">Upload Package <i class="bi bi-box-arrow-in-down fw-bold ms-1 fs-5"></i></button>
          </form>
        </div>
      </div>
    </section>

  </div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
    function suggestPackageMaxPax(name) {
      const normalized = (name || '').toLowerCase();
      if (normalized.includes('single')) return 2;
      if (normalized.includes('double')) return 4;
      if (normalized.includes('triple')) return 6;
      if (normalized.includes('quad') || normalized.includes('family')) return 8;
      return 2;
    }

    const packageNameInput = document.querySelector("#packageForm [name='package_name']");
    const packageMaxPaxInput = document.getElementById('package_max_pax');
    if (packageNameInput && packageMaxPaxInput) {
      packageNameInput.addEventListener('input', function() {
        packageMaxPaxInput.value = suggestPackageMaxPax(this.value);
      });
    }

    $("#roomForm").on("submit", function(event) {
      event.preventDefault(); // Prevent form from submitting normally

      // Create a new FormData object (this will handle the file and other form data)
      var formData = new FormData(this);

      $.ajax({
        url: 'upload_room.php', // PHP file to handle the request
        type: 'POST',
        data: formData,
        contentType: false, // Important! Set to false to handle FormData
        processData: false, // Don't process the data
        success: function(response) {
          // Assuming the PHP file returns JSON with a success or error message
          var responseObj = JSON.parse(response);

          // Show toast messages based on the response
          if (responseObj.toast) {
            Toastify({
              text: responseObj.toast,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#4CAF50" // Green for success
            }).showToast();

            // Reset the form (if success)
            $("#roomForm")[0].reset();

            // Clear and hide the image preview
            $("#border-image-preview").addClass("border border-2 border-dark rounded-4 p-4");
            $("#roomPreview").attr("src", "").addClass("d-none"); // Hide preview
            $("#room_number").val(''); // Clear room number input if needed
            $("#icon").show(); // Optionally show icon or reset any other elements
            $("#text").show();
          } else if (responseObj.error) {
            Toastify({
              text: responseObj.error,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#f44336" // Red for error
            }).showToast();
          }
        },
        error: function(xhr, status, error) {
          // Handle AJAX errors
          Toastify({
            text: "An error occurred. Please try again.",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#f44336" // Red for error
          }).showToast();
        }
      });
    });
  });

  $(document).ready(function() {
    $("#packageForm").on("submit", function(event) {
      event.preventDefault(); // Prevent form from submitting normally

      var formData = new FormData(this); // Collect form data

      $.ajax({
        url: 'upload_package.php', // Target upload_package.php
        type: 'POST',
        data: formData,
        contentType: false, // Important!
        processData: false, // Important!
        success: function(response) {
          var responseObj = JSON.parse(response);

          if (responseObj.toast) {
            Toastify({
              text: responseObj.toast,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#4CAF50" // Success green
            }).showToast();

            // Reset form and clear image preview
            $("#packageForm")[0].reset();
            if (packageMaxPaxInput) {
              packageMaxPaxInput.value = 2;
            }
            clearImagePreviews(); // Function to clear image previews (if defined)

            // Optional: Update part of the page, or show a success message
            // Example: Display a message on the same page instead of redirecting
            $('#successMessage').text(responseObj.toast).show();

            // Alternatively, if you want to redirect, uncomment the following line:
            // window.location.href = 'admin_dashboard.php'; // Optionally redirect
          } else if (responseObj.error) {
            Toastify({
              text: responseObj.error,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#f44336" // Error red
            }).showToast();
          }
        },
        error: function(xhr, status, error) {
          Toastify({
            text: "An error occurred. Please try again.",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#f44336" // Error red
          }).showToast();
        }
      });
    });
  });

  function clearImagePreviews() {
    // Clear the main package image preview
    const packagePreview = document.getElementById('packagePreview');
    const iconPackage = document.getElementById('icon-package');
    const textPackage = document.getElementById('text-package');
    const borderBoxPackage = document.getElementById('border-image-preview-package');

    // Reset package image preview
    packagePreview.src = ''; // Clear image preview
    packagePreview.classList.add('d-none'); // Hide the image preview

    // Show the icon and text again
    iconPackage.classList.remove('d-none');
    textPackage.classList.remove('d-none');

    // Reset border box styling
    borderBoxPackage.classList.add('border');
    borderBoxPackage.style.boxShadow = 'none'; // Remove shadow
  }

  // Main image preview (Package Room)
  document.getElementById('package_image').addEventListener('change', function(e) {
    const preview = document.getElementById('packagePreview');
    const icon = document.getElementById('icon-package');
    const text = document.getElementById('text-package');
    const borderBox = document.getElementById('border-image-preview-package');
    const file = e.target.files[0];

    if (file) {
      preview.src = URL.createObjectURL(file);
      preview.classList.remove('d-none'); // Show the image preview
      icon.classList.add('d-none'); // Hide the icon
      text.classList.add('d-none'); // Hide the text
      borderBox.classList.remove('border'); // Ensure border is visible
      borderBox.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.2)'; // Add shadow
    }
  });

  function formatCurrency(input) {
    let value = input.value.replace(/,/g, ''); // Remove any existing commas
    let formattedValue = Number(value).toLocaleString('en-US'); // Format the value with commas
    input.value = formattedValue;
  }

  document.getElementById("room_image").addEventListener("change", function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById("roomPreview");
        const borderDiv = document.getElementById("border-image-preview");

        preview.src = e.target.result;
        preview.classList.remove("d-none");

        document.getElementById("icon").style.display = "none";
        document.getElementById("text").style.display = "none";
        borderDiv.classList.remove("border", "border-2", "border-dark");
      };
      reader.readAsDataURL(file);
    }
  });

  document.getElementById("room_image").addEventListener("change", function(event) {
    const file = event.target.files[0];
    const roomNumberInput = document.getElementById("room_number");

    if (file) {
      // Generate a random room number if a file is selected
      const roomNumber = Math.floor(Math.random() * (9999999999 - 1000000000 + 1)) + 1000000000;
      roomNumberInput.value = roomNumber;

      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById("roomPreview");
        const borderDiv = document.getElementById("border-image-preview");

        preview.src = e.target.result;
        preview.classList.remove("d-none");

        // Hide the icon and text for the preview
        document.getElementById("icon").style.display = "none";
        document.getElementById("text").style.display = "none";

        // Remove the border styling
        borderDiv.classList.remove("border", "border-2", "border-dark");
      };
      reader.readAsDataURL(file);
    } else {
      // Reset the image preview when no file is selected
      const preview = document.getElementById("roomPreview");
      preview.src = '';
      preview.classList.add("d-none");

      // Restore the icon and text visibility
      document.getElementById("icon").style.display = "block";
      document.getElementById("text").style.display = "block";

      // Add the border styling back
      const borderDiv = document.getElementById("border-image-preview");
      borderDiv.classList.add("border", "border-2", "border-dark");

      // Clear the room number input
      roomNumberInput.value = '';
    }
  });

  function mt_rand(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }
</script>

</html>