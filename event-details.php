<?php
session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';

// Ambil slug event dari URL
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
  http_response_code(404);
  $error_message = "Event tidak ditemukan.";
}

$event = null;

if (empty($error_message)) {
  $sql = "
    SELECT 
      e.event_id,
      e.name,
      e.slug,
      e.description,
      e.location_text,
      e.destination_id,
      e.start_datetime,
      e.end_datetime,
      e.price_min,
      e.price_max,
      e.image_cover,
      d.name      AS dest_name,
      d.slug      AS dest_slug,
      d.latitude  AS dest_lat,
      d.longitude AS dest_lng,
      d.address   AS dest_address
    FROM events e
    LEFT JOIN destinations d
      ON e.destination_id = d.destination_id
    WHERE e.slug = ?
    LIMIT 1
  ";

  $stmt = mysqli_prepare($koneksi, $sql);
  if (!$stmt) {
    die('Query error: ' . mysqli_error($koneksi));
  }

  mysqli_stmt_bind_param($stmt, 's', $slug);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $event = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  if (!$event) {
    http_response_code(404);
    $error_message = "Event tidak ditemukan.";
  }
}

// Helper: format tanggal & harga
function format_tanggal_jam($datetimeStr) {
  if (!$datetimeStr) return '-';
  $dt = new DateTime($datetimeStr);
  // contoh: 02 Feb 2025 14:00
  return $dt->format('d M Y H:i');
}

function format_harga_event($min, $max) {
  if ($min === null && $max === null) {
    return 'Lihat informasi resmi';
  }
  if ((int)$min === 0 && (int)$max === 0) {
    return 'Gratis';
  }

  if ($min !== null && $max !== null && $min != $max) {
    return 'Rp ' . number_format($min, 0, ',', '.') . ' - Rp ' . number_format($max, 0, ',', '.');
  }

  // Jika hanya satu nilai, pakai salah satunya
  $val = $min ?? $max;
  return 'Mulai Rp ' . number_format($val, 0, ',', '.');
}
?>

<?php
// ==== HEADER (sudah include DOCTYPE, <head>, <body>, navbar, dll) ====
$pageTitle = !empty($event)
  ? htmlspecialchars($event['name']) . ' - Smart Tour Bandung'
  : 'Event Not Found - Smart Tour Bandung';

include 'header.php';
?>

<main class="main">

  <?php if (!empty($error_message)): ?>

    <!-- Halaman Not Found -->
    <div class="page-title dark-background" data-aos="fade"
         style="background-image: url(assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>Event Not Found</h1>
        <p><?= htmlspecialchars($error_message); ?></p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="events.php">Events</a></li>
            <li class="current">Not Found</li>
          </ol>
        </nav>
      </div>
    </div>

    <section class="section">
      <div class="container">
        <a href="events.php" class="btn btn-primary">
          <i class="bi bi-arrow-left"></i> Kembali ke daftar event
        </a>
      </div>
    </section>

  <?php else: ?>

    <?php
      // Tentukan gambar hero
      $heroImg = $event['image_cover'] ?: 'assets/img/travel/showcase-8.webp';
    ?>

    <!-- HERO EVENT -->
 <div class="page-title dark-background"
     data-aos="fade"
     style="background-image: url('<?= htmlspecialchars($heroImg); ?>'); padding-top:120px;">

  <div class="container position-relative text-center">

    <!-- Tombol back -->
    <div class="mb-3">
      <a href="events.php"
         class="btn btn-light rounded-pill px-3 shadow-sm"
         style="font-weight:500;">
        <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Events
      </a>
    </div>

    <!-- Nama Event -->
    <h1 class="text-white mb-3">
      <?= htmlspecialchars($event['name']); ?>
    </h1>

    <!-- Lokasi + Waktu + Harga di tengah -->
    <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-3 mb-3 small">

      <!-- Lokasi -->
      <?php if (!empty($event['location_text'])): ?>
        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
          <i class="bi bi-geo-alt me-1"></i>
          <?= htmlspecialchars($event['location_text']); ?>
        </span>
      <?php endif; ?>

      <!-- Waktu -->
      <span class="text-white-50 d-flex align-items-center">
        <i class="bi bi-calendar-event me-1"></i>
        <?= format_tanggal_jam($event['start_datetime']); ?>
        <?php if (!empty($event['end_datetime'])): ?>
          &mdash; <?= format_tanggal_jam($event['end_datetime']); ?>
        <?php endif; ?>
      </span>

      <!-- Harga -->
      <span class="text-white-50 d-flex align-items-center">
        <i class="bi bi-ticket-perforated me-1"></i>
        <?= htmlspecialchars(format_harga_event($event['price_min'], $event['price_max'])); ?>
      </span>

    </div>

    <!-- Destinasi terkait (kalau ada) -->
    <?php if (!empty($event['dest_name'])): ?>
      <p class="text-white-50 mb-0">
        <i class="bi bi-pin-map-fill me-1"></i>
        Destinasi terkait:
        <a href="destination-details.php?slug=<?= urlencode($event['dest_slug']); ?>"
           class="text-white text-decoration-underline">
          <?= htmlspecialchars($event['dest_name']); ?>
        </a>
      </p>
    <?php endif; ?>

  </div>
