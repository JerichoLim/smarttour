<?php
session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';

// --- FILTER ---
$search    = trim($_GET['q']   ?? '');
$category  = (int)($_GET['cat'] ?? 0);

// --- Pagination ---
$perPage = 9;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// --- Ambil kategori ---
$categories = [];
$resCat = mysqli_query($koneksi, "SELECT category_id, name FROM destination_categories ORDER BY name");

while ($row = mysqli_fetch_assoc($resCat)) {
  $categories[] = $row;
}

// --- WHERE Clause ---
$where = [];

if ($search !== '') {
  $esc = mysqli_real_escape_string($koneksi, $search);
  $where[] = "(d.name LIKE '%$esc%' OR d.address LIKE '%$esc%')";
}

if ($category > 0) {
  $where[] = "d.category_id = $category";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Hitung total ---
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM destinations d
  LEFT JOIN destination_categories c ON d.category_id = c.category_id
  $whereSql
";

$total = mysqli_fetch_assoc(mysqli_query($koneksi, $sqlCount))['total'] ?? 0;
$totalPages = max(1, ceil($total / $perPage));

// Reset page jika melebihi batas
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

// --- Query data ---
$sqlData = "
  SELECT
    d.destination_id, d.name, d.slug, d.description, d.address,
    d.ticket_price, d.image_cover,
    c.name AS category_name
  FROM destinations d
  LEFT JOIN destination_categories c ON d.category_id = c.category_id
  $whereSql
  ORDER BY d.name ASC
  LIMIT $perPage OFFSET $offset
";

$resData = mysqli_query($koneksi, $sqlData);
$destinations = [];

while ($row = mysqli_fetch_assoc($resData)) {
  $destinations[] = $row;
}

// --- Hitung urutan tampilan berdasarkan pagination ---
if ($total > 0) {
  $startNumber = $offset + 1;
  $endNumber   = $offset + count($destinations);
  if ($endNumber > $total) {
    $endNumber = $total;
  }
} else {
  $startNumber = 0;
  $endNumber   = 0;
}


// --- Helper ---
function short_desc($text, $max = 140) {
  $text = trim($text ?? '');
  return (mb_strlen($text) > $max) ? mb_substr($text, 0, $max) . '...' : $text;
}

function build_query($page, $search, $category) {
  $params = [];
  if ($search)  $params['q']   = $search;
  if ($category > 0) $params['cat'] = $category;
  if ($page > 1) $params['page'] = $page;

  return empty($params) ? "destinations.php" : ("destinations.php?" . http_build_query($params));
}

?>

<?php include 'header.php'; ?>

<!-- Page Title -->
<div class="page-title dark-background" data-aos="fade"
     style="background-image:url(assets/img/travel/showcase-8.webp);">
  <div class="container position-relative">
    <h1>Destinations</h1>
    <p>Jelajahi destinasi wisata Bandung: alam, kuliner, heritage, dan spot foto favorit.</p>
    <nav class="breadcrumbs">
      <ol>
        <li><a href="index.php">Home</a></li>
        <li class="current">Destinations</li>
      </ol>
    </nav>
  </div>
</div>

<section class="section">
  <div class="container" data-aos="fade-up">

    <!-- Filter -->
    <form class="row gy-2 align-items-end mb-4" method="get" action="destinations.php">
      <div class="col-lg-4 col-md-6">
        <label class="form-label">Cari destinasi</label>
        <input type="text" name="q" class="form-control"
               placeholder="Nama destinasi atau alamat"
               value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="col-lg-3 col-md-4">
        <label class="form-label">Kategori</label>
        <select name="cat" class="form-select">
          <option value="">Semua kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>"
              <?= ($category == $cat['category_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-lg-2 col-md-4">
        <button class="btn btn-primary w-100">
          <i class="bi bi-search me-1"></i> Filter
        </button>
      </div>

      <?php if ($search || $category): ?>
        <div class="col-lg-2 col-md-4">
          <a href="destinations.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-circle me-1"></i> Reset
          </a>
        </div>
      <?php endif; ?>
    </form>

    <!-- Grid -->
    <div class="row gy-4 mt-2">

      <?php if (!$destinations): ?>
        <div class="col-12">
          <div class="alert alert-info">Tidak ada destinasi yang sesuai dengan filter.</div>
        </div>
      <?php endif; ?>

      <?php foreach ($destinations as $d): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 shadow-sm border-0" data-aos="fade-up">

            <img src="<?= $d['image_cover'] ?: 'assets/img/travel/default.webp' ?>"
                 class="card-img-top" style="height:220px;object-fit:cover;"
                 alt="<?= htmlspecialchars($d['name']) ?>">

            <div class="card-body d-flex flex-column">

              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title"><?= htmlspecialchars($d['name']) ?></h5>
                <span class="badge bg-primary-subtle text-primary">
                  <i class="bi bi-tag me-1"></i><?= htmlspecialchars($d['category_name']) ?>
                </span>
              </div>

              <?php if ($d['address']): ?>
                <p class="text-muted small mb-2">
                  <i class="bi bi-geo-alt me-1"></i>
                  <?= htmlspecialchars($d['address']) ?>
                </p>
              <?php endif; ?>

              <p class="small flex-grow-1">
                <?= htmlspecialchars(short_desc($d['description'])) ?>
              </p>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-success fw-semibold small">
                  <?php if ($d['ticket_price'] !== null): ?>
                    Tiket ± Rp <?= number_format($d['ticket_price'], 0, ',', '.') ?>
                  <?php else: ?>
                    Info tiket di detail
                  <?php endif; ?>
                </span>

                <a href="destination-details.php?slug=<?= urlencode($d['slug']) ?>"
                   class="btn btn-sm btn-outline-primary">
                  Detail <i class="bi bi-arrow-right-short"></i>
                </a>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </div>

	<!-- Info jumlah destinasi -->
	<div class="text-center mt-4 mb-2">
	  <small class="text-muted">
		Menampilkan <strong><?= $startNumber ?>–<?= $endNumber ?></strong>
		dari <strong><?= $total ?></strong> destinasi
	  </small>
	</div>


    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination justify-content-center">

          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link"
               href="<?= $page > 1 ? build_query($page - 1, $search, $category) : '#' ?>">
              &laquo; Prev
            </a>
          </li>

          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
              <a class="page-link" href="<?= build_query($p, $search, $category) ?>">
                <?= $p ?>
              </a>
            </li>
          <?php endfor; ?>

          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link"
               href="<?= $page < $totalPages ? build_query($page + 1, $search, $category) : '#' ?>">
              Next &raquo;
            </a>
          </li>

        </ul>
      </nav>
    <?php endif; ?>

  </div>
</section>

<?php include 'footer.php'; ?>
