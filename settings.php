<?php
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link href="src/settings.css" rel="stylesheet">
</head>

<body>
  <div class="container py-5" style="min-height: 100vh;">
    <h1 class="mb-4 fw-bold">Settings</h1>

    <!-- Theme Settings -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="card-title fw-semibold">Theme Settings</h5>
            <p class="card-text text-muted">Toggle between light and dark modes.</p>
            <button id="toggle-dark-mode" class="btn btn-outline-dark">Enable Dark Mode</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>

</html>