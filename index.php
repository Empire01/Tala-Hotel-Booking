<?php
include 'header.php';
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
  require_once 'config/config.php';
  $db = new Database();
  $conn = $db->connect();

  $userId = $_SESSION['user_id'];

  // Update last seen
  $conn->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$userId]);

  // Fetch user details
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['present_address'] = $user['present_address'];
  }
}

$ratingsData = [
  'labels' => ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
  'series' => [0, 0, 0, 0, 0] 
];

$ratingsQuery = $conn->query("SELECT rating, COUNT(*) AS total FROM ratings GROUP BY rating ORDER BY rating DESC");
while ($row = $ratingsQuery->fetch(PDO::FETCH_ASSOC)) {
  // Map ratings values to the correct index (5 stars -> index 0, 1 star -> index 4)
  $ratingsData['series'][5 - $row['rating']] = (int)$row['total'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="src/index.css">
 <style>
    .front-text h1 {
      font-size: 4rem;
    }

    .front-text h3 {
      font-size: 2rem;
    }

    .button-group-sm {
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .btn-responsive {
      padding: 0.75rem 1.5rem;
    }

    /* Room Image Hover Effect - Add these new styles */
    .room-img {
      height: 250px;
      object-fit: cover;
      transition: all 0.3s ease;
    }
    
    .room-img:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2) !important;
      cursor: pointer;
      z-index: 10;
      position: relative;
    }
    /* End of Room Image Hover Effect */

    @media (max-width: 768px) {
      .front-text h1 {
        font-size: 2rem;
      }

      .front-text h3 {
        font-size: 1rem;
      }

      .button-group-sm {
        flex-direction: row;
        flex-wrap: wrap;
      }

      .btn-responsive {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        flex: 1 1 auto;
      }

      .field-groups {
        padding: 0rem 1rem !important;
        margin-top: 1rem !important;
      }
    }

    @media (max-width: 576px) {
      .button-group-sm {
        flex-direction: row;
      }
    }
</style>
</head>

<body>
  <div class="container-fluid m-0 p-0">
    <!-- Front image -->
    <div class="container-fluid w-100 text-center p-0 m-0 position-relative">
      <img src="img/home-image.jpg" alt="Tala" class="img-fluid w-100 front-image shadow">
      <div class="position-absolute top-50 start-50 translate-middle text-white front-text">
        <h1 class="fw-bold w-100">Welcome to our <span class="text-uppercase">Tala</span></h1>
        <h3 class="fw-light">Experience luxury and comfort with us.</h3>

        <!-- Responsive buttons -->
        <div class="container-fluid button-group-sm mt-3">
          <a href="rooms.php" class="btn btn-success rounded-2 btn-responsive">
            View Rooms <i class="bi bi-arrow-up-right-square ms-1"></i>
          </a>
          <?php if (!isset($_SESSION['role'])): ?>
            <a href="customer_register.php" class="btn btn-primary rounded-2 btn-responsive">
              Create Account <i class="bi bi-person-add"></i>
            </a>
          <?php else: ?>
            <a href="package_rooms.php" class="btn btn-primary rounded-2 btn-responsive">
              Promo Rooms <i class="bi bi-award"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Overview Section -->
    <div class="row my-5 g-0">
      <!-- Left Column: Overview & About -->
      <div class="col-md-6 d-flex flex-column justify-content-between py-4 px-5">
        <section>
          <h2>Overview</h2>
          <p class="details">Tala Hotel Management is committed to redefining the hospitality experience through personalized service, elegant accommodations, and modern convenience. Our website serves as a seamless portal for guests to explore our luxurious rooms, exclusive packages, and top-tier amenities.</p>
          <p class="details">Whether you're planning a relaxing getaway, a business trip, or a special event, our platform makes it easy to check availability, book rooms, and stay informed about our latest offers — all from the comfort of your device.</p>
        </section>

        <section class="mt-4">
          <h2>About Tala Hotel Services</h2>
          <p class="details">At Tala Hotel, we pride ourselves on delivering world-class hospitality tailored to each guest’s needs. From our beautifully appointed rooms to our attentive customer service, every detail is crafted to ensure a memorable stay.</p>
          <p class="details">Our website highlights our full range of services, including online room booking, package reservations, customer support, and personalized guest accounts. With an intuitive interface and responsive design, guests can experience the Tala Hotel difference before they even arrive.</p>
        </section>
      </div>

      <div class="col-md-6 d-flex flex-column justify-content-start p-4">
        <h2 class="mb-4">Ratings Chart</h2>
        <div id="ratingsChart" class="flex-grow-1" style="min-height: 350px;"></div>
        <p class="text-muted text-center">This data is from the customer and other user that visited our website.</p>
      </div>
    </div>

    <section class="my-5 px-0">
      <div class="container-fluid m-0 p-0 overflow-hidden shadow d-flex align-items-stretch">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.920033396222!2d120.99798737589498!3d14.607037877987547!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b7ef09ab9acb%3A0x5cf0e0b7bde8f7b2!2sMaceda%20St%2C%20Sampaloc%2C%20Manila%2C%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1683554789012!5m2!1sen!2sph" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
</section>
    <section class="my-5">
      <div class="container text-center">
        <div class="mb-5">
          <h2 class="fw-bold">Get in Touch</h2>
          <p class="text-muted mx-auto" style="max-width: 600px;">
            We'd love to hear from you! Whether you have questions, need support, or want to learn more about our services, our team is here to help.
          </p>
        </div>

        <div class="row justify-content-center g-4">
  
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="mb-3">
              <div class="bg-light d-inline-flex border border-primary align-items-center justify-content-center rounded-circle mb-3" style="width: 50px; height: 50px;">
                <i class="bi bi-geo-alt fs-4 text-primary"></i>
              </div>
              <h5 class="fw-semibold">Services</h5>
              <p class="text-muted mb-1">Tala Hotel Reservation</p>
              <p class="text-muted">Maceda Sampaloc</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="mb-3">
              <div class="bg-light d-inline-flex border border-primary align-items-center justify-content-center rounded-circle mb-3" style="width: 50px; height: 50px;">
                <i class="bi bi-telephone fs-4 text-primary"></i>
              </div>
              <h5 class="fw-semibold">Contact</h5>
              <p class="text-muted mb-1">+639567001538</p>
              <p class="text-muted">Talahotelservices@gmail.com</p>
            </div>
          </div>

          <!-- Pricing -->
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="mb-3">
              <div class="bg-light d-inline-flex border border-primary align-items-center justify-content-center rounded-circle mb-3" style="width: 50px; height: 50px;">
                <i class="bi bi-cash-coin fs-4 text-primary"></i>
              </div>
              <h5 class="fw-semibold">Pricing</h5>
              <p class="text-muted mb-1">Affordable packages</p>
              <p class="text-muted">Starting from ₱3000</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="mb-3">
              <div class="bg-light d-inline-flex border border-primary align-items-center justify-content-center rounded-circle mb-3" style="width: 50px; height: 50px;">
                <i class="bi bi-calendar-check fs-4 text-primary"></i>
              </div>
              <h5 class="fw-semibold">Reservation</h5>
              <p class="text-muted mb-1">Book online anytime</p>
              <p class="text-muted">Fast & secure</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="my-5 px-5">
  <div class="row">
    <h2 class="text-uppercase text-center fw-bold mb-3 mt-5">Experience something new!</h2>
    <div class="col-lg-4 col-md-12 mb-4 mb-lg-0">
      <img src="img/fooddd.jpeg" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
      <img src="img/gymm.jpg" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
    </div>
    <div class="col-lg-4 mb-4 mb-lg-0">
      <img src="img/spaaa.png" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
      <img src="img/swimming.jpg" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
    </div>
    <div class="col-lg-4 mb-4 mb-lg-0">
      <img src="img/casino.jpg" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
      <img src="img/nightclub.jpg" class="w-100 shadow-1-strong rounded mb-4 room-img" style="height: 250px; object-fit: cover;">
    </div>
  </div>
</section>

  </div>

  <?php include 'footer.php'; ?>
</body>
<script>
  // Ratings Chart
  var ratingsOptions = {
    series: [{
      name: 'Ratings',
      data: <?= json_encode($ratingsData['series']) ?>
    }],
    chart: {
      type: 'bar',
      height: 350
    },
    plotOptions: {
      bar: {
        borderRadius: 0,
        distributed: true
      }
    },
    colors: ['#00B894', '#0984E3', '#6C5CE7', '#FD79A8', '#E17055'],
    xaxis: {
      categories: <?= json_encode($ratingsData['labels']) ?>,
      labels: {
        style: {
          fontWeight: 600
        }
      }
    }
  };
  new ApexCharts(document.querySelector("#ratingsChart"), ratingsOptions).render();
</script>

</html>