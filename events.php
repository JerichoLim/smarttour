<?php
session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';

// ====== FILTER INPUT ======
$search   = trim($_GET['q']   ?? '');
$cat      = (int)($_GET['cat'] ?? 0);
$from_raw = trim($_GET['from'] ?? '');
$to_raw   = trim($_GET['to']   ?? '');

// Normalisasi tanggal (YYYY-MM-DD) jika valid, kalau tidak kosongkan
$from = '';
$to   = '';

if ($from_raw !== '') {
  $dt = date_create($from_raw);
  if ($dt) $from = $dt->format('Y-m-d');
}
if ($to_raw !== '') {
  $dt = date_create($to_raw);
  if ($dt) $to = $dt->format('Y-m-d');
}

// ====== PAGINATION ======
$perPage = 6;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ====== AMBIL LIST KATEGORI (UNTUK DROPDOWN) ======
$categories = [];
$sqlCat = "SELECT category_id, name FROM destination_categories ORDER BY name";
$resCat = mysqli_query($koneksi, $sqlCat);
if ($resCat) {
  while ($row = mysqli_fetch_assoc($resCat)) {
    $categories[] = $row;
  }
}

// ====== BANGUN WHERE CLAUSE ======
$where   = [];
$where[] = "1=1"; // supaya mudah concat AND

if ($search !== '') {
  $esc = mysqli_real_escape_string($koneksi, $search);
  $where[] = "(e.name LIKE '%$esc%' 
              OR e.location_text LIKE '%$esc%' 
              OR d.name LIKE '%$esc%')";
}

if ($cat > 0) {
  $where[] = "d.category_id = $cat";
}

if ($from !== '') {
  $where[] = "DATE(e.start_datetime) >= '$from'";
}

