<?php
session_start();
require 'koneksi.php';
require 'csrf.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';

// Ambil slug
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
  http_response_code(404);
  $error_message = "Destinasi tidak ditemukan.";
}

// Ambil data destinasi
if (empty($error_message)) {
  $sqlDest = "
    SELECT d.destination_id, d.name, d.slug, d.description, d.address,
           d.latitude, d.longitude, d.opening_hours, d.ticket_price, d.image_cover,
           c.name AS category_name
    FROM destinations d
    LEFT JOIN destination_categories c ON d.category_id = c.category_id
    WHERE d.slug = ?
    LIMIT 1
  ";

  $stmtDest = mysqli_prepare($koneksi, $sqlDest);
  if (!$stmtDest) {
    die('Query error: ' . mysqli_error($koneksi));
  }
  mysqli_stmt_bind_param($stmtDest, "s", $slug);
  mysqli_stmt_execute($stmtDest);
  $resDest = mysqli_stmt_get_result($stmtDest);
  $destination = mysqli_fetch_assoc($resDest);
  mysqli_stmt_close($stmtDest);

  if (!$destination) {
    http_response_code(404);
    $error_message = "Destinasi tidak ditemukan.";
  }
}

$images      = [];
$reviews     = [];
$avgRating   = null;
$reviewCount = 0;

if (empty($error_message)) {
  $destination_id = (int)$destination['destination_id'];

  // Ambil foto
  $stmtImg = mysqli_prepare(
    $koneksi,
    "SELECT image_path, caption
     FROM destination_images
     WHERE destination_id = ?
     ORDER BY image_id ASC"
  );
  mysqli_stmt_bind_param($stmtImg, "i", $destination_id);
  mysqli_stmt_execute($stmtImg);
  $resImg = mysqli_stmt_get_result($stmtImg);
  while ($row = mysqli_fetch_assoc($resImg)) {
    $images[] = $row;
  }
  mysqli_stmt_close($stmtImg);

  // Ambil review
  $stmtRev = mysqli_prepare(
    $koneksi,
    "SELECT r.rating, r.review_text, r.created_at, u.username
     FROM destination_reviews r
     JOIN user_tbl u ON r.id_user = u.id_user
     WHERE r.destination_id = ?
     ORDER BY r.created_at DESC"
  );
  mysqli_stmt_bind_param($stmtRev, "i", $destination_id);
  mysqli_stmt_execute($stmtRev);
  $resRev = mysqli_stmt_get_result($stmtRev);

  $ratingSum = 0;
  while ($row = mysqli_fetch_assoc($resRev)) {
    $reviews[] = $row;
    $ratingSum += (int)$row['rating'];
  }
  mysqli_stmt_close($stmtRev);

  $reviewCount = count($reviews);
  if ($reviewCount > 0) {
    $avgRating = round($ratingSum / $reviewCount, 1);
  }
}

function format_rupiah($angka) {
  return ($angka === null)
    ? null
    : "Rp " . number_format($angka, 0, ',', '.');
}

// ====== TITLE & BODY CLASS UNTUK HEADER ======
$pageTitle = !empty($destination)
  ? htmlspecialchars($destination['name']) . ' - Smart Tour Bandung'
  : 'Destination Not Found - Smart Tour Bandung';

$bodyClass = 'destination-details-page';

include 'header.php'; // <html>, <head>, <body>, navbar sudah dari sini
?>

<style>
  /* CSS kecil khusus halaman ini */
  .gallery-img {
    height: 170px;
    object-fit: cover;
    width: 100%;
    border-radius: 0.75rem;
  }
</style>

<main class="main">

  <?php if (!empty($error_message)): ?>

    <!-- Halaman Not Found -->
    <div class="page-title dark-background" data-aos="fade"
         style="background-image: url(assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>Destination Not Found</h1>
        <p><?= htmlspecialchars($error_message); ?></p>
        <a href="destinations.php" class="btn btn-light rounded-pill mt-3">
          <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Destinations
        </a>
      </div>
    </div>

  <?php else: ?>

    <?php
      // Tentukan gambar hero
      $hero = $destination['image_cover'];
      if (!$hero && !empty($images)) $hero = $images[0]['image_path'];
      if (!$hero) $hero = 'assets/img/travel/destination-3.webp';
    ?>

    <!-- HERO / PAGE TITLE (AMAN DARI NAVBAR KARENA PAKAI KELAS page-title) -->
