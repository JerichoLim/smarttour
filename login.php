<?php
session_start();
require 'koneksi.php';

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id_user, password FROM user_tbl WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_bind_result($stmt, $id, $password_hash);
    mysqli_stmt_fetch($stmt);

    if ($id) {
        if (password_verify($password, $password_hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $pesan = "Password salah.";
        }
    } else {
        $pesan = "Username tidak ditemukan.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Login - Tour</title>
  <meta name="description" content="Login to Tour web app">
  <meta name="keywords" content="login, tour, travel">

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

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .login-page-wrapper {
      min-height: 100vh;
      background-image: url('assets/img/gallery/gallery-2.webp');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
      z-index: 0;
    }

    .login-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: 1;
    }

    .login-content {
      position: relative;
      z-index: 2;
    }
  </style>
</head>

<body>

  <div class="login-page-wrapper d-flex flex-column align-items-center justify-content-center">
    <div class="login-overlay"></div>

    <!-- JUDUL UTAMA WEBSITE -->
<div class="login-content text-center mb-4">

  <!-- Judul -->
  <h1 class="fw-bold"
      style="
        font-size:2.4rem;
        font-weight:800;
        color:#ffffff;
        letter-spacing:1.5px;
        text-shadow:0 3px 12px rgba(0,0,0,0.6);
      ">
      SMART TOUR <span style="color:#00e0ff;">BANDUNG</span>
  </h1>

  <!-- Garis Estetis -->
  <div style="
      width:180px;
      height:4px;
      margin:8px auto 0 auto;
      border-radius:999px;
      background:linear-gradient(
          to right,
          rgba(255,255,255,0),
          rgba(0,224,255,0.9),
          rgba(255,255,255,0)
      );
      box-shadow:0 0 10px rgba(0,224,255,0.6);
    ">
  </div>

</div>




    <!-- FORM LOGIN -->
    <div class="container login-content">
      <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6">

          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">

              <h2 class="h4 mb-3 text-center">Welcome Back</h2>
              <p class="text-muted text-center mb-4">
                Login to continue exploring Bandung attractions.
              </p>

              <?php if (!empty($pesan)): ?>
                <div class="alert alert-danger py-2">
                  <?= htmlspecialchars($pesan); ?>
                </div>
              <?php endif; ?>

              <form action="" method="post">

                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="username" class="form-control" required
                      placeholder="Your username">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required
                      placeholder="Enter your password">
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                      Remember me
                    </label>
                  </div>
                  <a href="#" class="small text-decoration-none text-muted">Forgot password?</a>
                </div>

                <div class="d-grid mb-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                  </button>
                </div>

                <p class="mb-0 text-center small text-muted">
                  Don't have an account?
                  <a href="register.php" class="fw-semibold">Create one</a>
                </p>

              </form>

            </div>
          </div>

        </div>
      </div>
    </div>

</div>


  <!-- Vendor JS Files (opsional, tapi boleh tetap dipakai) -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
