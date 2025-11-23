<?php
session_start();
require 'koneksi.php';

// =====================================
// Wajib login
// =====================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Traveler';

$pesan_error  = '';
$pesan_sukses = '';

// nilai default form
$title        = '';
$start_date   = '';
$end_date     = '';
$max_per_day  = 3;
$preferences  = '';
$selected_cat = [];

// =====================================
// Ambil kategori destinasi untuk filter
// =====================================
$categories = [];
$sqlCat = "SELECT category_id, name FROM destination_categories ORDER BY name ASC";
$resCat = mysqli_query($koneksi, $sqlCat);
if ($resCat) {
    while ($row = mysqli_fetch_assoc($resCat)) {
        $categories[] = $row;
    }
}

/**
 * ===========================
 *  HELPER DUMMY CUACA & TRAFFIC
 * ===========================
 * Di bawah ini hanya simulasi.
 * Nanti bisa diganti dengan data BMKG & Google Maps beneran.
 */

/**
 * Slot waktu sederhana berdasarkan urutan destinasi per hari:
 *  - 0 => pagi
 *  - 1 => siang
 *  - 2 => sore
 */
function get_time_slot_key($indexPerDay) {
    if ($indexPerDay === 0) return 'pagi';
    if ($indexPerDay === 1) return 'siang';
    return 'sore';
}

/**
 * Deskripsi dummy cuaca per slot.
 */
function dummy_weather_desc($slotKey) {
    switch ($slotKey) {
        case 'pagi':
            return 'cerah berawan';
        case 'siang':
            return 'potensi hujan ringan';
        case 'sore':
            return 'berawan';
        default:
            return 'berawan';
    }
}

/**
 * Skor dummy cuaca per kategori.
 * - Outdoor (3,4,6,7,8) lebih bagus pagi/sore, sedikit minus siang.
 * - Indoor (14,15,16,17) justru di-boost saat siang (seolah hujan).
 */
function dummy_weather_score($slotKey, $categoryId) {
    $outdoorCats = [3, 4, 6, 7, 8];      // air terjun, danau, gunung, kebun binatang, waterpark
    $indoorCats  = [14, 15, 16, 17];     // tempat belanja, restoran, street food, cafe

    $score = 0;

    if (in_array($categoryId, $outdoorCats, true)) {
        if ($slotKey === 'pagi')  $score += 2;
        if ($slotKey === 'siang') $score -= 1;
        if ($slotKey === 'sore')  $score += 1;
    }

    if (in_array($categoryId, $indoorCats, true)) {
        if ($slotKey === 'siang') $score += 2;   // siang cenderung hujan → indoor jadi favorit
    }

    return $score;
}

/**
 * Deskripsi dummy traffic per slot.
 */
function dummy_traffic_desc($slotKey) {
    switch ($slotKey) {
        case 'pagi':
            return 'lalu lintas padat sedang (jam berangkat)';
        case 'siang':
            return 'lalu lintas relatif lancar';
        case 'sore':
            return 'potensi macet (jam pulang kantor)';
        default:
            return 'lalu lintas normal';
    }
}

/**
 * Skor dummy traffic.
 * Misal:
 *  - pagi & sore dikurangi sedikit
 *  - siang lebih positif (cenderung lebih lancar)
 */
function dummy_traffic_score($slotKey) {
    if ($slotKey === 'siang') return 1;
    if ($slotKey === 'pagi' || $slotKey === 'sore') return -1;
    return 0;
}