if ($to !== '') {
  $where[] = "DATE(e.start_datetime) <= '$to'";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ====== HITUNG TOTAL EVENT ======
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM events e
  LEFT JOIN destinations d ON e.destination_id = d.destination_id
  LEFT JOIN destination_categories c ON d.category_id = c.category_id
  $whereSql
";

$resCount = mysqli_query($koneksi, $sqlCount);
$rowCount = $resCount ? mysqli_fetch_assoc($resCount) : ['total' => 0];
$total    = (int)($rowCount['total'] ?? 0);

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

// ====== QUERY DATA EVENT ======
$sqlData = "
  SELECT
    e.event_id,
    e.name,
    e.slug,
    e.description,
    e.location_text,
    e.start_datetime,
    e.end_datetime,
    e.price_min,
    e.price_max,
    e.image_cover,
    d.name         AS dest_name,
    d.slug         AS dest_slug,
    c.category_id  AS cat_id,
    c.name         AS cat_name
  FROM events e
  LEFT JOIN destinations d ON e.destination_id = d.destination_id
  LEFT JOIN destination_categories c ON d.category_id = c.category_id
  $whereSql
  ORDER BY e.start_datetime DESC
  LIMIT $perPage OFFSET $offset
";

$resData = mysqli_query($koneksi, $sqlData);
$events  = [];
if ($resData) {
  while ($row = mysqli_fetch_assoc($resData)) {
    $events[] = $row;
  }
}

// ====== HELPER FUNCTIONS ======
function format_tanggal_jam_event($datetimeStr) {
  if (!$datetimeStr) return '-';
  $dt = new DateTime($datetimeStr);
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
  $val = $min ?? $max;
  return 'Mulai Rp ' . number_format($val, 0, ',', '.');
}

function event_status_label($start, $end) {
  $now = new DateTime();

  $startDt = $start ? new DateTime($start) : null;
  $endDt   = $end   ? new DateTime($end)   : null;

  if ($startDt && $endDt) {
    if ($now < $startDt) {
      return ['Akan datang', 'badge-success'];
    } elseif ($now >= $startDt && $now <= $endDt) {
      return ['Sedang berlangsung', 'badge-warning'];
    } else {
      return ['Selesai', 'badge-secondary'];
    }
  } elseif ($startDt && !$endDt) {
    if ($now < $startDt) {
      return ['Akan datang', 'badge-success'];
    } else {
      return ['Sedang/Telah berlangsung', 'badge-warning'];
    }
  }
  return ['Status tidak jelas', 'badge-secondary'];
}

function build_query_events($page, $search, $cat, $from, $to) {
  $params = [];
  if ($search !== '') $params['q']   = $search;
  if ($cat > 0)       $params['cat'] = $cat;
  if ($from !== '')   $params['from'] = $from;
  if ($to !== '')     $params['to']   = $to;
  if ($page > 1)      $params['page'] = $page;

  return empty($params) ? 'events.php' : ('events.php?' . http_build_query($params));
}

// Hitung urutan untuk info "menampilkan X–Y"
$fromIndex = $total > 0 ? $offset + 1 : 0;
$toIndex   = min($offset + $perPage, $total);

// ====== HEADER ======
$pageTitle = 'Events - Smart Tour Bandung';
include 'header.php';
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title dark-background" data-aos="fade"
       style="background-image: url(assets/img/travel/showcase-8.webp);">
    <div class="container position-relative">
      <h1>Events</h1>
      <p>Jelajahi event menarik di Bandung yang sedang atau akan berlangsung.</p>
      <nav class="breadcrumbs">
        <ol>
          <li><a href="index.php">Home</a></li>
          <li class="current">Events</li>
        </ol>
      </nav>
    </div>
  </div>
  <!-- End Page Title -->

  <section class="section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">

      <!-- Filter -->
      <form class="row gy-3 align-items-end mb-4" method="get" action="events.php">
        <div class="col-lg-4 col-md-6">
          <label for="q" class="form-label">Cari event</label>
          <input type="text"
                 class="form-control"
                 id="q"
                 name="q"
                 placeholder="Nama event atau lokasi"
                 value="<?= htmlspecialchars($search); ?>">
        </div>

        <div class="col-lg-3 col-md-6">
          <label for="cat" class="form-label">Kategori destinasi</label>
          <select name="cat" id="cat" class="form-select">
            <option value="0">Semua kategori</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['category_id']; ?>"
                <?= $cat === (int)$c['category_id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($c['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-lg-2 col-md-6">
          <label for="from" class="form-label">Mulai dari</label>
          <input type="date"
                 class="form-control"
                 id="from"
                 name="from"
                 value="<?= htmlspecialchars($from); ?>">
        </div>

        <div class="col-lg-2 col-md-6">
          <label for="to" class="form-label">Sampai</label>
          <input type="date"
                 class="form-control"
                 id="to"
                 name="to"
                 value="<?= htmlspecialchars($to); ?>">
        </div>

        <div class="col-lg-1 col-md-4 d-grid">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i>
          </button>
        </div>

        <?php if ($search !== '' || $cat > 0 || $from !== '' || $to !== ''): ?>
          <div class="col-12 mt-1">
            <a href="events.php" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-x-circle me-1"></i> Reset filter
            </a>
          </div>
        <?php endif; ?>
      </form>

      <!-- Daftar Event -->
      <div class="row gy-4">

        <?php if (empty($events)): ?>
          <div class="col-12">
            <div class="alert alert-info mb-0">
              Belum ada event yang sesuai dengan filter.
            </div>
          </div>
        <?php else: ?>

          <?php foreach ($events as $ev): ?>
            <?php
              $img = $ev['image_cover'] ?: 'assets/img/travel/destination-3.webp';
              [$statusText, $statusClass] = event_status_label($ev['start_datetime'], $ev['end_datetime']);
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0" data-aos="fade-up" data-aos-delay="150">
                <img src="<?= htmlspecialchars($img); ?>"
                     alt="<?= htmlspecialchars($ev['name']); ?>"
                     class="card-img-top destination-card-img">

                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0">
                      <?= htmlspecialchars($ev['name']); ?>
                    </h5>

                    <div class="text-end">
                      <span class="badge <?= $statusClass; ?> mb-1">
                        <?= htmlspecialchars($statusText); ?>
                      </span>
                      <?php if (!empty($ev['cat_name'])): ?>
                        <div>
                          <span class="badge bg-primary-subtle text-primary small">
                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($ev['cat_name']); ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if (!empty($ev['location_text'])): ?>
                    <p class="mb-1 text-muted small">
                      <i class="bi bi-geo-alt me-1"></i>
                      <?= htmlspecialchars($ev['location_text']); ?>
                    </p>
                  <?php elseif (!empty($ev['dest_name'])): ?>
                    <p class="mb-1 text-muted small">
                      <i class="bi bi-geo-alt me-1"></i>
                      <?= htmlspecialchars($ev['dest_name']); ?>
                    </p>
                  <?php endif; ?>

                  <p class="mb-1 small">
                    <i class="bi bi-calendar-event me-1"></i>
                    <?= format_tanggal_jam_event($ev['start_datetime']); ?>
                    <?php if (!empty($ev['end_datetime'])): ?>
                      <br><span class="ms-4">
                        s.d. <?= format_tanggal_jam_event($ev['end_datetime']); ?>
                      </span>
                    <?php endif; ?>
                  </p>

                  <p class="mb-2 small text-success fw-semibold">
                    <i class="bi bi-cash-stack me-1"></i>
                    <?= htmlspecialchars(format_harga_event($ev['price_min'], $ev['price_max'])); ?>
                  </p>

                  <div class="mt-auto d-flex justify-content-between align-items-center">
                    <?php if (!empty($ev['dest_slug'])): ?>
                      <a href="destination-details.php?slug=<?= urlencode($ev['dest_slug']); ?>"
                         class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-geo-fill me-1"></i> Destinasi
                      </a>
                    <?php else: ?>
                      <span></span>
                    <?php endif; ?>

                    <a href="event-details.php?slug=<?= urlencode($ev['slug']); ?>"
                       class="btn btn-sm btn-primary">
                      Detail <i class="bi bi-arrow-right-short"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>

      </div>

      <!-- Pagination + info jumlah -->
      <?php if ($totalPages > 1): ?>
        <div class="row align-items-center mt-4">
          <div class="col-md-4">
            <small class="text-muted">
              Menampilkan <strong><?= $fromIndex; ?></strong>–<strong><?= $toIndex; ?></strong>
              dari <strong><?= $total; ?></strong> event
              <?php if ($search !== ''): ?>
                untuk pencarian "<strong><?= htmlspecialchars($search); ?></strong>"
              <?php endif; ?>
            </small>
          </div>
          <div class="col-md-8">
            <nav aria-label="Events pagination">
              <ul class="pagination justify-content-md-end justify-content-center mb-0">
                <!-- Prev -->
                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                  <a class="page-link"
                     href="<?= $page > 1 ? build_query_events($page - 1, $search, $cat, $from, $to) : '#'; ?>">
                    &laquo;
                  </a>
                </li>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                  <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                    <a class="page-link"
                       href="<?= build_query_events($p, $search, $cat, $from, $to); ?>">
                      <?= $p; ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <!-- Next -->
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                  <a class="page-link"
                     href="<?= $page < $totalPages ? build_query_events($page + 1, $search, $cat, $from, $to) : '#'; ?>">
                    &raquo;
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      <?php elseif ($total > 0): ?>
        <div class="mt-3">
          <small class="text-muted">
            Menampilkan <strong><?= $fromIndex; ?></strong>–<strong><?= $toIndex; ?></strong>
            dari <strong><?= $total; ?></strong> event
          </small>
        </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<?php include 'footer.php'; ?>