<!-- HERO (CENTERED STYLE) -->
<div class="page-title dark-background"
     data-aos="fade"
     style="background-image: url('<?= htmlspecialchars($hero); ?>'); padding-top:120px;">

  <div class="container position-relative text-center">

    <!-- Tombol back -->
    <div class="mb-3">
      <a href="destinations.php"
         class="btn btn-light rounded-pill px-3 shadow-sm"
         style="font-weight:500;">
        <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Destinations
      </a>
    </div>

    <!-- Nama Destinasi -->
    <h1 class="text-white mb-3">
      <?= htmlspecialchars($destination['name']); ?>
    </h1>

    <!-- Kategori + Rating di tengah -->
    <div class="d-flex justify-content-center align-items-center gap-3 mb-3">

      <!-- Kategori -->
      <?php if (!empty($destination['category_name'])): ?>
        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
          <i class="bi bi-tag me-1"></i>
          <?= htmlspecialchars($destination['category_name']); ?>
        </span>
      <?php endif; ?>

      <!-- Rating -->
      <?php if ($avgRating !== null): ?>
        <span class="text-warning small d-flex align-items-center">
          <i class="bi bi-star-fill me-1"></i>
          <?= $avgRating; ?> (<?= $reviewCount; ?> review)
        </span>
      <?php else: ?>
        <span class="text-light small opacity-75">Belum ada review</span>
      <?php endif; ?>

    </div>

    <!-- Alamat -->
    <?php if (!empty($destination['address'])): ?>
      <p class="text-white-50 mb-4">
        <i class="bi bi-geo-alt me-1"></i>
        <?= htmlspecialchars($destination['address']); ?>
      </p>
    <?php endif; ?>

  </div>
