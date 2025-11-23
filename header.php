<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Smart Tour Bandung</title>
  <meta name="description" content="Smart Tour Bandung - Explore Bandung with personalized itinerary">
  <meta name="keywords" content="bandung, wisata, itinerary, smart tour">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>

<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl d-flex align-items-center justify-content-between">

<a href="index.php" class="logo d-flex align-items-center me-auto">

  <!-- Ikon bulat -->
  <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
       style="
         width:44px;
         height:44px;
         background:linear-gradient(135deg, #00c6ff, #0072ff);
         box-shadow:0 2px 8px rgba(0,0,0,0.25);
       ">
    <i class="bi bi-geo-alt-fill text-white" style="font-size:1.2rem;"></i>
  </div>

  <!-- Nama Brand -->
  <h1 class="sitename m-0"
      style="
        font-size:1.35rem;
        font-weight:800;
        letter-spacing:0.5px;
        color:#ffffff;
        text-shadow:0 2px 4px rgba(0,0,0,0.25);
      ">
      SMART TOUR <span style="color:#00e5ff;">BANDUNG</span>
  </h1>

</a>


      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="destinations.php">Destinations</a></li>
		  <li><a href="events.php">Events</a></li>
          <li><a href="itineraries.php">Itineraries</a></li>
          <li><a href="testimonials.php">Testimonials</a></li>
          <li><a href="gallery.php">Gallery</a></li>

          <?php if ($username): ?>
            <li class="dropdown">
              <a href="#">
                <span><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($username) ?></span>
                <i class="bi bi-chevron-down toggle-dropdown"></i>
              </a>
              <ul>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="itineraries.php">My Itineraries</a></li>
                <li><a href="logout.php" onclick="return confirm('Logout dari Smart Tour Bandung?');">Logout</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li><a href="login.php">Login</a></li>
          <?php endif; ?>

        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
</header>

<main class="main">
