<head>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    /* Footer link styles matching navbar */
    .footer-link {
      position: relative;
      color: #6c757d;
      /* Bootstrap text-muted color */
      transition: color 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .footer-link::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: -2px;
      width: 0%;
      height: 2px;
      background-color: blue;
      transition: width 0.3s ease;
    }

    .footer-link:hover {
      color: blue !important;
    }

    .footer-link:hover::after {
      width: 100%;
    }

    .footer-link.active {
      color: blue !important;
    }

    .footer-link.active::after {
      width: 100%;
    }
  </style>
</head>

<!-- Footer -->
<footer class="text-center text-lg-start bg-body-tertiary text-muted mt-5 shadow">
  <!-- Section: Social media -->
  <section class="d-flex justify-content-center justify-content-lg-between p-4 px-5 border-bottom">
    <!-- Left -->
    <div class="me-5 d-none d-lg-block">
      <span>Get connected with us on social networks:</span>
    </div>

    <!-- Right -->
    <div>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-facebook"></i>
      </a>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-twitter-x"></i> <!-- Twitter (X) -->
      </a>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-google"></i>
      </a>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-instagram"></i>
      </a>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-linkedin"></i>
      </a>
      <a href="#" class="footer-link me-4">
        <i class="bi bi-github"></i>
      </a>
    </div>
  </section>

  <!-- Section: Links -->
  <section>
    <div class="container text-center text-md-start mt-5">
      <div class="row mt-3">
        <!-- Company Info -->
        <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">
            <i class="bi bi-gem me-3"></i> Tala
          </h6>
          <p>At Tala, we provide top-notch services with a focus on quality and integrity. Our team is committed to building trust and delivering exceptional results every time.</p>
        </div>

        <!-- Services -->
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">Services</h6>
          <p><a href="#" class="footer-link">Check-in</a></p>
          <p><a href="#" class="footer-link">Cleaning</a></p>
          <p><a href="#" class="footer-link">Monthly</a></p>
          <p><a href="#" class="footer-link">Installment</a></p>
        </div>

        <!-- Useful Links -->
        <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
          <h6 class="text-uppercase fw-bold mb-4">Useful links</h6>
          <p><a href="#" class="footer-link">Pricing</a></p>
          <p><a href="#" class="footer-link">Settings</a></p>
          <p><a href="#" class="footer-link">Report</a></p>
          <p><a href="#" class="footer-link">Help</a></p>
        </div>

        <!-- Contact -->
        <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
          <h6 class="text-uppercase fw-bold mb-4">Contact</h6>
          <p><i class="bi bi-geo-alt me-3"></i> Maceda, Sampaloc</p>
          <p><i class="bi bi-envelope me-3"></i> Talahotelservices@gmail.com</p>
          <p><i class="bi bi-telephone me-3"></i> +639-567-001-538</p>
        </div>
      </div>
    </div>
  </section>
</footer>