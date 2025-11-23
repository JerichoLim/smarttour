<?php
session_start();
require 'koneksi.php';

// ======================
// Wajib login
// ======================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Traveler';

// Ambil itinerary_id dari query string
$itinerary_id = (int)($_GET['itinerary_id'] ?? 0);
if ($itinerary_id <= 0) {
    header("Location: itineraries.php");
    exit;
}

$pesan_error_item  = '';
$pesan_sukses_item = '';

// ======================
// Ambil data itinerary (cek kepemilikan)
// ======================
$sqlIt = "
    SELECT itinerary_id, id_user, title, start_date, end_date, preferences, total_cost, created_at
    FROM itineraries
    WHERE itinerary_id = ? AND id_user = ?
    LIMIT 1
";
$stmtIt = mysqli_prepare($koneksi, $sqlIt);
if (!$stmtIt) {
    die("Query error (itinerary): " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmtIt, "ii", $itinerary_id, $id_user);
mysqli_stmt_execute($stmtIt);
$resIt = mysqli_stmt_get_result($stmtIt);
$itinerary = mysqli_fetch_assoc($resIt);
mysqli_stmt_close($stmtIt);

if (!$itinerary) {
    // itinerary tidak ditemukan / bukan milik user
    http_response_code(404);
    echo "Itinerary tidak ditemukan.";
    exit;
}

// ======================
// HANDLE POST (hapus / ubah urutan / tambah item)
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'add';

    // --------------------------
    // HAPUS ITEM
    // --------------------------
    if ($action === 'delete_item') {
        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id > 0) {
            $sqlDel = "DELETE FROM itinerary_items WHERE item_id = ? AND itinerary_id = ? LIMIT 1";
            $stmtDel = mysqli_prepare($koneksi, $sqlDel);
            if ($stmtDel) {
                mysqli_stmt_bind_param($stmtDel, "ii", $item_id, $itinerary_id);
                mysqli_stmt_execute($stmtDel);
                mysqli_stmt_close($stmtDel);
            }
        }

        // redirect agar tidak resubmit
        header("Location: itinerary-detail.php?itinerary_id=" . $itinerary_id);
        exit;
    }

    // --------------------------
    // UBAH URUTAN (NAIK / TURUN)
    // --------------------------
    if ($action === 'move_up' || $action === 'move_down') {
        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id > 0) {
            // Ambil data item saat ini
            $sqlCur = "
                SELECT item_id, order_number
                FROM itinerary_items
                WHERE item_id = ? AND itinerary_id = ?
                LIMIT 1
            ";
            $stmtCur = mysqli_prepare($koneksi, $sqlCur);
            if ($stmtCur) {
                mysqli_stmt_bind_param($stmtCur, "ii", $item_id, $itinerary_id);
                mysqli_stmt_execute($stmtCur);
                $resCur = mysqli_stmt_get_result($stmtCur);
                $cur = mysqli_fetch_assoc($resCur);
                mysqli_stmt_close($stmtCur);

                if ($cur) {
                    $currentOrder = (int)$cur['order_number'];
                    if ($currentOrder <= 0) $currentOrder = 1;

                    if ($action === 'move_up') {
                        $newOrder = $currentOrder - 1;
                        if ($newOrder >= 1) {
                            // Geser item yang ada di posisi newOrder ke bawah (order_number + 1)
                            $sqlSwap1 = "
                              UPDATE itinerary_items
                              SET order_number = order_number + 1
                              WHERE itinerary_id = ? AND order_number = ?
                            ";
                            $stmtSwap1 = mysqli_prepare($koneksi, $sqlSwap1);
                            if ($stmtSwap1) {
                                mysqli_stmt_bind_param($stmtSwap1, "ii", $itinerary_id, $newOrder);
                                mysqli_stmt_execute($stmtSwap1);
                                mysqli_stmt_close($stmtSwap1);
                            }

                            // Set item ini ke order baru
                            $sqlSwap2 = "
                              UPDATE itinerary_items
                              SET order_number = ?
                              WHERE item_id = ?
                            ";
                            $stmtSwap2 = mysqli_prepare($koneksi, $sqlSwap2);
                            if ($stmtSwap2) {
                                mysqli_stmt_bind_param($stmtSwap2, "ii", $newOrder, $item_id);
                                mysqli_stmt_execute($stmtSwap2);
                                mysqli_stmt_close($stmtSwap2);
                            }
                        }
                    } else { // move_down
                        $newOrder = $currentOrder + 1;

                        // Geser item yang ada di posisi newOrder ke atas (order_number - 1)
                        $sqlSwap1 = "
                          UPDATE itinerary_items
                          SET order_number = order_number - 1
                          WHERE itinerary_id = ? AND order_number = ?
                        ";
                        $stmtSwap1 = mysqli_prepare($koneksi, $sqlSwap1);
                        if ($stmtSwap1) {
                            mysqli_stmt_bind_param($stmtSwap1, "ii", $itinerary_id, $newOrder);
                            mysqli_stmt_execute($stmtSwap1);
                            mysqli_stmt_close($stmtSwap1);
                        }

                        // Set item ini ke order baru
                        $sqlSwap2 = "
                          UPDATE itinerary_items
                          SET order_number = ?
                          WHERE item_id = ?
                        ";
                        $stmtSwap2 = mysqli_prepare($koneksi, $sqlSwap2);
                        if ($stmtSwap2) {
                            mysqli_stmt_bind_param($stmtSwap2, "ii", $newOrder, $item_id);
                            mysqli_stmt_execute($stmtSwap2);
                            mysqli_stmt_close($stmtSwap2);
                        }
                    }
                }
            }
        }

        // redirect agar tidak resubmit
        header("Location: itinerary-detail.php?itinerary_id=" . $itinerary_id);
        exit;
    }

    // --------------------------
    // TAMBAH ITEM (DEFAULT)