</div>

    <!-- END HERO -->

    <!-- KONTEN UTAMA -->
    <section class="section">
      <div class="container">
        <div class="row gy-4">

          <!-- LEFT SIDE -->
          <div class="col-lg-8">
	
            <!-- Deskripsi -->
            <div class="mb-4">
              <h3>Deskripsi</h3>
              <?php if (!empty($destination['description'])): ?>
                <p><?= nl2br(htmlspecialchars($destination['description'])); ?></p>
              <?php else: ?>
                <p class="text-muted">Belum ada deskripsi rinci untuk destinasi ini.</p>
              <?php endif; ?>
            </div>

            <!-- Info praktis -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100">
                  <h5 class="mb-2">
                    <i class="bi bi-clock me-1"></i> Jam Operasional
                  </h5>
                  <?php if (!empty($destination['opening_hours'])): ?>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($destination['opening_hours'])); ?></p>
                  <?php else: ?>
                    <p class="mb-0 text-muted">
                      Jam buka belum tersedia, silakan cek info terbaru di lokasi atau media resmi.
                    </p>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100">
                  <h5 class="mb-2">
                    <i class="bi bi-ticket-perforated me-1"></i> Perkiraan Tiket Masuk
                  </h5>
                  <?php if ($destination['ticket_price'] !== null): ?>
                    <p class="mb-0 fw-semibold text-success">
                      <?= format_rupiah($destination['ticket_price']); ?>
                    </p>
                    <small class="text-muted">
                      *Harga bisa berubah, cek kembali sebelum berkunjung.
                    </small>
                  <?php else: ?>
                    <p class="mb-0 text-muted">Data tiket belum tersedia.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Galeri -->
            <div class="mb-5">
              <h3 class="mb-3">Galeri Foto</h3>
              <?php if (empty($images)): ?>
                <p class="text-muted">Belum ada foto tambahan untuk destinasi ini.</p>
            </div>

            <!-- Review -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="mb-0">Review Pengguna</h3>
                <?php if ($avgRating !== null): ?>
                  <div class="text-warning small">
                    <i class="bi bi-star-fill me-1"></i><?= $avgRating; ?>
                    <span class="text-muted ms-1">(<?= $reviewCount; ?> review)</span>
                  </div>
                <?php endif; ?>
              </div>

              <?php if (empty($reviews)): ?>
                <p class="text-muted">Belum ada review. Jadilah yang pertama!</p>
              <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                  <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <strong><?= htmlspecialchars($rev['username']); ?></strong>
                      <small class="text-muted">
                        <?php
                          $dt = new DateTime($rev['created_at']);
                          echo $dt->format('d M Y H:i');
                        ?>
                      </small>
                    </div>
                    <div class="text-warning mb-1">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?= $i <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                      <?php endfor; ?>
                    </div>
                    <?php if (!empty($rev['review_text'])): ?>
                      <p class="mb-0 small">
                        <?= nl2br(htmlspecialchars($rev['review_text'])); ?>
                      </p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
                      <option value="">Pilih rating</option>
                      <option value="5">5 - Sangat puas</option>
                      <option value="4">4 - Puas</option>
                      <option value="3">3 - Cukup</option>
                      <option value="2">2 - Kurang</option>
                      <option value="1">1 - Tidak puas</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="review_text">Review</label>
                    <textarea name="review_text" id="review_text" rows="3"
                              class="form-control"
                              placeholder="Ceritakan pengalaman Anda di destinasi ini..."
                              required></textarea>
                  </div>

                  <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-1"></i> Kirim Review
                    </button>
                  </div>
                </form>
              </div>

            </div>
          </div>
          <!-- END LEFT SIDE -->

          <!-- RIGHT SIDE -->
          <div class="col-lg-4">
            <!-- Map -->
            <div class="card shadow-sm mb-4">
              <div class="card-body">
                <h5 class="card-title mb-3">
                  <i class="bi bi-geo-alt-fill me-1"></i> Lokasi di Peta
                </h5>
                <?php if ($destination['latitude'] !== null && $destination['longitude'] !== null): ?>
                  <div class="ratio ratio-4x3 mb-2">
                    <iframe
                      src="https://www.google.com/maps?q=<?= $destination['latitude']; ?>,<?= $destination['longitude']; ?>&hl=id&z=15&output=embed"
                      style="border:0;"
                      allowfullscreen=""
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"></iframe>
                  </div>
                  <a href="https://www.google.com/maps/search/?api=1&query=<?= $destination['latitude']; ?>,<?= $destination['longitude']; ?>"
                     target="_blank"
                     class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-map me-1"></i> Buka di Google Maps
                  </a>
                <?php else: ?>
                  <p class="text-muted mb-0">Koordinat lokasi belum tersedia.</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Info singkat -->
            <div class="card shadow-sm mb-4">
              <div class="card-body">
                <h5 class="card-title mb-3">
                  <i class="bi bi-info-circle me-1"></i> Info Singkat
                </h5>
                <ul class="list-unstyled mb-0 small">
                  <?php if (!empty($destination['category_name'])): ?>
                    <li class="mb-2">
                      <i class="bi bi-folder2-open me-1"></i>
                      Kategori:
                      <strong><?= htmlspecialchars($destination['category_name']); ?></strong>
                    </li>
                  <?php endif; ?>
                  <li class="mb-2">
                    <i class="bi bi-people me-1"></i>
                    Cocok untuk: keluarga, teman, dan wisata santai.
                  </li>
                  <li class="mb-0">
                    <i class="bi bi-lightbulb me-1"></i>
                    Tips: sesuaikan itinerary dengan cuaca dan kemacetan Bandung.
                  </li>
                </ul>
              </div>
            </div>

            <!-- Itinerary CTA -->
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3">
                  <i class="bi bi-diagram-3 me-1"></i> Tambahkan ke Itinerary
                </h5>
                <p class="small mb-3">
                  Jadikan destinasi ini bagian dari perjalananmu di Bandung.
                </p>
                <a href="itinerary-create.php?add_slug=<?= urlencode($destination['slug']); ?>"
                   class="btn btn-primary btn-sm w-100 mb-2">
                  <i class="bi bi-plus-circle me-1"></i> Buat Itinerary Baru
                </a>
                <a href="itineraries.php"
                   class="btn btn-outline-secondary btn-sm w-100">
                  <i class="bi bi-list-check me-1"></i> Kelola Itinerary Saya
                </a>
              </div>
            </div>

          </div>
          <!-- END RIGHT SIDE -->

        </div>
      </div>
    </section>

  <?php endif; ?>

</main>

<?php include 'footer.php'; ?>