// =====================================
// HANDLE POST: generate itinerary otomatis
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title        = trim($_POST['title'] ?? '');
    $start_date   = trim($_POST['start_date'] ?? '');
    $end_date     = trim($_POST['end_date'] ?? '');
    $max_per_day  = (int)($_POST['max_per_day'] ?? 3);
    $preferences  = trim($_POST['preferences'] ?? '');
    $selected_cat = $_POST['categories'] ?? [];

    if ($max_per_day <= 0) $max_per_day = 3;
    if ($max_per_day > 10) $max_per_day = 10;

    // Validasi dasar
    if ($title === '') {
        $pesan_error = "Judul itinerary wajib diisi.";
    } elseif ($start_date === '') {
        $pesan_error = "Tanggal mulai wajib diisi.";
    } else {
        // Normalisasi tanggal & hitung lama hari
        $startDt = DateTime::createFromFormat('Y-m-d', $start_date);
        if (!$startDt) {
            $pesan_error = "Format tanggal mulai tidak valid.";
        } else {
            if ($end_date === '') {
                // kalau end_date kosong, anggap 1 hari
                $endDt = clone $startDt;
                $end_date = $endDt->format('Y-m-d');
            } else {
                $endDt = DateTime::createFromFormat('Y-m-d', $end_date);
                if (!$endDt) {
                    $pesan_error = "Format tanggal selesai tidak valid.";
                }
            }

            if ($pesan_error === '' && $startDt > $endDt) {
                $pesan_error = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.";
            }

            // Kalau tidak ada error, lanjut
            if ($pesan_error === '') {

                $diffDays = $startDt->diff($endDt)->days;
                $total_days = $diffDays + 1;
                if ($total_days <= 0) $total_days = 1;

                // =====================================
                // Ambil destinasi kandidat
                // =====================================
                $limit = $total_days * $max_per_day;

                // siapkan filter kategori
                $cat_ids_int = [];
                if (!empty($selected_cat) && is_array($selected_cat)) {
                    foreach ($selected_cat as $cid) {
                        $cid_int = (int)$cid;
                        if ($cid_int > 0) {
                            $cat_ids_int[] = $cid_int;
                        }
                    }
                }

                // sekarang juga ambil category_id agar bisa dipakai skor cuaca/traffic
                $sqlDest = "SELECT destination_id, name, category_id, ticket_price FROM destinations";
                if (!empty($cat_ids_int)) {
                    $in = implode(',', $cat_ids_int);
                    $sqlDest .= " WHERE category_id IN ($in)";
                }
                $sqlDest .= " ORDER BY RAND() LIMIT " . (int)$limit;

                $resDest = mysqli_query($koneksi, $sqlDest);
                $destList = [];
                if ($resDest) {
                    while ($row = mysqli_fetch_assoc($resDest)) {
                        $destList[] = $row;
                    }
                }

                if (empty($destList)) {
                    $pesan_error = "Tidak ditemukan destinasi yang cocok untuk preferensi/kategori yang dipilih.";
                } else {
                    // =====================================
                    // Insert ke tabel itineraries (header)
                    // =====================================
                    $sqlIt = "
                        INSERT INTO itineraries
                            (id_user, title, start_date, end_date, preferences, created_at)
                        VALUES
                            (?, ?, ?, ?, ?, NOW())
                    ";
                    $stmtIt = mysqli_prepare($koneksi, $sqlIt);
                    if (!$stmtIt) {
                        $pesan_error = "Terjadi kesalahan sistem (insert itinerary): " . mysqli_error($koneksi);
                    } else {
                        mysqli_stmt_bind_param(
                            $stmtIt,
                            "issss",
                            $id_user,
                            $title,
                            $start_date,
                            $end_date,
                            $preferences
                        );

                        if (mysqli_stmt_execute($stmtIt)) {
                            $new_itinerary_id = mysqli_insert_id($koneksi);
                            mysqli_stmt_close($stmtIt);

                            // =====================================
                            // Insert item itinerary per hari
                            // dengan dummy cuaca & traffic
                            // =====================================
                            $sqlItem = "
                                INSERT INTO itinerary_items
                                    (itinerary_id, destination_id, visit_date, start_time, end_time, order_number, notes)
                                VALUES
                                    (?, ?, ?, ?, ?, ?, ?)
                            ";
                            $stmtItem = mysqli_prepare($koneksi, $sqlItem);
                            if (!$stmtItem) {
                                $pesan_error = "Terjadi kesalahan sistem (insert items).";
                            } else {
                                $timeSlots = ['09:00:00', '13:00:00', '16:00:00'];
                                $destIndex = 0;
                                $totalDest = count($destList);

                                for ($d = 0; $d < $total_days; $d++) {
                                    $visitDt = clone $startDt;
                                    if ($d > 0) {
                                        $visitDt->modify("+" . $d . " day");
                                    }
                                    $visit_date_str = $visitDt->format('Y-m-d');

                                    // per hari, pilih destinasi dengan mempertimbangkan skor cuaca & traffic
                                    for ($i = 0; $i < $max_per_day && $destIndex < $totalDest; $i++) {

                                        $slotKey   = get_time_slot_key($i); // pagi / siang / sore
                                        $start_time = $timeSlots[$i] ?? null;
                                        $end_time   = null; // bisa dikembangkan

                                        // pilih destinasi terbaik dari sisa kandidat [destIndex .. end]
                                        $bestIndex = $destIndex;
                                        $bestScore = -9999;

                                        for ($k = $destIndex; $k < $totalDest; $k++) {
                                            $cand = $destList[$k];

                                            $catId = isset($cand['category_id']) ? (int)$cand['category_id'] : 0;

                                            $baseScore     = 0; // bisa dikembangkan misal berdasar rating/ticket_price
                                            $weatherScore  = dummy_weather_score($slotKey, $catId);
                                            $trafficScore  = dummy_traffic_score($slotKey);

                                            // total skor: kombinasi
                                            $score = $baseScore + $weatherScore + $trafficScore;

                                            if ($score > $bestScore) {
                                                $bestScore = $score;
                                                $bestIndex = $k;
                                            }
                                        }

                                        // tukar posisi supaya yang terbaik masuk ke destIndex
                                        if ($bestIndex !== $destIndex) {
                                            $tmp = $destList[$destIndex];
                                            $destList[$destIndex] = $destList[$bestIndex];
                                            $destList[$bestIndex] = $tmp;
                                        }

                                        // gunakan destinasi pada destIndex
                                        $dest = $destList[$destIndex];
                                        $destIndex++;

                                        $order_number = $i + 1;

                                        // catatan: seolah-olah mempertimbangkan cuaca & traffic
                                        $weatherDesc = dummy_weather_desc($slotKey);
                                        $trafficDesc = dummy_traffic_desc($slotKey);

                                        $notes = "Generated automatically from preferences.\n" .
                                                 "Perkiraan cuaca slot {$slotKey}: {$weatherDesc}.\n" .
                                                 "Perkiraan kondisi lalu lintas: {$trafficDesc}.";

                                        mysqli_stmt_bind_param(
                                            $stmtItem,
                                            "iisssis",
                                            $new_itinerary_id,
                                            $dest['destination_id'],
                                            $visit_date_str,
                                            $start_time,
                                            $end_time,
                                            $order_number,
                                            $notes
                                        );
                                        mysqli_stmt_execute($stmtItem);
                                    }
                                }

                                mysqli_stmt_close($stmtItem);

                                // Selesai, redirect ke detail itinerary baru
                                header("Location: itinerary-detail.php?itinerary_id=" . $new_itinerary_id);
                                exit;
                            }
                        } else {
                            $pesan_error = "Gagal menyimpan itinerary: " . mysqli_error($koneksi);
                            mysqli_stmt_close($stmtIt);
                        }
                    }
                }
            }
        }
    }
}

