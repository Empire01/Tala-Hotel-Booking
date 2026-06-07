<?php
include 'header.php';
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

// Fetch users
$usersQuery = $conn->query("SELECT * FROM users WHERE role = 'customer'");
$users = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

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

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Users Table</title>
  <style>
    .modal-profile {
      width: 100px;
      height: 100px;
      object-fit: cover;
    }

    .form-control[readonly] {
      background-color: #f8f9fa;
    }

    .label-col {
      text-align: end;
      font-weight: 500;
    }

    .input-col input {
      width: 100%;
    }

    .form-row {
      margin-bottom: 1rem;
    }

    .modal-body label {
      margin-bottom: 0;
    }

    .table td,
    .table th {
      vertical-align: middle;
      padding: 15px 16px;
    }

    .modal-cover {
      background-color: lightslategray;
      height: 120px;
      position: relative;
      border-top-left-radius: 0.5rem;
      border-top-right-radius: 0.5rem;
    }

    .profile-image-container {
      position: absolute;
      bottom: -50px;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 100px;
      border-radius: 50%;
      overflow: hidden;
      border: 4px solid white;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
      background-color: white;
    }

    .profile-image-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>

<body>
  <div class="container my-5">
    <h2 class="mb-4">Users Table</h2>
    <table class="table table-hover table-striped align-middle text-center rounded rounded-4 overflow-hidden shadow">
      <thead class="table-dark">
        <tr>
          <th>Profile Image</th>
          <th>Email</th>
          <th>Account Created</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr data-user-row-id="<?= $user['id'] ?>">
            <td>
              <img src="profile/<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '60111.jpg' ?>"
                alt="Profile" width="50" height="50"
                class="rounded-circle border border-success object-fit-cover">
            </td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= date("F d, Y", strtotime($user['created_at'])) ?></td>
            <td>
              <?php
              $lastSeen = $user['last_seen'];
              if ($lastSeen && (time() - strtotime($lastSeen)) <= 300) { // 5 mins
                echo '<span class="badge bg-success">Online</span>';
              } elseif ($lastSeen) {
                echo '<span class="badge bg-secondary">Active ' . timeAgo($lastSeen) . '</span>';
              } else {
                echo '<span class="badge bg-secondary">Offline</span>';
              }
              ?>
            </td>
            <td>
              <button class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#viewUserModal<?= $user['id'] ?>">
                View Details
              </button>
            </td>
          </tr>

          <div class="modal fade" id="viewUserModal<?= $user['id'] ?>" tabindex="-1"
            aria-labelledby="viewUserModalLabel<?= $user['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">

                <div class="modal-cover">
                  <div class="profile-image-container">
                    <img src="profile/<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '60111.jpg' ?>"
                      alt="Profile">
                  </div>
                </div>

                <div class="modal-body p-5 pt-4 mt-1">
                  <div class="text-center mb-4 mt-5">
                    <div class="m-0 p-0 d-flex justify-content-center align-items-center gap-2" id="userProfileStatus<?= $user['id'] ?>">
                      <h5 class="mb-1"><?= htmlspecialchars($user['fullname']) ?></h5>
                      <!-- Badge will be dynamically injected here -->
                    </div>

                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                  </div>

                  <form>
                    <div class="container">
                      <div class="row form-row align-items-center">
                        <div class="col-md-3 label-col">Full Name</div>
                        <div class="col-md-9 input-col">
                          <input type="text" class="form-control"
                            value="<?= explode(' ', $user['fullname'])[0] ?>" readonly>
                        </div>
                      </div>

                      <div class="row form-row align-items-center">
                        <div class="col-md-3 label-col">Email Address</div>
                        <div class="col-md-9 input-col">
                          <input type="email" class="form-control"
                            value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                      </div>

                      <div class="row form-row align-items-center">
                        <div class="col-md-3 label-col">Date of Birth</div>
                        <div class="col-md-9 input-col">
                          <input type="text" class="form-control"
                            value="<?= !empty($user['date_of_birth']) ? date("F d, Y", strtotime($user['date_of_birth'])) : 'N/A' ?>"
                            readonly>
                        </div>
                      </div>

                      <div class="row form-row align-items-center">
                        <div class="col-md-3 label-col">Profile Photo</div>
                        <div class="col-md-9 d-flex align-items-center">
                          <img id="previewImg<?= $user['id'] ?>"
                            src="profile/<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '60111.jpg' ?>"
                            alt="Profile" width="70" height="70" class="rounded-circle border shadow me-2">

                          <button type="button" class="btn btn-outline-secondary btn-sm replace-btn" data-user-id="<?= $user['id'] ?>">
                            Click to replace
                          </button>

                          <input type="file" accept="image/*" class="d-none profile-input" id="profileInput<?= $user['id'] ?>" data-user-id="<?= $user['id'] ?>">

                          <!-- Hidden field to store selected file temporarily -->
                          <input type="hidden" id="selectedImageFlag<?= $user['id'] ?>" value="0">
                        </div>
                      </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                      <div class="m-0 p-0">
                        <button type="button" class="btn btn-outline-danger delete-user-btn"
                          data-user-id="<?= $user['id'] ?>"
                          data-modal-id="viewUserModal<?= $user['id'] ?>">
                          <i class="bi bi-trash3 me-2"></i>Delete User
                        </button>
                        <button type="button" class="btn btn-outline-danger ms-1">
                          <i class="bi bi-person-dash me-2"></i>Disable Account
                        </button>
                      </div>
                      <div>
                        <button type="button" class="btn btn-outline-dark me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary save-changes-btn" data-user-id="<?= $user['id'] ?>">Save changes</button>
                      </div>
                    </div>
                  </form>

                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

