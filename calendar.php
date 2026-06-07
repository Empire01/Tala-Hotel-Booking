<?php
include 'header.php';
require_once 'config/config.php';
require_once 'classes/Room.php';
require_once 'classes/PackageRoom.php';

$database = new Database();
$conn = $database->connect();

$rooms = new Room();
$regularRooms = $rooms->getAvailableRooms();

$package = new PackageRoom();
$packageRooms = $package->getAllPackageRooms();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Calendar View</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <style>
    #calendar {
      max-width: 100%;
      margin: 0 auto;
    }

    .room-item {
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .room-item:hover {
      background-color: #f1f1f1 !important;
    }

    .room-item:focus {
      background-color: lightgray !important;
    }

    .form-section {
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
    }

    .form-section h5 {
      font-weight: bold;
      margin-bottom: 20px;
    }

    .form-control:focus {
      box-shadow: none;
      border-color: #86b7fe;
    }

    .fc-toolbar-title {
      font-size: 1.25rem !important;
    }

    @media (max-width: 991px) {
      .calendar-side-form {
        margin-top: 20px;
      }
    }

    .rooms-sidebar {
      max-height: 35rem;
      overflow-y: auto;
    }

    .fc-toolbar button {
      background: gray;
      color: white;
      border-radius: 6px;
      border: none;
      padding: 5px 12px;
      margin: 2px;
      font-weight: 600;
    }
  </style>
</head>

<body>
  <div class="container-fluid my-4 px-4">
    <div class="row">
      <div class="col-lg-3 mb-4 rooms-sidebar">
        <h5 class="mb-3">Regular Rooms</h5>
        <ul class="list-unstyled">
          <?php foreach ($regularRooms as $room): ?>
            <li class="d-flex align-items-center p-2 mb-2 bg-white rounded shadow-sm room-item"
              data-type="room" tabindex="0" data-id="<?= $room['id'] ?>">
              <img src="<?= htmlspecialchars($room['image_path']) ?>" class="img-thumbnail" style="width: 3rem; height: 3rem; object-fit: cover;">
              <div class="ms-2 w-100">
                <div class="fw-semibold"><?= htmlspecialchars($room['room_name']) ?></div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <div class="text-muted small calendar-price">₱<?= number_format($room['price'], 2) ?></div>
                  <?php if ($room['is_available'] == '1') : ?>
                    <small class="badge bg-success">Available</small>
                  <?php elseif ($room['is_available'] == '2') : ?>
                    <small class="badge bg-warning">Maintenance</small>
                  <?php endif; ?>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

        <h5 class="mt-4 mb-3">Package Rooms</h5>
        <ul class="list-unstyled">
          <?php foreach ($packageRooms as $room): ?>
            <li class="d-flex align-items-center p-2 mb-2 bg-white rounded shadow-sm room-item"
              data-type="package" tabindex="0" data-id="<?= $room['id'] ?>">
              <img src="<?= htmlspecialchars($room['package_image']) ?>" class="img-thumbnail" style="width: 3rem; height: 3rem; object-fit: cover;">

              <div class="ms-2 w-100">
                <div class="fw-semibold"><?= htmlspecialchars($room['package_name']) ?></div>

                <div class="d-flex justify-content-between align-items-center mt-1">
                  <div class="text-muted small calendar-price">₱<?= number_format($room['package_price'], 2) ?></div>
                  <?php if ($room['room_status'] == 'available') : ?>
                    <small class="badge bg-success">Available</small>
                  <?php elseif ($room['room_status'] == 'maintenance') : ?>
                    <small class="badge bg-warning">Available</small>
                  <?php endif; ?>
                </div>

              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="col-lg-6 mb-4">
        <div id="calendar" class="bg-white p-3 rounded shadow-sm"></div>
      </div>

      <div class="col-lg-3 calendar-side-form mb-4">
        <div class="form-section rounded">
          <h5>Add New Event</h5>
          <form id="addEventForm">
            <div class="mb-3">
              <label for="event_title" class="form-label">Title</label>
              <input type="text" class="form-control" id="event_title" placeholder="Enter event title" required autocomplete="off">
            </div>
            <div class="mb-3">
              <textarea class="form-control" id="event_description" placeholder="Description" style="height: 8rem; resize: none;"></textarea>
            </div>
            <div class="mb-3">
  <label for="event_start" class="form-label">Start Date</label>
  <input type="date" class="form-control" id="event_start" required>
</div>
            <div class="mb-3">
              <label for="event_end" class="form-label">End Date</label>
              <input type="date" class="form-control" id="event_end">
                  <script>
  const now = new Date();
  const offsetPH = 8 * 60; 
  const localTime = new Date(now.getTime() + (offsetPH - now.getTimezoneOffset()) * 60000);
  function formatDate(date) {
    return date.toISOString().split('T')[0];
  }
  const today = new Date(localTime);
  document.getElementById('event_start').min = formatDate(today);
  const tomorrow = new Date(localTime);
  tomorrow.setDate(tomorrow.getDate() + 1);
  document.getElementById('event_end').min = formatDate(tomorrow);
</script>

              <div class="form-text calendar-small">Event!</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Add Event</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php
    $eventQuery = $conn->query("SELECT id, title, description, start_date, end_date FROM events");
    $eventList = $eventQuery->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="row">
      <div class="col-12 p-4 m-0">
        <h4 class="mb-3">Event List</h4>
        <div id="eventListPlaceholder">
          <?php if (!empty($eventList)): ?>
            <div class="row g-3">
              <?php foreach ($eventList as $event): ?>
                <div class="col-md-6 col-lg-4">
                  <div class="card h-100">
                    <div class="card-body">
                      <h5 class="card-title mb-2"><?= htmlspecialchars($event['title']) ?></h5>
                      <h6 class="card-subtitle text-muted mb-2">
                        <?= date('F j, Y', strtotime($event['start_date'])) ?>
                        to <?= date('F j, Y', strtotime($event['end_date'])) ?>
                      </h6>
                      <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-muted">No events found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal for Event Details -->
  <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p><strong>Title:</strong> <span id="modalEventTitle"></span></p>
          <p><strong>Description:</strong> <span id="modalEventDescription"></span></p>
          <p><strong>Start:</strong> <span id="modalEventStart"></span></p>
          <p><strong>End:</strong> <span id="modalEventEnd"></span></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script>
    let calendar;

    window.addEventListener('DOMContentLoaded', function() {
      const calendarEl = document.getElementById('calendar');

      calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        events: 'get_events.php',
        eventClick: function(info) {
          const event = info.event;
          const description = event.extendedProps.description || 'No description';

          document.getElementById('modalEventTitle').textContent = event.title;
          document.getElementById('modalEventDescription').textContent = description;
          document.getElementById('modalEventStart').textContent = event.startStr;
          document.getElementById('modalEventEnd').textContent = event.endStr || 'Same day';

          const eventModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
          eventModal.show();
        }
      });

      calendar.render();

      document.querySelectorAll('.room-item').forEach(item => {
        item.addEventListener('click', function() {
          const id = this.getAttribute('data-id');
          const type = this.getAttribute('data-type');

          fetch(`fetch_events.php?type=${type}&id=${id}`)
            .then(res => res.json())
            .then(events => {
              calendar.removeAllEvents();

              if (events.error) {
                console.error(events.error);
                return;
              }

              events.forEach(event => {
                calendar.addEvent({
                  title: event.title,
                  start: event.start,
                  end: event.end,
                  allDay: true,
                  backgroundColor: type === 'room' ? '#0d6efd' : '#198754',
                  borderColor: type === 'room' ? '#0d6efd' : '#198754',
                  description: event.description || ''
                });
              });
            })
            .catch(err => console.error(err));
        });
      });

      document.getElementById('addEventForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const title = document.getElementById('event_title').value.trim();
        const description = document.getElementById('event_description').value.trim();
        const start = document.getElementById('event_start').value;
        let end = document.getElementById('event_end').value;

        if (!title || !start) {
          Toastify({
            text: "Please fill in required fields.",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc3545"
          }).showToast();
          return;
        }

        if (!end) end = start;

        fetch('add_event.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              title,
              description,
              start,
              end
            })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              calendar.refetchEvents();
              document.getElementById('addEventForm').reset();
              Toastify({
                text: "Event added successfully!",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#198754"
              }).showToast();
            } else {
              Toastify({
                text: data.error || "Failed to add event.",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545"
              }).showToast();
            }
          })
          .catch(err => {
            console.error(err);
            Toastify({
              text: "Something went wrong.",
              duration: 3000,
              gravity: "top",
              position: "right",
              backgroundColor: "#dc3545"
            }).showToast();
          });
      });
    });
  </script>
</body>

</html>