// helper untuk cek kategori yang sudah dipilih
function is_cat_checked($cat_id, $selected_cat) {
    return in_array($cat_id, array_map('intval', $selected_cat), true);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Auto Itinerary - Smart Tour Bandung</title>
  <meta name="description" content="Generate itinerary otomatis berdasarkan preferensi.">
  <meta name="keywords" content="auto itinerary, bandung, smart tour bandung">

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
          <li><a href="about.html">About</a></li>
          <li><a href="contact.html">Contact</a></li>
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
        <h1>Auto Itinerary</h1>
        <p>Generate itinerary otomatis berdasarkan preferensi, dengan simulasi cuaca & lalu lintas per hari.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="itineraries.php">My Itineraries</a></li>
            <li class="current">Auto Itinerary</li>
          </ol>
        </nav>
      </div>
    </div>
    <!-- End Page Title -->

    <section class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="mb-3">
          <a href="itineraries.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar itinerary
          </a>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-4 p-lg-5">

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h2 class="h4 mb-0">
                    <i class="bi bi-magic me-1"></i> Generate Itinerary Otomatis
                  </h2>
                  <span class="badge bg-primary-subtle text-primary form-helper-badge">
                    <i class="bi bi-cloud-sun me-1"></i> Simulasi cuaca & traffic per hari
                  </span>
                </div>

                <?php if ($pesan_error): ?>
                  <div class="alert alert-danger py-2">
                    <?php echo htmlspecialchars($pesan_error); ?>
                  </div>
                <?php endif; ?>

                <form action="itinerary-auto.php" method="post">

                  <!-- Judul -->
                  <div class="mb-3">
                    <label for="title" class="form-label">Judul Itinerary <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="title"
                      id="title"
                      class="form-control"
                      required
                      placeholder="Contoh: 3D2N Bandung Coffee & Kuliner Trip"
                      value="<?php echo htmlspecialchars($title); ?>">
                  </div>

                  <!-- Tanggal -->
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control"
                        required
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
                      <div class="form-text small">
                        Jika dikosongkan, sistem anggap perjalanan 1 hari.
                      </div>
                    </div>
                  </div>

                  <!-- Kategori -->
                  <div class="mb-3">
                    <label class="form-label">Pilih Kategori Destinasi (opsional)</label>
                    <div class="row">
                      <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                          <div class="col-md-6">
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                name="categories[]"
                                id="cat_<?php echo (int)$cat['category_id']; ?>"
                                value="<?php echo (int)$cat['category_id']; ?>"
                                <?php echo is_cat_checked($cat['category_id'], $selected_cat) ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="cat_<?php echo (int)$cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                              </label>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <p class="text-muted small">Belum ada kategori destinasi di database.</p>
                      <?php endif; ?>
                    </div>
                    <div class="form-text small">
                      Jika tidak memilih apa pun, sistem akan mengambil destinasi dari semua kategori.
                    </div>
                  </div>

                  <!-- Destinasi per hari -->
                  <div class="mb-3">
                    <label for="max_per_day" class="form-label">Jumlah destinasi per hari</label>
                    <input
                      type="number"
                      name="max_per_day"
                      id="max_per_day"
                      class="form-control"
                      min="1" max="10"
                      value="<?php echo (int)$max_per_day; ?>">
                    <div class="form-text small">
                      Rekomendasi 2–4 destinasi per hari agar tidak terlalu padat.
                    </div>
                  </div>

                  <!-- Preferensi -->
                  <div class="mb-3">
                    <label for="preferences" class="form-label">Preferensi Perjalanan (opsional)</label>
                    <textarea
                      name="preferences"
                      id="preferences"
                      rows="4"
                      class="form-control"
                      placeholder="Contoh:
- Suka coffee shop & kuliner malam
- Bawa anak kecil, hindari trek terlalu jauh
- Lebih suka area Lembang & Dago, hindari macet kota saat jam pulang kerja"><?php echo htmlspecialchars($preferences); ?></textarea>
                    <div class="form-text small">
                      Preferensi ini disimpan di catatan itinerary dan bisa kamu perhatikan saat mengedit manual.
                    </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="itineraries.php" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-magic me-1"></i> Generate Itinerary
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