// --------------------------
    $destination_id = (int)($_POST['destination_id'] ?? 0);
    $visit_date     = trim($_POST['visit_date'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $end_time       = trim($_POST['end_time'] ?? '');
    $order_number   = trim($_POST['order_number'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    // Normalisasi kosong -> null
    if ($visit_date === '') $visit_date = null;
    if ($start_time === '') $start_time = null;
    if ($end_time === '')   $end_time   = null;

    if ($order_number === '') {
        $order_number_val = 1;
    } else {
        $order_number_val = (int)$order_number;
        if ($order_number_val <= 0) $order_number_val = 1;
    }

    // Validasi dasar
    if ($destination_id <= 0) {
        $pesan_error_item = "Destinasi wajib dipilih.";
    } else {
        // Cek destinasi ada
        $sqlDestCheck = "SELECT destination_id FROM destinations WHERE destination_id = ? LIMIT 1";
        $stmtDC = mysqli_prepare($koneksi, $sqlDestCheck);
        if (!$stmtDC) {
            $pesan_error_item = "Terjadi kesalahan sistem (cek destinasi).";
        } else {
            mysqli_stmt_bind_param($stmtDC, "i", $destination_id);
            mysqli_stmt_execute($stmtDC);
            mysqli_stmt_store_result($stmtDC);

            if (mysqli_stmt_num_rows($stmtDC) === 0) {
                $pesan_error_item = "Destinasi tidak ditemukan.";
            }
            mysqli_stmt_close($stmtDC);
        }
    }

    if ($pesan_error_item === '') {
        $sqlInsertItem = "
            INSERT INTO itinerary_items
                (itinerary_id, destination_id, visit_date, start_time, end_time, order_number, notes)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmtIns = mysqli_prepare($koneksi, $sqlInsertItem);
        if (!$stmtIns) {
            $pesan_error_item = "Terjadi kesalahan sistem (insert item).";
        } else {
            mysqli_stmt_bind_param(
                $stmtIns,
                "iisssis",
                $itinerary_id,
                $destination_id,
                $visit_date,
                $start_time,
                $end_time,
                $order_number_val,
                $notes
            );

            if (mysqli_stmt_execute($stmtIns)) {
                mysqli_stmt_close($stmtIns);
                // PRG pattern: redirect supaya tidak re-submit saat refresh
                header("Location: itinerary-detail.php?itinerary_id=" . $itinerary_id);
                exit;
            } else {
                $pesan_error_item = "Gagal menyimpan item itinerary: " . mysqli_error($koneksi);
                mysqli_stmt_close($stmtIns);
            }
        }
    }
}

// ======================
// Ambil destinasi untuk dropdown
// ======================
$destinations = [];
$sqlD = "SELECT destination_id, name FROM destinations ORDER BY name ASC";
$resD = mysqli_query($koneksi, $sqlD);
if ($resD) {
    while ($row = mysqli_fetch_assoc($resD)) {
        $destinations[] = $row;
    }
}

// ======================
// Ambil item itinerary
// ======================
$sqlItems = "
    SELECT it.item_id,
           it.visit_date,
           it.start_time,
           it.end_time,
           it.order_number,
           it.notes,
           d.destination_id,
           d.name AS destination_name,
           d.slug,
           d.address,
           d.image_cover
    FROM itinerary_items it
    JOIN destinations d ON it.destination_id = d.destination_id
    WHERE it.itinerary_id = ?
    ORDER BY
        it.visit_date IS NULL, it.visit_date,
        it.order_number,
        it.start_time
";
$stmtItems = mysqli_prepare($koneksi, $sqlItems);
if (!$stmtItems) {
    die("Query error (items): " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmtItems, "i", $itinerary_id);
mysqli_stmt_execute($stmtItems);
$resItems = mysqli_stmt_get_result($stmtItems);

$items = [];
if ($resItems) {
    while ($row = mysqli_fetch_assoc($resItems)) {
        $items[] = $row;
    }
}
mysqli_stmt_close($stmtItems);

// ======================
// Helper
// ======================
function fmt_date($date) {
    if ($date === null || $date === '0000-00-00' || $date === '') return '-';
    $dt = new DateTime($date);
    return $dt->format('d M Y');
}

function fmt_time($time) {
    if ($time === null || $time === '' || $time === '00:00:00') return '';
    return substr($time, 0, 5); // HH:MM
}

function fmt_rupiah($angka) {
    if ($angka === null) return '-';
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo htmlspecialchars($itinerary['title']); ?> - Itinerary Detail - Smart Tour Bandung</title>
  <meta name="description" content="Detail itinerary perjalanan Anda di Bandung">
  <meta name="keywords" content="itinerary, bandung, wisata, smart tour bandung">

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
    .itinerary-header-card {
      border-radius: 1rem;
    }
    .itinerary-day-title {
      border-left: 4px solid #0d6efd;
      padding-left: 0.75rem;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    .itinerary-item-card {
      border-radius: 0.75rem;
    }
    .destination-thumb {
      width: 90px;
      height: 80px;
      object-fit: cover;
      border-radius: 0.75rem;
    }
    .item-actions-form {
      display:inline-block;
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
          <li><a href="gallery.php">Gallery</a></li>
          <li class="dropdown"><a href="#"><span>More</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="faq.html">FAQ</a></li>
              <li><a href="terms.html">Terms</a></li>
              <li><a href="privacy.html">Privacy</a></li>
            </ul>
          </li>
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
        <h1>Itinerary Detail</h1>
        <p>Rincian rencana perjalanan Anda di Bandung.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="itineraries.php">My Itineraries</a></li>
            <li class="current">Detail</li>
          </ol>
        </nav>
      </div>
    </div>
    <!-- End Page Title -->

    <section class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <a href="itineraries.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar itinerary
          </a>
          
          <div class="d-flex gap-2">
            <!-- Download PDF Button -->
            <a href="itinerary-download.php?itinerary_id=<?php echo $itinerary_id; ?>" target="_blank" class="btn btn-success btn-sm">
              <i class="bi bi-file-pdf me-1"></i> Download PDF
            </a>
            
            <!-- Delete Itinerary Button -->
            <form action="itinerary-delete.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh itinerary ini? Semua destinasi di dalamnya akan ikut terhapus dan tindakan ini tidak dapat dibatalkan.');" class="d-inline">
              <input type="hidden" name="itinerary_id" value="<?php echo $itinerary_id; ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Hapus Itinerary
              </button>
            </form>
          </div>
        </div>

        <div class="row gy-4">

          <!-- Kolom Kiri: Detail & Item -->
          <div class="col-lg-8">

            <!-- Card Header Itinerary -->
            <div class="card shadow-sm itinerary-header-card mb-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                  <div>
                    <h2 class="h4 mb-1">
                      <i class="bi bi-map me-1"></i>
                      <?php echo htmlspecialchars($itinerary['title']); ?>
                    </h2>
                    <p class="mb-1 small text-muted">
                      <i class="bi bi-calendar-range me-1"></i>
                      <?php echo fmt_date($itinerary['start_date']); ?> - <?php echo fmt_date($itinerary['end_date']); ?>
                    </p>
                    <p class="mb-0 small text-muted">
                      <i class="bi bi-clock-history me-1"></i>
                      Dibuat:
                      <?php
                        $dtCreated = new DateTime($itinerary['created_at']);
                        echo $dtCreated->format('d M Y H:i');
                      ?>
                    </p>
                  </div>
                  <div class="text-end">
                    <p class="mb-1 small text-muted">
                      Estimasi biaya total:
                    </p>
                    <p class="mb-0 fw-semibold text-success">
                      <?php echo fmt_rupiah($itinerary['total_cost']); ?>
                    </p>
                  </div>
                </div>

                <?php if (!empty($itinerary['preferences'])): ?>
                  <hr class="my-3">
                  <p class="mb-0 small">
                    <strong>Preferensi Pengguna:</strong><br>
                    <?php echo nl2br(htmlspecialchars($itinerary['preferences'])); ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Daftar Item Itinerary -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h5 mb-0">Rencana Kunjungan</h3>
                <small class="text-muted">
                  Total destinasi: <strong><?php echo count($items); ?></strong>
                </small>
              </div>

              <?php if (empty($items)): ?>
                <div class="alert alert-info">
                  Anda belum menambahkan destinasi ke itinerary ini. Gunakan form di sebelah kanan untuk menambahkan.
                </div>
              <?php else: ?>
                <?php
                  $currentDay = null;
                  foreach ($items as $it) {
                    $dayLabel = $it['visit_date'] ? fmt_date($it['visit_date']) : 'Tanpa tanggal spesifik';
                    if ($dayLabel !== $currentDay) {
                      if ($currentDay !== null) {
                        echo '<hr class="my-3">';
                      }
                      echo '<div class="itinerary-day-title mb-2"><i class="bi bi-calendar-week me-1"></i>' . htmlspecialchars($dayLabel) . '</div>';
                      $currentDay = $dayLabel;
                    }
                ?>
                  <div class="card border-0 shadow-sm mb-2 itinerary-item-card">
                    <div class="card-body d-flex">
                      <div class="me-3 d-none d-md-block">
                        <?php
                          $thumb = $it['image_cover'] ?: 'assets/img/travel/destination-3.webp';
                        ?>
                        <img src="<?php echo htmlspecialchars($thumb); ?>"
                             alt="<?php echo htmlspecialchars($it['destination_name']); ?>"
                             class="destination-thumb">
                      </div>
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                          <div>
                            <a href="destination-details.php?slug=<?php echo urlencode($it['slug']); ?>"
                               class="fw-semibold text-decoration-none">
                              <?php echo htmlspecialchars($it['destination_name']); ?>
                            </a>
                            <div class="small text-muted">
                              <i class="bi bi-geo-alt me-1"></i>
                              <?php echo htmlspecialchars($it['address'] ?? '-'); ?>
                            </div>
                            <?php if (!empty($it['notes'])): ?>
                              <div class="small mt-1">
                                <i class="bi bi-chat-left-text me-1"></i>
                                <?php echo nl2br(htmlspecialchars($it['notes'])); ?>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="text-end small text-muted">
                            <?php if ($it['start_time'] || $it['end_time']): ?>
                              <div>
                                <i class="bi bi-clock me-1"></i>
                                <?php echo fmt_time($it['start_time']); ?>
                                <?php if ($it['end_time']): ?>
                                  - <?php echo fmt_time($it['end_time']); ?>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                            <div class="mt-1">
                              <span class="badge bg-secondary-subtle text-secondary mb-1">
                                Urutan #<?php echo (int)$it['order_number']; ?>
                              </span>
                              <div>
                                <!-- Tombol naik -->
                                <form method="post"
                                      action="itinerary-detail.php?itinerary_id=<?php echo $itinerary_id; ?>"
                                      class="item-actions-form">
                                  <input type="hidden" name="action" value="move_up">
                                  <input type="hidden" name="item_id" value="<?php echo (int)$it['item_id']; ?>">
                                  <button type="submit"
                                          class="btn btn-outline-secondary btn-sm"
                                          title="Naikkan urutan">
                                    <i class="bi bi-arrow-up"></i>
                                  </button>
                                </form>

                                <!-- Tombol turun -->
                                <form method="post"
                                      action="itinerary-detail.php?itinerary_id=<?php echo $itinerary_id; ?>"
                                      class="item-actions-form">
                                  <input type="hidden" name="action" value="move_down">
                                  <input type="hidden" name="item_id" value="<?php echo (int)$it['item_id']; ?>">
                                  <button type="submit"
                                          class="btn btn-outline-secondary btn-sm"
                                          title="Turunkan urutan">
                                    <i class="bi bi-arrow-down"></i>
                                  </button>
                                </form>

                                <!-- Hapus -->
                                <form method="post"
                                      action="itinerary-detail.php?itinerary_id=<?php echo $itinerary_id; ?>"
                                      class="item-actions-form"
                                      onsubmit="return confirm('Hapus destinasi ini dari itinerary?');">
                                  <input type="hidden" name="action" value="delete_item">
                                  <input type="hidden" name="item_id" value="<?php echo (int)$it['item_id']; ?>">
                                  <button type="submit"
                                          class="btn btn-outline-danger btn-sm"
                                          title="Hapus item">
                                    <i class="bi bi-trash"></i>
                                  </button>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } // end foreach ?>
              <?php endif; ?>
            </div>

          </div>
          <!-- End Kolom Kiri -->

          <!-- Kolom Kanan: Form tambah item -->
          <div class="col-lg-4">

            <div class="card shadow-sm border-0 mb-4">
              <div class="card-body">
                <h3 class="h6 mb-3">
                  <i class="bi bi-plus-circle me-1"></i>
                  Tambah Destinasi ke Itinerary
                </h3>

                <?php if ($pesan_error_item): ?>
                  <div class="alert alert-danger py-2">
                    <?php echo htmlspecialchars($pesan_error_item); ?>
                  </div>
                <?php endif; ?>

                <?php if ($pesan_sukses_item): ?>
                  <div class="alert alert-success py-2">
                    <?php echo htmlspecialchars($pesan_sukses_item); ?>
                  </div>
                <?php endif; ?>

                <form action="itinerary-detail.php?itinerary_id=<?php echo $itinerary_id; ?>" method="post">
                  <input type="hidden" name="action" value="add">

                  <div class="mb-3">
                    <label for="destination_id" class="form-label">Pilih Destinasi</label>
                    <select name="destination_id" id="destination_id" class="form-select" required>
                      <option value="">-- Pilih destinasi --</option>
                      <?php foreach ($destinations as $d): ?>
                        <option value="<?php echo (int)$d['destination_id']; ?>">
                          <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label for="visit_date" class="form-label">Tanggal Kunjungan (opsional)</label>
                    <input type="date" name="visit_date" id="visit_date" class="form-control"
                           value="<?php echo htmlspecialchars($itinerary['start_date'] ?? ''); ?>">
                    <div class="form-text small">
                      Kosongkan jika belum menentukan tanggal pasti.
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="start_time" class="form-label">Jam Mulai (opsional)</label>
                      <input type="time" name="start_time" id="start_time" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="end_time" class="form-label">Jam Selesai (opsional)</label>
                      <input type="time" name="end_time" id="end_time" class="form-control">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label for="order_number" class="form-label">Urutan Kunjungan (opsional)</label>
                    <input type="number" name="order_number" id="order_number" class="form-control" min="1"
                           placeholder="1, 2, 3, ...">
                    <div class="form-text small">
                      Kosongkan untuk default (1).
                    </div>
                  </div>

                  <div class="mb-3">
                    <label for="notes" class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" id="notes" rows="2" class="form-control"
                              placeholder="Misal: cari tempat parkir dekat pintu masuk, cocok untuk brunch, dsb."></textarea>
                  </div>

                  <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-plus-lg me-1"></i> Tambahkan
                    </button>
                  </div>

                </form>

              </div>
            </div>

            <div class="card shadow-sm border-0">
              <div class="card-body">
                <h3 class="h6 mb-2">
                  <i class="bi bi-lightbulb me-1"></i>
                  Tips Menyusun Itinerary
                </h3>
                <ul class="small mb-0">
                  <li>Kelompokkan destinasi yang berdekatan untuk mengurangi waktu di jalan.</li>
                  <li>Perhatikan jam buka/tutup destinasi dan waktu puncak kemacetan Bandung.</li>
                  <li>Atur waktu makan dan istirahat di sela kunjungan.</li>
                  <li>Gunakan detail destinasi untuk melihat peta dan review pengguna lain.</li>
                </ul>
              </div>
            </div>

          </div>
          <!-- End Kolom Kanan -->

        </div>

      </div>
    </section>

  </main>

  <!-- Footer -->
<?php include 'footer.php'; ?>