</div>

    <!-- END HERO -->

    <!-- DETAIL EVENT -->
    <section class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <!-- Deskripsi -->
            <div class="mb-4">
              <h3>Deskripsi Event</h3>
              <?php if (!empty($event['description'])): ?>
                <p><?= nl2br(htmlspecialchars($event['description'])); ?></p>
              <?php else: ?>
                <p class="text-muted">Belum ada deskripsi rinci untuk event ini.</p>
              <?php endif; ?>
            </div>
          <!-- KONTEN KIRI -->
          <div class="col-lg-8">

  

            <!-- Info Waktu & Harga -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100">
                  <h5 class="mb-2"><i class="bi bi-calendar-event me-1"></i> Waktu Pelaksanaan</h5>
                  <p class="mb-1">
                    <strong>Mulai:</strong><br>
                    <?= format_tanggal_jam($event['start_datetime']); ?>
                  </p>
                  <?php if (!empty($event['end_datetime'])): ?>
                    <p class="mb-0">
                      <strong>Selesai:</strong><br>
                      <?= format_tanggal_jam($event['end_datetime']); ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100">
                  <h5 class="mb-2"><i class="bi bi-cash-stack me-1"></i> Kisaran Harga</h5>
                  <p class="mb-0 fw-semibold text-success">
                    <?= htmlspecialchars(format_harga_event($event['price_min'], $event['price_max'])); ?>
                  </p>
                  <small class="text-muted">
                    *Harga dapat berubah, pastikan cek informasi resmi penyelenggara.
                  </small>
                </div>
              </div>
            </div>

            <!-- Tambah ke Itinerary -->
            <div class="mb-4">
              <h3 class="h5 mb-3">
                <i class="bi bi-diagram-3 me-1"></i> Tambahkan ke Itinerary
              </h3>
              <p class="small mb-3">
                Ingin memasukkan event ini ke rencana perjalananmu di Bandung? Tambahkan ke itinerary baru
                atau kelola itinerary yang sudah ada.
              </p>
              <div class="d-flex flex-wrap gap-2">
                <!-- Parameter ini bisa kamu tangani di itinerary-create.php -->
                <a href="itinerary-create.php?add_event_id=<?= (int)$event['event_id']; ?>"
                   class="btn btn-primary btn-sm">
                  <i class="bi bi-plus-circle me-1"></i> Buat Itinerary Baru dengan Event Ini
                </a>
                <a href="itineraries.php" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-list-check me-1"></i> Kelola Itinerary Saya
                </a>
              </div>
            </div>

          </div>

          <!-- KONTEN KANAN (MAP & INFO) -->
         <!-- Lokasi Event -->
<!-- KONTEN KANAN -->
<div class="col-lg-4">

  <!-- Lokasi Event -->
  <div class="mb-4">
    <div class="p-3 border rounded-3 h-100">
      <h5 class="mb-3">
        <i class="bi bi-geo-alt-fill me-1"></i> Lokasi Event
      </h5>

      <?php if (!empty($event['dest_lat']) && !empty($event['dest_lng'])): ?>
        <?php
          $lat = (float)$event['dest_lat'];
          $lng = (float)$event['dest_lng'];
          $mapSrc = "https://www.google.com/maps?q={$lat},{$lng}&hl=id&z=15&output=embed";
        ?>
        <div class="ratio ratio-4x3 mb-2 rounded overflow-hidden">
          <iframe
            src="<?= htmlspecialchars($mapSrc); ?>"
            style="border:0;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <a href="https://www.google.com/maps/search/?api=1&query=<?= $lat; ?>,<?= $lng; ?>"
           target="_blank" class="btn btn-outline-primary btn-sm w-100">
          <i class="bi bi-map me-1"></i> Buka di Google Maps
        </a>

      <?php else: ?>
        <p class="text-muted mb-2">Koordinat belum tersedia. Lokasi:</p>

        <?php if (!empty($event['location_text'])): ?>
          <p class="mb-0 fw-semibold">
            <i class="bi bi-geo-alt me-1"></i>
            <?= htmlspecialchars($event['location_text']); ?>
          </p>
        <?php else: ?>
          <p class="mb-0 text-muted">Lokasi belum diisi.</p>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>

  <!-- Destinasi Terkait -->
  <?php if (!empty($event['dest_name'])): ?>
    <div class="mb-4">
      <div class="p-3 border rounded-3 h-100">
        <h5 class="mb-3">
          <i class="bi bi-pin-map-fill me-1"></i> Destinasi Terkait
        </h5>

        <p class="mb-1 fw-semibold"><?= htmlspecialchars($event['dest_name']); ?></p>

        <?php if (!empty($event['dest_address'])): ?>
          <p class="mb-2 small">
            <i class="bi bi-geo-alt me-1"></i>
            <?= htmlspecialchars($event['dest_address']); ?>
          </p>
        <?php endif; ?>

        <a href="destination-details.php?slug=<?= urlencode($event['dest_slug']); ?>"
           class="btn btn-outline-primary btn-sm w-100">
          Lihat Detail Destinasi
        </a>
      </div>
    </div>
  <?php endif; ?>

</div>



        </div>
      </div>
    </section>

  <?php endif; ?>

</main>

<?php include 'footer.php'; ?>
