<?php
include 'header.php';
require_once 'config/config.php';

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Geoapify Autocomplete CSS and JS -->
  <link rel="stylesheet" href="https://unpkg.com/@geoapify/geocoder-autocomplete/styles/minimal.css" />
  <script src="https://unpkg.com/@geoapify/geocoder-autocomplete@1.2.0/dist/geocoder-autocomplete.min.js"></script>

  <title>Profile</title>
  <style>
    .profile-pic-container {
      position: absolute;
      bottom: -50px;
      left: 50%;
      transform: translateX(-50%);
    }

    .profile-pic {
      position: relative;
      width: 100px;
      height: 100px;
    }

    .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #ddd;
    }

    .edit-icon {
      position: absolute;
      bottom: 0;
      right: 0;
      background: #0d6efd;
      color: #fff;
      border-radius: 50%;
      padding: 4px 6px;
      font-size: 12px;
      cursor: pointer;
    }

    .autocomplete-container {
      max-width: 100%;
      margin: 20px auto;
    }

    .suggestions {
      width: 525px;
      margin: 0 auto;
      background-color: white;
      max-height: 200px;
      overflow-y: auto;
      position: absolute;
      z-index: 999;
    }

    .suggestion-item {
      padding: 8px;
      cursor: pointer;
    }

    .suggestion-item:hover {
      background-color: #f0f0f0;
    }
  </style>
</head>

