<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$id_user  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Traveler';

// Ambil daftar itinerary milik user
$sqlList = "
  SELECT i.itinerary_id,
         i.title,
         i.start_date,
         i.end_date,
         i.created_at,
         i.total_cost,
         COUNT(it.item_id) AS items_count
  FROM itineraries i
  LEFT JOIN itinerary_items it
    ON i.itinerary_id = it.itinerary_id
  WHERE i.id_user = ?
  GROUP BY i.itinerary_id, i.title, i.start_date, i.end_date, i.created_at, i.total_cost
  ORDER BY i.created_at DESC
";

$stmtList = mysqli_prepare($koneksi, $sqlList);
if (!$stmtList) {
  die("Query error: " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmtList, "i", $id_user);
mysqli_stmt_execute($stmtList);
$resList = mysqli_stmt_get_result($stmtList);

$itineraries = [];
if ($resList) {
  while ($row = mysqli_fetch_assoc($resList)) {
    $itineraries[] = $row;
  }
}
mysqli_stmt_close($stmtList);

// Helper format tanggal
function fmt_date($date) {
  if ($date === null || $date === '0000-00-00' || $date === '') return '-';
  $dt = new DateTime($date);
  return $dt->format('d M Y');
}

// Helper format rupiah
function fmt_rupiah($angka) {
  if ($angka === null) return '-';
  return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<?php include 'header.php'; ?>

<style>
  .itinerary-card {
    border-radius: 1rem;
  }
  .itinerary-badge {
    font-size: 0.75rem;
  }
</style>

    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>My Itineraries</h1>
        <p>Rencanakan dan kelola perjalananmu di Bandung berdasarkan preferensi pribadi.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">My Itineraries</li>
          </ol>
        </nav>
      </div>
    </div>
    <!-- End Page Title -->

    <section class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- Header + Tombol Aksi -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="h4 fw-bold mb-0">Daftar Itinerary Saya</h2>
            <small class="text-muted">
              Total: <strong><?php echo count($itineraries); ?></strong> itinerary
            </small>
          </div>

          <div class="text-end">
            <a href="itinerary-create.php" class="btn btn-outline-primary me-2">
              <i class="bi bi-plus-lg me-1"></i> Buat Manual
            </a>
            <a href="itinerary-auto.php" class="btn btn-primary">
              <i class="bi bi-magic me-1"></i> Auto Generate
            </a>
          </div>
        </div>

        <!-- Info -->
        <div class="alert alert-info py-2 mb-4">
          <i class="bi bi-info-circle me-1"></i>
          Anda dapat membuat itinerary secara manual atau menggunakan fitur
          <strong>Auto Generate</strong> berdasarkan preferensi Anda. Semua hasil tetap bisa diedit
          di halaman <em>Itinerary Detail</em>.
        </div>

        <?php if (empty($itineraries)): ?>
          <div class="row">
            <div class="col-lg-8 mx-auto">
              <div class="alert alert-light border">
                <p class="mb-1">Anda belum memiliki itinerary.</p>
                <p class="mb-0">
                  Mulai dengan
                  <a href="itinerary-create.php" class="fw-semibold">membuat secara manual</a>
                  atau coba
                  <a href="itinerary-auto.php" class="fw-semibold">Auto Generate Itinerary</a>.
                </p>
              </div>
            </div>
          </div>
        <?php else: ?>

          <div class="row gy-3">
            <?php foreach ($itineraries as $it): ?>
              <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100 itinerary-card">
                  <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h5 class="card-title mb-0">
                        <?php echo htmlspecialchars($it['title']); ?>
                      </h5>
                      <span class="badge bg-secondary-subtle text-secondary itinerary-badge">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?php echo (int)$it['items_count']; ?> destinasi
                      </span>
                    </div>

                    <p class="mb-1 small text-muted">
                      <i class="bi bi-calendar-week me-1"></i>
                      <?php echo fmt_date($it['start_date']); ?> - <?php echo fmt_date($it['end_date']); ?>
                    </p>

                    <p class="mb-1 small text-muted">
                      <i class="bi bi-clock-history me-1"></i>
                      Dibuat pada:
                      <?php
                        $dt = new DateTime($it['created_at']);
                        echo $dt->format('d M Y H:i');
                      ?>
                    </p>

                    <p class="mb-2 small text-muted">
                      <i class="bi bi-cash-stack me-1"></i>
                      Estimasi biaya: <strong><?php echo fmt_rupiah($it['total_cost']); ?></strong>
                    </p>

                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2 flex-wrap gap-2">
                      <a href="itinerary-detail.php?itinerary_id=<?php echo (int)$it['itinerary_id']; ?>"
                         class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Lihat Detail
                      </a>

                      <div class="d-flex gap-1">
                        <!-- Download PDF Button -->
                        <a href="itinerary-download.php?itinerary_id=<?php echo (int)$it['itinerary_id']; ?>" 
                           target="_blank"
                           class="btn btn-sm btn-success"
                           title="Download PDF">
                          <i class="bi bi-file-pdf"></i>
                        </a>
                        
                        <!-- Tombol hapus itinerary -->
                        <form action="itinerary-delete.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus itinerary ini? Semua destinasi di dalamnya akan ikut terhapus.');" class="d-inline">
                          <input type="hidden" name="itinerary_id" value="<?php echo (int)$it['itinerary_id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus itinerary">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>

      </div>
    </section>

  </main>

  <!-- Footer -->
  <?php include 'footer.php'; ?>