<?php
require 'koneksi.php';

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi dasar
    if ($fullname === '' || $email === '' || $username === '' || $password === '') {
        $pesan = "Nama lengkap, email, username, dan password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid.";
    } elseif ($password !== $password_confirm) {
        $pesan = "Konfirmasi password tidak sama.";
    } else {
        // cek apakah username atau email sudah ada
        $sql = "SELECT id_user FROM user_tbl WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $pesan = "Username atau email sudah dipakai, silakan pilih yang lain.";
        } else {
            // hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // simpan ke database (sesuai struktur user_tbl Anda)
            $sqlInsert = "INSERT INTO user_tbl (username, password, fullname, email, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
            mysqli_stmt_bind_param($stmtInsert, "ssss", $username, $passwordHash, $fullname, $email);

            if (mysqli_stmt_execute($stmtInsert)) {
				// Langsung redirect setelah registrasi berhasil
				header("Location: login.php");
				exit;
			} else {
				$pesan = "Terjadi error saat menyimpan: " . mysqli_error($koneksi);
			}


            mysqli_stmt_close($stmtInsert);
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Register - Smart Tour Bandung</title>
  <meta name="description" content="Create account to Smart Tour Bandung web app">
  <meta name="keywords" content="register, signup, smart tour bandung, travel">

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
      background-image: url('assets/img/travel/showcase-8.webp');
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

    <!-- JUDUL SMART TOUR BANDUNG -->
    <div class="login-content text-center mb-4">
      <h1 class="text-white fw-bold" style="letter-spacing:1px; text-shadow:0 2px 6px rgba(0,0,0,0.6);">
        SMART TOUR BANDUNG
      </h1>
    </div>

    <!-- FORM REGISTER -->
    <div class="container login-content">
      <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6">

          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">

              <h2 class="h4 mb-3 text-center">Create Your Account</h2>
              <p class="text-muted text-center mb-4">
                Join us and start planning your Bandung trips.
              </p>

              <?php if (!empty($pesan)): ?>
                <div class="alert <?php echo (strpos($pesan, 'berhasil') !== false) ? 'alert-success' : 'alert-danger'; ?> py-2">
                  <?php echo htmlspecialchars($pesan); ?>
                </div>
              <?php endif; ?>

              <!-- submit ke halaman ini sendiri -->
              <form action="" method="post">

                <!-- Fullname -->
                <div class="mb-3">
                  <label for="fullname" class="form-label">Nama Lengkap</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="fullname" id="fullname" class="form-control" required
                      placeholder="Nama lengkap Anda"
                      value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">
                  </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" required
                      placeholder="you@example.com"
                      value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                  </div>
                </div>

                <!-- Username -->
                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" name="username" id="username" class="form-control" required
                      placeholder="Pilih username"
                      value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                  </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required
                      minlength="6" placeholder="Minimal 6 karakter">
                  </div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-3">
                  <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" required
                      minlength="6" placeholder="Ulangi password">
                  </div>
                </div>

                <div class="mb-3 form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
                  <label class="form-check-label small" for="terms">
                    Saya setuju dengan <a href="terms.html" target="_blank">Syarat & Ketentuan</a> dan
                    <a href="privacy.html" target="_blank">Kebijakan Privasi</a>.
                  </label>
                </div>

                <div class="d-grid mb-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Register
                  </button>
                </div>

                <p class="mb-0 text-center small text-muted">
                  Sudah punya akun?
                  <a href="login.php" class="fw-semibold">Login di sini</a>
                </p>

              </form>

            </div>
          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- Vendor JS Files (opsional) -->
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
