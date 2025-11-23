<?php
session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Traveler';

$pesan_error  = '';
$pesan_sukses = '';

// Siapkan variabel agar bisa di-*repopulate* ke form jika error
$title       = $_POST['title']        ?? '';
$start_date  = $_POST['start_date']   ?? '';
$end_date    = $_POST['end_date']     ?? '';
$preferences = $_POST['preferences']  ?? '';
$total_cost  = $_POST['total_cost']   ?? '';

// Handle POST: simpan itinerary baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($title);
    $start_date  = trim($start_date);
    $end_date    = trim($end_date);
    $preferences = trim($preferences);
    $total_cost  = trim($total_cost);

    // Normalisasi nilai
    if ($start_date === '')  $start_date  = null;
    if ($end_date   === '')  $end_date    = null;
    if ($preferences === '') $preferences = null;

    // Normalisasi total_cost (boleh kosong)
    $total_cost_val = null;
    if ($total_cost !== '') {
        // buang karakter selain digit (jaga-jaga user isi "1.500.000" atau "1,500,000")
        $clean = preg_replace('/[^\d]/', '', $total_cost);
        if ($clean !== '' && is_numeric($clean)) {
            $total_cost_val = (float)$clean;
        }
    }

    // Validasi
    if ($title === '') {
        $pesan_error = "Judul itinerary wajib diisi.";
    } elseif ($start_date && $end_date && $start_date > $end_date) {
        $pesan_error = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.";
    } else {
        // Insert ke database
        $sql = "
            INSERT INTO itineraries
                (id_user, title, start_date, end_date, preferences, total_cost, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = mysqli_prepare($koneksi, $sql);
        if (!$stmt) {
            $pesan_error = "Terjadi kesalahan sistem: " . mysqli_error($koneksi);
        } else {

            // Jika total_cost kosong, simpan sebagai 0 (atau bisa NULL kalau ingin dibedakan)
            if ($total_cost_val === null) {
                $total_cost_val = 0.0;
            }

            mysqli_stmt_bind_param(
                $stmt,
                "issssd",            // i: id_user, s: title, s: start_date, s: end_date, s: preferences, d: total_cost
                $id_user,
                $title,
                $start_date,
                $end_date,
                $preferences,
                $total_cost_val
            );

            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($koneksi);
                mysqli_stmt_close($stmt);

                // Redirect ke detail itinerary yang baru dibuat
                header("Location: itinerary-detail.php?itinerary_id=" . $new_id);
                exit;
            } else {
                $pesan_error = "Gagal menyimpan itinerary: " . mysqli_error($koneksi);
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Create Itinerary - Smart Tour Bandung</title>
  <meta name="description" content="Buat itinerary perjalanan baru di Bandung.">
  <meta name="keywords" content="itinerary, bandung, smart tour bandung, travel">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .form-helper-badge {
      font-size: 0.75rem;
      border-radius: 999px;
    }
  </style>
</head>

<body class="starter-page-page">

  <!-- Header -->
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">Smart Tour Bandung</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="destinations.php">Destinations</a></li>
          <li><a href="itineraries.php" class="active">My Itineraries</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li class="dropdown ms-2">
            <a href="#">
              <span><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?></span>
              <i class="bi bi-chevron-down toggle-dropdown"></i>
            </a>
            <ul>
              <li><a href="profile.php">Profile</a></li>
              <li><a href="itineraries.php">My Itineraries</a></li>
              <li><a href="logout.php" onclick="return confirm('Logout dari Smart Tour Bandung?');">Logout</a></li>
            </ul>
          </li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>
  <!-- End Header -->

  <main class="main">

    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>Create New Itinerary</h1>
        <p>Buat rencana perjalanan baru yang disesuaikan dengan preferensimu di Bandung.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="itineraries.php">My Itineraries</a></li>
            <li class="current">Create</li>
          </ol>
        </nav>
      </div>
    </div>
    <!-- End Page Title -->

    <section class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-4 p-lg-5">

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h2 class="h4 mb-0">
                    <i class="bi bi-clipboard-plus me-1"></i> New Itinerary
                  </h2>
                  <span class="badge bg-primary-subtle text-primary form-helper-badge">
                    <i class="bi bi-lightbulb me-1"></i> Tips: mulai dari hari & zona area wisata
                  </span>
                </div>

                <?php if ($pesan_error): ?>
                  <div class="alert alert-danger py-2">
                    <?php echo htmlspecialchars($pesan_error); ?>
                  </div>
                <?php endif; ?>

                <form action="itinerary-create.php" method="post">

                  <!-- Title -->
                  <div class="mb-3">
                    <label for="title" class="form-label">Judul Itinerary <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="title"
                      id="title"
                      class="form-control"
                      required
                      placeholder="Contoh: Weekend Family Trip Lembang - Dago"
                      value="<?php echo htmlspecialchars($title); ?>">
                    <div class="form-text small">
                      Gunakan judul yang mudah diingat, misalnya berdasarkan tema atau tanggal.
                    </div>
                  </div>

                  <!-- Dates -->
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="start_date" class="form-label">Tanggal Mulai</label>
                      <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="end_date" class="form-label">Tanggal Selesai</label>
                      <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                  </div>

                  <!-- Preferences -->
                  <div class="mb-3">
                    <label for="preferences" class="form-label">Preferensi Perjalanan (opsional)</label>
                    <textarea
                      name="preferences"
                      id="preferences"
                      rows="4"
                      class="form-control"
                      placeholder="Contoh: 
- Suka coffee shop & tempat instagramable
- Hindari destinasi yang terlalu ramai
- Cocok untuk anak kecil
- Budget makanan per hari sekitar Rp 300.000"><?php echo htmlspecialchars($preferences); ?></textarea>
                    <div class="form-text small">
                      Preferensi ini bisa digunakan sebagai catatan saat memilih destinasi.
                    </div>
                  </div>

                  <!-- Total Cost -->
                  <div class="mb-3">
                    <label for="total_cost" class="form-label">Estimasi Total Biaya (opsional)</label>
                    <div class="input-group">
                      <span class="input-group-text">Rp</span>
                      <input
                        type="text"
                        name="total_cost"
                        id="total_cost"
                        class="form-control"
                        placeholder="Contoh: 1500000"
                        value="<?php echo htmlspecialchars($total_cost); ?>">
                    </div>
                    <div class="form-text small">
                      Bisa diisi perkiraan kasar dulu, nanti dapat di-update setelah itinerary lengkap.
                    </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="itineraries.php" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-check2-circle me-1"></i> Simpan Itinerary
                    </button>
                  </div>

                </form>

              </div>
            </div>

          </div>
        </div>

      </div>
    </section>

  </main>

  <!-- Footer -->
<?php include 'footer.php'; ?>