<body>

  <div class="container py-5">
    <div class="card shadow rounded-3 p-4">
      <ul class="nav nav-tabs mb-4" id="profileTabs">
        <li class="nav-item">
          <a class="nav-link active top-profile" href="#">Edit Profile</a>
        </li>
      </ul>

      <form id="profileForm" enctype="multipart/form-data" novalidate>
        <div class="row mb-4 position-relative py-5 rounded-top" style="background-color: gray; height: 150px;">
          <?php
          $profileImg = !empty($user['profile_image']) && file_exists("profile/" . $user['profile_image']) ? $user['profile_image'] : '60111.jpg';
          ?>
          <div class="profile-pic-container">
            <div class="profile-pic">
              <img src="profile/<?= htmlspecialchars($profileImg) ?>" alt="User Profile" id="previewImage">
              <label class="edit-icon" title="Change Photo">
                <input type="file" name="profile_image" hidden>
                ✎
              </label>
            </div>
          </div>
        </div>

        <div class="row g-3" style="margin-top: 5rem;">
          <input type="hidden" name="user_id" value="<?= $user_id ?>">
          <div class="col-md-6">
            <label class="form-label">Your Name</label>
            <?php
            // Assuming $user['role'] contains the user role
            $readonly = ($user['role'] == 'admin') ? 'readonly' : '';
            ?>

            <input type="text" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" name="fullname" autocomplete="off" <?= $readonly ?>>
            <div class="invalid-feedback">Please enter your name.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" name="email" autocomplete="off" <?= $readonly ?>>
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" value="" name="new_password" id="newPassword" autocomplete="new-password" placeholder="Enter new password">
            <small class="text-muted">Leave blank if you don't want to change it.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <input type="password" class="form-control" value="" name="confirm_password" id="confirmPassword" autocomplete="new-password" placeholder="Confirm new password">
              <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show or hide password">Show</button>
            </div>
            <small class="text-muted">Use the button to show or hide the password you typed.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" value="<?= htmlspecialchars($user['date_of_birth']) ?>" name="date_of_birth" autocomplete="off">
            <div class="invalid-feedback">Please enter your date of birth.</div>
          </div>
          <div class="col-md-6">
            <div class="m-0 p-0 d-flex align-items-center gap-2 mb-2">
              <label class="form-label m-0">Present Address</label>
              <small class="text-muted"> &#40;Please fill out this field before updating anything in your profile.&#41;</small>
            </div>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['present_address']) ?>" name="present_address" id="presentAddress" autocomplete="off">
            <div class="invalid-feedback">Please enter a present address.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Permanent Address</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['permanent_address']) ?>" name="permanent_address" id="permanentAddress" autocomplete="off">
            <div class="invalid-feedback">Please enter a permanent address.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['city']) ?>" name="city" id="city" autocomplete="off" readonly>
            <div class="invalid-feedback">Please enter your city.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Postal Code</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['postal_code']) ?>" name="postal_code" autocomplete="off">
            <div class="invalid-feedback">Please enter your postal code.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Country</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['country']) ?>" name="country" id="country" autocomplete="off" readonly>
            <div class="invalid-feedback">Please enter your country.</div>
          </div>
        </div>

        <div class="mt-4 text-end">
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    const apiKey = '1567619f32d9432dbc051c35ac45a7d5';

    function initAddressAutocomplete(inputId, addressType) {
      const input = document.getElementById(inputId);
      const suggestionsContainer = document.createElement('div');
      suggestionsContainer.classList.add('suggestions');
      input.parentNode.appendChild(suggestionsContainer);
      let autoFillTimer = null;

      const debounce = (func, delay) => {
        let timer;
        return (...args) => {
          clearTimeout(timer);
          timer = setTimeout(() => func.apply(this, args), delay);
        };
      };

      function applyLocationFields(suggestion) {
        if (addressType !== 'present' || !suggestion) {
          return;
        }

        const cityInput = document.getElementById('city');
        const countryInput = document.getElementById('country');
        const city = suggestion.properties.city || suggestion.properties.county || suggestion.properties.state || '';
        const country = suggestion.properties.country || suggestion.properties.country_code || '';

        if (cityInput) {
          cityInput.value = city;
          validateField(cityInput);
        }

        if (countryInput) {
          countryInput.value = country;
          validateField(countryInput);
        }
      }

      function fetchLocationSuggestion(query, applyFirstResult = false) {
        const trimmedQuery = query.trim();
        if (trimmedQuery.length < 3) {
          suggestionsContainer.innerHTML = '';
          suggestionsContainer.style.border = 'none';
          return;
        }

        const apiUrl = `https://api.geoapify.com/v1/geocode/autocomplete?text=${encodeURIComponent(trimmedQuery)}&countryCodes=PH&apiKey=${apiKey}`;
        fetch(apiUrl)
          .then(response => response.json())
          .then(data => {
            const suggestions = data.features || [];
            suggestionsContainer.innerHTML = '';

            if (suggestions.length > 0) {
              suggestionsContainer.style.border = '1px solid #ccc';

              if (applyFirstResult) {
                applyLocationFields(suggestions[0]);
                return;
              }

              suggestions.forEach(suggestion => {
                const suggestionItem = document.createElement('div');
                suggestionItem.classList.add('suggestion-item');
                suggestionItem.innerText = suggestion.properties.formatted;

                suggestionItem.addEventListener('click', function() {
                  input.value = suggestion.properties.formatted;
                  suggestionsContainer.innerHTML = '';
                  suggestionsContainer.style.border = 'none';
                  applyLocationFields(suggestion);
                });

                suggestionsContainer.appendChild(suggestionItem);
              });
            } else {
              suggestionsContainer.innerHTML = '<div class="suggestion-item">No suggestions found</div>';
              suggestionsContainer.style.border = '1px solid #ccc';
            }
          })
          .catch(error => {
            console.error("Error fetching suggestions:", error);
          });
      }

      input.addEventListener('input', debounce(function() {
        const query = input.value.trim();
        fetchLocationSuggestion(query, false);
      }, 500));

      input.addEventListener('blur', function() {
        clearTimeout(autoFillTimer);
        autoFillTimer = setTimeout(() => {
          if (input.value.trim().length >= 3) {
            fetchLocationSuggestion(input.value, true);
          }
        }, 250);
      });
    }

    window.addEventListener('DOMContentLoaded', () => {
      initAddressAutocomplete('presentAddress', 'present');
    });

    const requiredFields = ['fullname', 'email', 'date_of_birth', 'present_address']; // added present_address as required
    const optionalFields = ['permanent_address', 'city', 'postal_code', 'country'];
    const originalValues = {};

    window.addEventListener('DOMContentLoaded', () => {
      [...requiredFields, ...optionalFields].forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input && input.value.trim() === "") {
          input.classList.add("is-invalid");
        }
        if (input) {
          originalValues[name] = input.value.trim();
        }
      });

      [...requiredFields, ...optionalFields].forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
          input.addEventListener("input", function() {
            if (this.value.trim() === "") {
              this.classList.add("is-invalid");
              this.classList.remove("is-valid");
            } else {
              this.classList.remove("is-invalid");
            }
          });
        }
      });

      // Image preview on change
      document.querySelector('input[name="profile_image"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(event) {
            document.getElementById('previewImage').src = event.target.result;
          };
          reader.readAsDataURL(file);
        }
      });

      const togglePasswordButton = document.getElementById('togglePassword');
      const newPasswordInput = document.getElementById('newPassword');
      const confirmPasswordInput = document.getElementById('confirmPassword');

      if (togglePasswordButton && newPasswordInput && confirmPasswordInput) {
        togglePasswordButton.addEventListener('click', function() {
          const passwordType = newPasswordInput.type === 'password' ? 'text' : 'password';
          newPasswordInput.type = passwordType;
          confirmPasswordInput.type = passwordType;
          togglePasswordButton.textContent = passwordType === 'password' ? 'Show' : 'Hide';
        });
      }

      // Form submission logic
      document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        let isValid = true;
        let isChanged = false;

        // Validate required fields
        requiredFields.forEach(name => {
          const input = form.querySelector(`input[name="${name}"]`);
          if (input && input.value.trim() === "") {
            input.classList.add("is-invalid");
            isValid = false;
          }
        });

        // Check if all required and optional fields are empty
        const allFieldsEmpty = [...requiredFields, ...optionalFields].every(name => {
          const input = form.querySelector(`input[name="${name}"]`);
          return input && input.value.trim() === "";
        });

        if (allFieldsEmpty) {
          // Show SweetAlert for missing fields
          Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please fill out all fields before updating your profile.',
          });
          return;
        }

        if (!isValid) {
          // Show Toastify for validation error
          Toastify({
            text: "Please fill out the required fields.",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc3545",
          }).showToast();
          return;
        }

        // Check if any text fields changed
        [...requiredFields, ...optionalFields].forEach(name => {
          const input = form.querySelector(`input[name="${name}"]`);
          if (input && originalValues.hasOwnProperty(name) && input.value.trim() !== originalValues[name]) {
            isChanged = true;
          }
        });

        const newPasswordValue = form.querySelector('input[name="new_password"]')?.value.trim() || '';
        const confirmPasswordValue = form.querySelector('input[name="confirm_password"]')?.value.trim() || '';
        if (newPasswordValue !== '' || confirmPasswordValue !== '') {
          isChanged = true;
        }

        // Check if profile image changed
        const profileImageInput = form.querySelector('input[name="profile_image"]');
        const profileImageChanged = profileImageInput && profileImageInput.files.length > 0;

        if (!isChanged && !profileImageChanged) {
          // Show SweetAlert only when no changes detected
          Swal.fire({
            icon: 'info',
            title: 'No changes',
            text: 'Please input your details first before saving.',
          });
          return;
        }

        // Submit via AJAX
        const formData = new FormData(form);
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            Toastify({
              text: data.message,
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: data.success ? "#28a745" : "#dc3545",
            }).showToast();

            if (data.success) {
              const newName = form.querySelector('input[name="fullname"]').value;
              const newProfileImage = document.getElementById('previewImage').src;

              document.querySelector('#userFullName').innerText = newName;
              document.querySelector('#userProfileImage').src = newProfileImage;

              // Update originalValues
              [...requiredFields, ...optionalFields].forEach(name => {
                const input = form.querySelector(`input[name="${name}"]`);
                if (input) {
                  originalValues[name] = input.value.trim();
                }
              });
            }
          })
          .catch(err => {
            console.error('Error:', err);
            Toastify({
              text: "Something went wrong.",
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#dc3545",
            }).showToast();
          });
      });

      // Auto-capitalize specified fields
      function capitalizeWords(str) {
        return str.replace(/\b\w/g, char => char.toUpperCase());
      }

      const capitalizeFields = [
        'fullname',
        'present_address',
        'permanent_address',
        'city',
        'country'
      ];

      capitalizeFields.forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
          input.addEventListener('input', () => {
            const caretPos = input.selectionStart;
            input.value = capitalizeWords(input.value);
            input.setSelectionRange(caretPos, caretPos);
          });
        }
      });
    });

    // Validation function to add/remove is-invalid or is-valid class
    function validateField(input) {
      if (input.value.trim() === "") {
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
      } else {
        input.classList.remove("is-invalid");
      }
    }
  </script>




  <?php if (isset($user['role']) && $user['role'] === 'customer') : ?>
    <?php include 'footer.php'; ?>
  <?php endif; ?>
</body>

</html>