<script>
  function updateOnlineStatuses() {
    fetch('get_online_status.php')
      .then(response => response.json())
      .then(data => {
        data.forEach(user => {
          const row = document.querySelector(`tr[data-user-row-id="${user.id}"]`);
          if (row) {
            const statusCell = row.querySelector('td:nth-child(4)');
            if (statusCell) {
              if (user.status === 'Online') {
                statusCell.innerHTML = '<span class="badge bg-success">Online</span>';
              } else {
                statusCell.innerHTML = `<span class="badge bg-secondary">${user.status}</span>`;
              }
            }
          }

          // Update modal status directly here
          const profileStatus = document.querySelector(`#userProfileStatus${user.id}`);
          if (profileStatus) {
            const nameElement = profileStatus.querySelector('h5');
            const fullName = nameElement ? nameElement.outerHTML : '';

            let badgeClass = 'bg-secondary';
            let statusText = user.status;

            if (user.status === 'Online') {
              badgeClass = 'bg-success';
            }

            profileStatus.innerHTML = `
            ${fullName}
            <span class="badge ${badgeClass}">${statusText}</span>
          `;
          }
        });
      });
  }


  // 🔁 This function updates the badge next to full name in modal
  function updateUserProfileStatus(userId) {
    const profileStatus = document.querySelector(`#userProfileStatus${userId}`);

    if (profileStatus) {
      fetch(`get_online_status.php?id=${userId}`)
        .then(response => response.json())
        .then(user => {
          const lastSeen = user.last_seen;
          let statusText = '';
          let badgeClass = 'bg-secondary';

          if (lastSeen && (Date.now() - new Date(lastSeen).getTime() <= 300000)) {
            statusText = 'Online';
            badgeClass = 'bg-success';
          } else if (lastSeen) {
            statusText = `Active ${timeAgo(lastSeen)}`;
          } else {
            statusText = 'Offline';
          }

          const nameElement = profileStatus.querySelector('h5');
          const fullName = nameElement ? nameElement.outerHTML : '';

          profileStatus.innerHTML = `
          ${fullName}
          <span class="badge ${badgeClass}">${statusText}</span>
        `;
        });
    }
  }

  // Time formatting helper
  function timeAgo(time) {
    const now = new Date();
    const diff = now - new Date(time);
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (minutes < 1) return `${seconds} seconds ago`;
    if (minutes < 60) return `${minutes} minutes ago`;
    if (hours < 24) return `${hours} hours ago`;
    return `${Math.floor(hours / 24)} days ago`;
  }

  // 🔄 Run once immediately
  updateOnlineStatuses();

  // 🔁 Poll every 5 seconds
  setInterval(updateOnlineStatuses, 5000);


  document.querySelectorAll('.replace-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.dataset.userId;
      const fileInput = document.getElementById(`profileInput${userId}`);
      fileInput.click();
    });
  });

  // Preview selected image and set flag
  document.querySelectorAll('.profile-input').forEach(input => {
    input.addEventListener('change', function() {
      const userId = this.dataset.userId;
      const file = this.files[0];

      if (file) {
        const reader = new FileReader();
        reader.onload = () => {
          document.getElementById(`previewImg${userId}`).src = reader.result;
          document.getElementById(`selectedImageFlag${userId}`).value = "1"; // Mark that new image was selected
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // Save changes button handles image update
  document.querySelectorAll('.save-changes-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.dataset.userId;
      const fileInput = document.getElementById(`profileInput${userId}`);
      const selectedFlag = document.getElementById(`selectedImageFlag${userId}`).value;

      if (selectedFlag === "1" && fileInput.files.length > 0) {
        const file = fileInput.files[0];

        Swal.fire({
          title: 'Confirm Update',
          text: 'Are you sure you want to save the new profile picture?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Save',
          cancelButtonText: 'Cancel'
        }).then(result => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('profile_image', file);

            fetch('update_profile_image.php', {
                method: 'POST',
                body: formData
              })
              .then(res => res.json())
              .then(data => {
                if (data.status === 'success') {
                  Toastify({
                    text: data.message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#28a745",
                    style: {
                      marginRight: "10px"
                    }
                  }).showToast();

                  // Update image in the table row
                  // Update image in the table row
                  const newImagePath = data.new_image_path; // From PHP response
                  const tableImage = document.querySelector(`tr[data-user-row-id="${userId}"] img`);
                  if (tableImage) tableImage.src = newImagePath + '?t=' + new Date().getTime();

                  const modalImage = document.querySelector(`#viewUserModal${userId} .modal-cover .profile-image-container img`);
                  if (modalImage) modalImage.src = newImagePath + '?t=' + new Date().getTime();

                  // Reset hidden flag
                  document.getElementById(`selectedImageFlag${userId}`).value = "0";
                } else {
                  Toastify({
                    text: data.message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545"
                  }).showToast();
                }
              })
              .catch(error => {
                console.error('Error:', error);
                Toastify({
                  text: "An error occurred while uploading.",
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "#dc3545"
                }).showToast();
              });
          }
        });
      } else {
        Swal.fire({
          icon: 'info',
          title: 'No changes',
          text: 'Please select a new image before saving.',
        });
      }
    });
  });

  // Delete user
  document.querySelectorAll('.delete-user-btn').forEach(button => {
    button.addEventListener('click', function() {
      const userId = this.dataset.userId;
      const modalId = this.dataset.modalId;

      Swal.fire({
        title: 'Are you sure?',
        text: "This user will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('delete_user.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: 'user_id=' + encodeURIComponent(userId)
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                Toastify({
                  text: data.message,
                  duration: 3000,
                  gravity: "top",
                  position: "right",
                  backgroundColor: "#28a745"
                }).showToast();

                const row = document.querySelector(`tr[data-user-row-id="${userId}"]`);
                if (row) row.remove();

                const modalEl = document.getElementById(modalId);
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
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

</html>