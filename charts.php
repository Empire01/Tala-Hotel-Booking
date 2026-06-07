<?php
include 'header.php';
require_once 'config/config.php';

$database = new Database();
$conn = $database->connect();

// BOOKINGS DATA
$bookingsData = [];
$bookingQuery = $conn->query("SELECT status, COUNT(*) as total FROM bookings GROUP BY status");
while ($row = $bookingQuery->fetch(PDO::FETCH_ASSOC)) {
  $bookingsData['labels'][] = $row['status'];
  $bookingsData['series'][] = (int)$row['total'];
}

$ratingsData = [
  'labels' => ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
  'series' => [0, 0, 0, 0, 0] // Default values for the ratings count
];

$ratingsQuery = $conn->query("SELECT rating, COUNT(*) AS total FROM ratings GROUP BY rating ORDER BY rating DESC");
while ($row = $ratingsQuery->fetch(PDO::FETCH_ASSOC)) {
  // Map ratings values to the correct index (5 stars -> index 0, 1 star -> index 4)
  $ratingsData['series'][5 - $row['rating']] = (int)$row['total'];
}

// USERS DATA (Display Month Name + Year)
$usersData = [];
$usersQuery = $conn->query("
  SELECT DATE_FORMAT(created_at, '%M %Y') AS month, COUNT(*) AS total 
  FROM users 
  GROUP BY month 
  ORDER BY MIN(created_at) ASC
");
while ($row = $usersQuery->fetch(PDO::FETCH_ASSOC)) {
  $usersData['labels'][] = $row['month'];
  $usersData['series'][] = (int)$row['total'];
}

// INCOME DATA (Show Full Date: "Month Day, Year")
$incomeData = [];
$incomeQuery = $conn->query("
  SELECT DATE_FORMAT(booking_date, '%M %d, %Y') AS formatted_date, SUM(payment_amount) as total 
  FROM bookings 
  GROUP BY formatted_date
  ORDER BY MIN(booking_date) ASC
");
while ($row = $incomeQuery->fetch(PDO::FETCH_ASSOC)) {
  $incomeData['labels'][] = $row['formatted_date'];
  $incomeData['series'][] = (float)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <div class="container mt-5" style="min-height: 100vh;">
    <section class="card rounded-4 bg-light mt-4 mb-5 px-5 py-4 shadow border-0 d-flex flex-column gap-5">
      <div class="container-fluid m-0 p-0">
        <h5 class="mb-4">Overview</h5>
        <div id="overviewChart"></div>
      </div>
      <div class="row m-0 p-0">
        <div class="col-md-6 px-2">
          <h5 class="mb-4">Bookings</h5>
          <div id="bookingsChart"></div>
        </div>
        <div class="col-md-6 px-2">
          <h5 class="mb-4">Ratings</h5>
          <div id="ratingsChart"></div>
        </div>
      </div>
      <div class="m-0 p-0">
        <h5 class="mb-4">Users</h5>
        <div id="usersChart"></div>
      </div>
      <div class="container-fluid m-0 p-0">
        <h5 class="mb-4">Incomes</h5>
        <div id="incomesChart"></div>
      </div>
    </section>
  </div>
</body>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Bookings Chart
    var bookingsOptions = {
      series: <?= json_encode($bookingsData['series']) ?>,
      chart: {
        width: "100%",
        type: 'pie'
      },
      labels: <?= json_encode($bookingsData['labels']) ?>,
      colors: Array(<?= count($bookingsData['series']) ?>).fill('#3498db'),
      responsive: [{
        breakpoint: 480,
        options: {
          chart: {
            width: 200
          },
          legend: {
            position: 'bottom'
          }
        }
      }]
    };
    new ApexCharts(document.querySelector("#bookingsChart"), bookingsOptions).render();

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

    // Users Chart (with "Month Year" labels)
    var usersOptions = {
      series: [{
        name: "Users Registered",
        data: <?= json_encode($usersData['series']) ?>
      }],
      chart: {
        type: 'area',
        height: 350,
        toolbar: {
          show: true
        }
      },
      dataLabels: {
        enabled: true
      },
      colors: ['#6C5CE7'],
      stroke: {
        curve: 'smooth',
        width: 3
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.6,
          opacityTo: 0.1,
          stops: [0, 90, 100]
        }
      },
      xaxis: {
        categories: <?= json_encode($usersData['labels']) ?>,
        title: {
          text: 'Registration Month'
        },
        labels: {
          rotate: -45
        }
      },
      yaxis: {
        title: {
          text: 'Total Users'
        },
        tickAmount: 5
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val + " users";
          }
        }
      },
      grid: {
        borderColor: '#e0e0e0',
        strokeDashArray: 4
      }
    };
    new ApexCharts(document.querySelector("#usersChart"), usersOptions).render();

    // Income Chart (with Full Date "Month Day, Year")
    var incomesOptions = {
      series: [{
        name: "Income",
        data: <?= json_encode($incomeData['series']) ?>
      }],
      chart: {
        height: 350,
        type: 'line'
      },
      colors: ['#FDCB6E'],
      stroke: {
        curve: 'smooth',
        width: 3
      },
      xaxis: {
        categories: <?= json_encode($incomeData['labels']) ?>,
        title: {
          text: 'Date'
        }
      },
      yaxis: {
        title: {
          text: 'Amount (PHP)'
        }
      }
    };
    new ApexCharts(document.querySelector("#incomesChart"), incomesOptions).render();
  });
</script>

</html>