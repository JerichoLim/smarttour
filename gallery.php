<?php
session_start();
require 'koneksi.php';

// (Opsional) Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';

// ====== FILTER KATEGORI ======
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// ====== PAGINATION ======
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ====== AMBIL LIST KATEGORI YANG PUNYA GAMBAR ======
$categories = [];
$sqlCat = "
  SELECT DISTINCT
    c.category_id,
    c.name
  FROM destination_images di
  JOIN destinations d
    ON di.destination_id = d.destination_id
  LEFT JOIN destination_categories c
    ON d.category_id = c.category_id
  WHERE c.category_id IS NOT NULL
  ORDER BY c.name
";
$resCat = mysqli_query($koneksi, $sqlCat);
if ($resCat) {
  while ($row = mysqli_fetch_assoc($resCat)) {
    $categories[] = $row;
  }
}

// ====== WHERE UNTUK FILTER KATEGORI ======
$where = [];
if ($cat > 0) {
  $where[] = "c.category_id = {$cat}";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ====== HITUNG TOTAL FOTO ======
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM destination_images di
  JOIN destinations d
    ON di.destination_id = d.destination_id
  LEFT JOIN destination_categories c
    ON d.category_id = c.category_id
  {$whereSql}
";
$resCount = mysqli_query($koneksi, $sqlCount);
$rowCount = $resCount ? mysqli_fetch_assoc($resCount) : ['total' => 0];
$total    = (int)($rowCount['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));

// Jika page > totalPages, sesuaikan
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $perPage;
}

// ====== AMBIL DATA FOTO (DENGAN LIMIT/OFFSET) ======
$sqlData = "
  SELECT
    di.image_path,
    di.caption,
    d.name        AS dest_name,
    d.slug        AS dest_slug,
    c.category_id AS cat_id,
    c.name        AS cat_name
  FROM destination_images di
  JOIN destinations d
    ON di.destination_id = d.destination_id
  LEFT JOIN destination_categories c
    ON d.category_id = c.category_id
  {$whereSql}
  ORDER BY di.image_id DESC
  LIMIT {$perPage} OFFSET {$offset}
";
$resData = mysqli_query($koneksi, $sqlData);

$images = [];
if ($resData) {
  while ($row = mysqli_fetch_assoc($resData)) {
    $images[] = $row;
  }
}

// ====== HELPER UNTUK LINK PAGINATION ======
function build_gallery_query($page, $cat) {
  $params = [];
  if ($cat > 0) $params['cat'] = $cat;
  if ($page > 1) $params['page'] = $page;

  return empty($params) ? 'gallery.php' : ('gallery.php?' . http_build_query($params));
}

// Info urutan data (x - y dari total z)
$from = ($total > 0) ? $offset + 1 : 0;
$to   = ($total > 0) ? min($offset + count($images), $total) : 0;

// ====== TITLE UNTUK HEADER ======
$pageTitle = 'Gallery - Smart Tour Bandung';
include 'header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title dark-background"
       data-aos="fade"
       style="background-image: url(assets/img/travel/showcase-8.webp);">
    <div class="container position-relative text-center">
      <h1>Gallery</h1>
      <p>Jelajahi momen-momen terbaik di berbagai destinasi wisata Bandung.</p>
      <nav class="breadcrumbs">
        <ol>
          <li><a href="index.php">Home</a></li>
          <li class="current">Gallery</li>
        </ol>
      </nav>
    </div>
  </div>
  <!-- End Page Title -->

  <!-- Gallery Section -->
  <section id="gallery" class="gallery section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">

      <!-- FILTER KATEGORI (RAPI & TENGAH) -->
      <div class="row mb-4">
        <div class="col-12">
          <ul class="nav nav-pills justify-content-center gap-2 gallery-filter-pills">
            <li class="nav-item">
              <a class="nav-link <?= ($cat === 0 ? 'active' : '') ?>"
                 href="gallery.php">
                Semua
              </a>
            </li>
            <?php foreach ($categories as $c): ?>
              <li class="nav-item">
                <a class="nav-link <?= ($cat === (int)$c['category_id'] ? 'active' : '') ?>"
                   href="gallery.php?cat=<?= (int)$c['category_id']; ?>">
                  <?= htmlspecialchars($c['name']); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- GRID GALLERY -->
      <div class="row gy-4">
        <?php if (empty($images)): ?>
          <div class="col-12">
            <div class="alert alert-info mb-0">
              Belum ada foto pada gallery untuk kategori ini.
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($images as $img): ?>
            <div class="col-xl-3 col-md-4 col-sm-6">
              <div class="gallery-card position-relative">
                <div class="gallery-image">
                  <a href="<?= htmlspecialchars($img['image_path']); ?>"
                     class="glightbox"
                     data-gallery="gallery-images">
                    <img src="<?= htmlspecialchars($img['image_path']); ?>"
                         class="img-fluid w-100"
                         alt="<?= htmlspecialchars($img['caption'] ?: $img['dest_name']); ?>">
                    <div class="gallery-overlay d-flex align-items-center justify-content-center">
                      <div class="text-center px-2">
                        <h4 class="mb-1 text-white">
                          <?= htmlspecialchars($img['dest_name']); ?>
                        </h4>
                        <?php if (!empty($img['caption'])): ?>
                          <p class="mb-1 small text-white-50">
                            <?= htmlspecialchars($img['caption']); ?>
                          </p>
                        <?php endif; ?>
                        <?php if (!empty($img['cat_name'])): ?>
                          <small class="badge bg-primary-subtle text-primary">
                            <?= htmlspecialchars($img['cat_name']); ?>
                          </small>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- INFO JUMLAH + PAGINATION -->
      <div class="row mt-4 align-items-center">
        <div class="col-md-6 mb-3 mb-md-0">
          <small class="text-muted">
            Menampilkan
            <strong><?= $from; ?>–<?= $to; ?></strong>
            dari total
            <strong><?= $total; ?></strong> foto
            <?php if ($cat > 0): ?>
              pada kategori
              <strong>
                <?php
                  $catName = '';
                  foreach ($categories as $c) {
                    if ((int)$c['category_id'] === $cat) {
                      $catName = $c['name'];
                      break;
                    }
                  }
                  echo htmlspecialchars($catName);
                ?>
              </strong>
            <?php endif; ?>
          </small>
        </div>

        <div class="col-md-6">
          <?php if ($totalPages > 1): ?>
            <nav aria-label="Gallery pagination">
              <ul class="pagination justify-content-md-end justify-content-center mb-0">
                <!-- Prev -->
                <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                  <a class="page-link"
                     href="<?= ($page > 1) ? build_gallery_query($page - 1, $cat) : '#'; ?>">
                    &laquo;
                  </a>
                </li>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                  <li class="page-item <?= ($p === $page ? 'active' : '') ?>">
                    <a class="page-link"
                       href="<?= build_gallery_query($p, $cat); ?>">
                      <?= $p; ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <!-- Next -->
                <li class="page-item <?= ($page >= $totalPages ? 'disabled' : '') ?>">
                  <a class="page-link"
                     href="<?= ($page < $totalPages) ? build_gallery_query($page + 1, $cat) : '#'; ?>">
                    &raquo;
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
  <!-- /Gallery Section -->

</main>

<?php include 'footer.php'; ?>
