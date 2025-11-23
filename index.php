<?php
session_start();
require 'koneksi.php';

// Wajib login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$username  = $_SESSION['username'] ?? 'Traveler';
$pageTitle = 'Smart Tour Bandung - Home';

// ========== AMBIL EVENT YANG SEDANG / AKAN BERLANGSUNG ==========
$events = [];
$now    = date('Y-m-d H:i:s');

$sqlEvents = "
  SELECT 
    e.event_id,
    e.name,
    e.slug,
    e.location_text,
    e.start_datetime,
    e.end_datetime,
    e.price_min,
    e.price_max,
    e.image_cover,
    d.name AS dest_name
  FROM events e
  LEFT JOIN destinations d
    ON e.destination_id = d.destination_id
  WHERE
    (e.end_datetime IS NOT NULL AND e.end_datetime >= ?)
    OR
    (e.end_datetime IS NULL AND e.start_datetime >= ?)
  ORDER BY e.start_datetime ASC
  LIMIT 6
";

$stmtEv = mysqli_prepare($koneksi, $sqlEvents);
if ($stmtEv) {
  mysqli_stmt_bind_param($stmtEv, 'ss', $now, $now);
  mysqli_stmt_execute($stmtEv);
  $resEv = mysqli_stmt_get_result($stmtEv);
  while ($row = mysqli_fetch_assoc($resEv)) {
    $events[] = $row;
  }
  mysqli_stmt_close($stmtEv);
}

// ========== HELPER UNTUK EVENT DI HOMEPAGE ==========
function home_event_date_range($start, $end) {
  if (!$start) return '-';
  try {
    $ds = new DateTime($start);
    $textStart = $ds->format('d M Y H:i');

    if (!$end) {
      return $textStart;
    }

    $de = new DateTime($end);

    // Jika masih di hari yang sama
    if ($ds->format('Y-m-d') === $de->format('Y-m-d')) {
      return $textStart . ' – ' . $de->format('H:i');
    }

    // Hari berbeda
    return $textStart . ' – ' . $de->format('d M Y H:i');
  } catch (Exception $e) {
    return $start;
  }
}

function home_event_price_short($min, $max) {
  if ($min === null && $max === null) {
    return 'Harga lihat info resmi';
  }
  if ((int)$min === 0 && (int)$max === 0) {
    return 'Gratis';
  }

  if ($min !== null && $max !== null && $min != $max) {
    return 'Rp ' . number_format($min, 0, ',', '.') .
           ' - Rp ' . number_format($max, 0, ',', '.');
  }

  $val = $min ?? $max;
  return 'Mulai Rp ' . number_format($val, 0, ',', '.');
}

function home_event_date_badge($start) {
  if (!$start) return '';
  try {
    $dt = new DateTime($start);
    return $dt->format('d M');
  } catch (Exception $e) {
    return '';
  }
}

include 'header.php';
?>

    <!-- Travel Hero Section -->
    <section id="travel-hero" class="travel-hero section dark-background">

      <div class="hero-background">
        <video autoplay muted loop>
          <source src="assets/img/travel/video-2.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
      </div>

      <div class="container position-relative">
        <div class="row align-items-center">
          <div class="col-lg-7">
            <div class="hero-text" data-aos="fade-up" data-aos-delay="100">
              <p class="text-light mb-2">
                Welcome back, <strong><?= htmlspecialchars($username); ?></strong>
              </p>
              <h1 class="hero-title">Rencanakan Itinerary Wisata Bandung-mu</h1>
              <p class="hero-subtitle">
                Jelajahi destinasi alam, kuliner, dan heritage di Bandung dengan rekomendasi yang
                dipersonalisasi sesuai preferensi kamu. Smart Tour Bandung membantu menyusun rencana perjalanan
                harian yang efisien dan menyenangkan.
              </p>
              <div class="hero-buttons">
                <a href="itinerary-create.php" class="btn btn-primary me-3">
                  <i class="bi bi-magic me-1"></i> Generate Itinerary
                </a>
                <a href="destinations.php" class="btn btn-outline">
                  <i class="bi bi-geo-alt me-1"></i> Lihat Destinasi
                </a>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="booking-form-wrapper" data-aos="fade-left" data-aos-delay="200">
              <div class="booking-form">
                <h3 class="form-title">Quick Bandung Planner</h3>
                <form id="quick-planner-form" action="itinerary-auto.php" method="post">
                  <div class="form-group mb-3">
                    <label for="preference">Jenis Wisata</label>
                    <select name="preference" id="preference" class="form-select" required>
                      <option value="">Pilih jenis wisata</option>
                      <option value="alam">Alam & Outdoor</option>
                      <option value="kuliner">Kuliner & Cafe Hopping</option>
                      <option value="heritage">Heritage & Kota Lama</option>
                      <option value="keluarga">Keluarga & Anak</option>
                      <option value="instagramable">Spot Foto & Instagramable</option>
                    </select>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="days">Jumlah Hari</label>
                        <select name="days" id="days" class="form-select" required>
                          <option value="1">1 Hari</option>
                          <option value="2">2 Hari</option>
                          <option value="3">3 Hari</option>
                          <option value="4">4 Hari</option>
                          <option value="5">5+ Hari</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="form-group mb-3">
                    <label for="budget">Perkiraan Budget per Orang</label>
                    <select name="budget" id="budget" class="form-select" required>
                      <option value="">Pilih rentang budget</option>
                      <option value="low">Low Budget</option>
                      <option value="medium">Medium</option>
                      <option value="high">High / Premium</option>
                    </select>
                  </div>

                  <div class="form-group mb-3">
                    <label for="note">Catatan Tambahan (opsional)</label>
                    <textarea name="note" id="note" class="form-control" rows="2"
                      placeholder="Misal: cari yang ramah anak, hindari macet berat, wajib ada kuliner halal."></textarea>
                  </div>

                  <!-- Hidden field untuk itinerary-auto.php -->
                  <input type="hidden" name="title" id="auto_title">
                  <input type="hidden" name="end_date" id="auto_end_date">
                  <input type="hidden" name="max_per_day" id="auto_max_per_day" value="3">
                  <input type="hidden" name="preferences" id="auto_preferences">

                  <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-magic me-1"></i> Buat Itinerary Sederhana
                  </button>
                </form>
              </div>

            </div>
          </div>
        </div>
      </div>

    </section><!-- /Travel Hero Section -->

    <!-- Why Us Section -->
    <section id="why-us" class="why-us section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- About Us Content -->
        <div class="row align-items-center mb-5">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
            <div class="content">
              <h3>Kenapa Smart Tour Bandung?</h3>
              <p>
                Smart Tour Bandung dirancang untuk membantu wisatawan dan warga lokal menyusun rencana perjalanan
                yang efisien, menyenangkan, dan sesuai minat. Data destinasi, jam buka, perkiraan waktu tempuh, dan
                preferensi pengguna dipadukan menjadi itinerary yang siap dipakai.
              </p>
              <p>
                Kamu tidak perlu lagi berpindah-pindah aplikasi dan tab browser hanya untuk menyusun rute wisata.
                Cukup pilih preferensi, lama perjalanan, dan kami bantu rekomendasikan urutan destinasi terbaik di
                area Bandung dan sekitarnya.
              </p>
              <div class="stats-row">
                <div class="stat-item">
                  <span data-purecounter-start="0" data-purecounter-end="150" data-purecounter-duration="2" class="purecounter">0</span>
                  <div class="stat-label">Destinasi Terdata</div>
                </div>
                <div class="stat-item">
                  <span data-purecounter-start="0" data-purecounter-end="320" data-purecounter-duration="2" class="purecounter">0</span>
                  <div class="stat-label">Itinerary Dibuat</div>
                </div>
                <div class="stat-item">
                  <span data-purecounter-start="0" data-purecounter-end="95" data-purecounter-duration="2" class="purecounter">0</span>
                  <div class="stat-label">% Pengguna Puas</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="about-image">
              <img src="assets/img/destinations/bandung.jpg" alt="Smart Tour Bandung" class="img-fluid rounded-4">
              <div class="experience-badge">
                <div class="experience-number">Bandung</div>
                <div class="experience-text">Alam • Kuliner • Heritage</div>
              </div>
            </div>
          </div>
        </div><!-- End About Us Content -->

        <!-- Why Choose Us -->
        <div class="why-choose-section">
          <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5" data-aos="fade-up" data-aos-delay="100">
              <h3>Fitur Utama untuk Membantu Perjalananmu</h3>
              <p>Semua dalam satu tempat: destinasi, review, dan itinerary personal.</p>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-map"></i>
                </div>
                <h4>Daftar Destinasi Bandung</h4>
                <p>
                  Telusuri destinasi alam, kuliner, dan spot foto populer di Bandung lengkap dengan informasi
                  alamat, jam buka, dan perkiraan biaya.
                </p>
              </div>
            </div>

            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="250">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-diagram-3"></i>
                </div>
                <h4>Itinerary Otomatis</h4>
                <p>
                  Susun rute harian berdasarkan preferensi, jumlah hari, dan jenis wisata. Kurangi waktu di jalan,
                  maksimalkan waktu menikmati Bandung.
                </p>
              </div>
            </div>

            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-people"></i>
                </div>
                <h4>Review Pengguna</h4>
                <p>
                  Lihat pengalaman pengguna lain di setiap destinasi untuk membantumu memutuskan mana yang paling
                  cocok dikunjungi.
                </p>
              </div>
            </div>

            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="350">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h4>Integrasi Peta</h4>
                <p>
                  Setiap destinasi dilengkapi dengan lokasi di peta untuk memudahkan navigasi dan perencanaan rute.
                </p>
              </div>
            </div>

            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-heart"></i>
                </div>
                <h4>Favorit & Wishlist</h4>
                <p>
                  Simpan destinasi favoritmu dan susun itinerary dari daftar favorit hanya dengan beberapa klik.
                </p>
              </div>
            </div>

            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="450">
              <div class="feature-card">
                <div class="feature-icon">
                  <i class="bi bi-shield-check"></i>
                </div>
                <h4>Pengalaman Terpersonalisasi</h4>
                <p>
                  Sistem akan belajar dari preferensimu, sehingga rekomendasi destinasi dan itinerary makin lama
                  makin relevan.
                </p>
              </div>
            </div>
          </div><!-- End Features Grid -->
        </div><!-- End Why Choose Us -->

      </div>
    </section><!-- /Why Us Section -->

    <!-- Featured Destinations Section -->
    <section id="featured-destinations" class="featured-destinations section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Featured Bandung Destinations</h2>
        <div><span>Explore Our</span> <span class="description-title">Top Picks in Bandung</span></div>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row">

          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="featured-destination">
              <div class="destination-overlay">
                <img src="assets/img/destinations/tahura.jpg" alt="Tahura Djuanda" class="img-fluid">
                <div class="destination-info">
                  <span class="destination-tag">Alam & Trekking</span>
                  <h3>Taman Hutan Raya Ir. H. Djuanda</h3>
                  <p class="location"><i class="bi bi-geo-alt-fill"></i> Dago Pakar, Bandung</p>
                  <p class="description">
                    Hutan kota dengan udara sejuk, jalur trekking, Goa Jepang, dan pemandangan hijau Bandung Utara.
                    Cocok untuk wisata alam ringan dan foto-foto.
                  </p>
                  <div class="destination-meta">
                    <div class="tours-count">
                      <i class="bi bi-clock"></i>
                      <span>Rekomendasi: 2–4 jam</span>
                    </div>
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <span>4.7 (user reviews)</span>
                    </div>
                  </div>
                  <div class="price-info">
                    <span class="starting-from">Tiket mulai</span>
                    <span class="amount">Rp 20.000</span>
                  </div>
                  <a href="destination-details.php?slug=tahura-djuanda" class="explore-btn">
                    <span>Detail Destinasi</span>
                    <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="row g-3">

              <div class="col-12" data-aos="fade-left" data-aos-delay="300">
                <div class="compact-destination">
                  <div class="destination-image">
                    <img src="assets/img/destinations/orchid-forest.jpg" alt="Orchid Forest Cikole" class="img-fluid">
                    <div class="badge-offer">Instagramable</div>
                  </div>
                  <div class="destination-details">
                    <h4>Orchid Forest Cikole</h4>
                    <p class="location"><i class="bi bi-geo-alt"></i> Cikole, Lembang</p>
                    <p class="brief">
                      Taman anggrek terbesar dengan jembatan gantung ikonik, cocok untuk hunting foto dan jalan santai
                      di tengah hutan pinus.
                    </p>
                    <div class="stats-row">
                      <span class="tour-count"><i class="bi bi-clock"></i> 2–3 jam</span>
                      <span class="rating"><i class="bi bi-star-fill"></i> 4.8</span>
                      <span class="price">± Rp 40.000</span>
                    </div>
                    <a href="destination-details.php?slug=orchid-forest-cikole" class="quick-link">Lihat Detail <i class="bi bi-chevron-right"></i></a>
                  </div>
                </div>
              </div>

              <div class="col-12" data-aos="fade-left" data-aos-delay="400">
                <div class="compact-destination">
                  <div class="destination-image">
                    <img src="assets/img/destinations/braga.jpg" alt="Jalan Braga" class="img-fluid">
                  </div>
                  <div class="destination-details">
                    <h4>Jalan Braga</h4>
                    <p class="location"><i class="bi bi-geo-alt"></i> Pusat Kota Bandung</p>
                    <p class="brief">
                      Kawasan heritage dengan bangunan kolonial, kafe, dan galeri seni. Ikonik untuk foto
                      nuansa kota tua Bandung.
                    </p>
                    <div class="stats-row">
                      <span class="tour-count"><i class="bi bi-clock"></i> Flexible</span>
                      <span class="rating"><i class="bi bi-star-fill"></i> 4.6</span>
                      <span class="price">Free entry</span>
                    </div>
                    <a href="destination-details.php?slug=jalan-braga" class="quick-link">Lihat Detail <i class="bi bi-chevron-right"></i></a>
                  </div>
                </div>
              </div>

              <div class="col-12" data-aos="fade-left" data-aos-delay="500">
                <div class="compact-destination">
                  <div class="destination-image">
                    <img src="assets/img/destinations/sudirman.jpg" alt="Sudirman Street Food" class="img-fluid">
                    <div class="badge-offer limited">Kuliner Malam</div>
                  </div>
                  <div class="destination-details">
                    <h4>Sudirman Street Food</h4>
                    <p class="location"><i class="bi bi-geo-alt"></i> Jl. Sudirman, Bandung</p>
                    <p class="brief">
                      Sentra kuliner malam dengan berbagai tenant makanan lokal dan internasional. Cocok untuk
                      kulineran setelah keliling kota.
                    </p>
                    <div class="stats-row">
                      <span class="tour-count"><i class="bi bi-clock"></i> 18.00–23.00</span>
                      <span class="rating"><i class="bi bi-star-fill"></i> 4.7</span>
                      <span class="price">Budget variatif</span>
                    </div>
                    <a href="destination-details.php?slug=sudirman-street-food" class="quick-link">Lihat Detail <i class="bi bi-chevron-right"></i></a>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>

    </section><!-- /Featured Destinations Section -->

    <!-- Upcoming Events Section -->
<!-- Events Section (Minimalist Swiper, No Extra CSS) -->
<section id="home-events" class="section light-background">

  <div class="container section-title" data-aos="fade-up">
    <h2>Upcoming Events</h2>
    <div><span>Cari Event Menarik?</span> <span class="description-title">Bandung Selalu Ada Agenda Seru!</span></div>
  </div>

  <div class="container" data-aos="fade-up" data-aos-delay="100">

    <?php if (empty($events)): ?>

      <div class="alert alert-info text-center mb-0">
        Belum ada event yang sedang atau akan berlangsung.
      </div>

    <?php else: ?>

      <div class="swiper init-swiper" style="padding-bottom:40px;">
        <script type="application/json" class="swiper-config">
          {
            "loop": false,
            "slidesPerView": 1,
            "spaceBetween": 20,
            "navigation": {
              "nextEl": ".swiper-button-next",
              "prevEl": ".swiper-button-prev"
            },
            "breakpoints": {
              "768": {
                "slidesPerView": 2,
                "spaceBetween": 24
              },
              "1200": {
                "slidesPerView": 3,
                "spaceBetween": 24
              }
            }
          }
        </script>

        <div class="swiper-wrapper">

          <?php foreach ($events as $ev): ?>
            <?php
              $cover = $ev['image_cover'] ?: 'assets/img/travel/showcase-8.webp';
              $dateBadge = home_event_date_badge($ev['start_datetime']);
            ?>

            <div class="swiper-slide">
              <div class="card shadow-sm">

                <img src="<?= htmlspecialchars($cover); ?>"
                     class="card-img-top"
                     alt="<?= htmlspecialchars($ev['name']); ?>">

                <div class="card-body">
                  <?php if ($dateBadge): ?>
                    <span class="badge bg-primary mb-2">
                      <i class="bi bi-calendar-event me-1"></i><?= $dateBadge; ?>
                    </span>
                  <?php endif; ?>

                  <h5 class="card-title">
                    <?= htmlspecialchars($ev['name']); ?>
                  </h5>

                  <?php if (!empty($ev['location_text'])): ?>
                    <p class="card-text small mb-1">
                      <i class="bi bi-geo-alt me-1"></i>
                      <?= htmlspecialchars($ev['location_text']); ?>
                    </p>
                  <?php endif; ?>

                  <p class="card-text small mb-2">
                    <i class="bi bi-clock-history me-1"></i>
                    <?= htmlspecialchars(home_event_date_range($ev['start_datetime'], $ev['end_datetime'])); ?>
                  </p>

                  <p class="card-text text-success small fw-semibold">
                    <i class="bi bi-ticket-perforated me-1"></i>
                    <?= htmlspecialchars(home_event_price_short($ev['price_min'], $ev['price_max'])); ?>
                  </p>

                  <div class="d-flex justify-content-between mt-3">
                    <a href="event-details.php?slug=<?= urlencode($ev['slug']); ?>" class="btn btn-outline-primary btn-sm">
                      Detail
                    </a>
                    <a href="itinerary-create.php?add_event_id=<?= (int)$ev['event_id']; ?>" class="btn btn-primary btn-sm">
                      + Itinerary
                    </a>
                  </div>
                </div>

              </div>
            </div>

          <?php endforeach; ?>

        </div>

        <!-- Swiper Controls -->
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>

      </div>

      <div class="text-center mt-4">
        <a href="events.php" class="btn btn-outline-primary">
          <i class="bi bi-calendar-week me-1"></i> Lihat Semua Event
        </a>
      </div>

    <?php endif; ?>

  </div>

</section>



    <!-- Testimonials Home Section -->
    <section id="testimonials-home" class="testimonials-home section">

      <div class="container section-title" data-aos="fade-up">
        <h2>Testimonials</h2>
        <div><span>Apa Kata</span> <span class="description-title">Pengguna Smart Tour Bandung</span></div>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "slidesPerView": "auto",
              "pagination": {
                "el": ".swiper-pagination",
                "type": "bullets",
                "clickable": true
              },
              "breakpoints": {
                "320": {
                  "slidesPerView": 1,
                  "spaceBetween": 40
                },
                "1200": {
                  "slidesPerView": 3,
                  "spaceBetween": 1
                }
              }
            }
          </script>
          <div class="swiper-wrapper">

            <div class="swiper-slide">
              <div class="testimonial-item">
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Itinerary-nya membantu banget, dari pagi sampai malam rapi, nggak banyak waktu kebuang di jalan. Cocok buat short escape ke Bandung.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
                <img src="assets/img/person/person-m-9.webp" class="testimonial-img" alt="">
                <h3>Rama</h3>
                <h4>Jakarta</h4>
              </div>
            </div>

            <div class="swiper-slide">
              <div class="testimonial-item">
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Suka banget sama fitur preferensi. Tinggal pilih kuliner dan coffee hopping, langsung keluar rekomendasi tempat dan urutan kunjungannya.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
                <img src="assets/img/person/person-f-5.webp" class="testimonial-img" alt="">
                <h3>Sinta</h3>
                <h4>Travel Enthusiast</h4>
              </div>
            </div>

            <div class="swiper-slide">
              <div class="testimonial-item">
                <p>
                  <i class="bi bi-quote quote-icon-left"></i>
                  <span>Anak-anak senang, orang tua juga tenang. Rute keluarga yang disarankan pas, nggak terlalu padat tapi tetap banyak tempat menarik.</span>
                  <i class="bi bi-quote quote-icon-right"></i>
                </p>
                <img src="assets/img/person/person-f-12.webp" class="testimonial-img" alt="">
                <h3>Dina</h3>
                <h4>Family Trip Planner</h4>
              </div>
            </div>

          </div>
          <div class="swiper-pagination"></div>
        </div>

      </div>

    </section><!-- /Testimonials Home Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="hero-content" data-aos="zoom-in" data-aos-delay="200">
          <div class="content-wrapper">
            <div class="badge-wrapper">
              <span class="promo-badge">Mulai Eksplor Bandung</span>
            </div>
            <h2>Masih Bingung Mau ke Mana?</h2>
            <p>Gunakan Smart Tour Bandung untuk menyusun rencana perjalanan yang sesuai gaya liburanmu. Dari Lembang sampai Ciwidey, kami bantu atur rutenya.</p>

            <div class="action-section">
              <div class="main-actions">
                <a href="destinations.php" class="btn btn-explore">
                  <i class="bi bi-compass"></i>
                  Lihat Destinasi
                </a>
                <a href="itinerary-create.php" class="btn btn-deals">
                  <i class="bi bi-magic"></i>
                  Buat Itinerary
                </a>
              </div>
            </div>
          </div>

          <div class="visual-element">
            <img src="assets/img/destinations/gedung_sate.jpg" alt="Travel Adventure" class="hero-image" loading="lazy">
            <div class="image-overlay">
              <div class="stat-item">
                <span class="stat-number">150+</span>
                <span class="stat-label">Destinasi Bandung</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">300+</span>
                <span class="stat-label">Itinerary dibuat</span>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Call To Action Section -->

  </main>

  <!-- Script Quick Planner -->
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('quick-planner-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      const prefSelect   = document.getElementById('preference');
      const startInput   = document.getElementById('start_date');
      const daysSelect   = document.getElementById('days');
      const budgetSelect = document.getElementById('budget');
      const noteInput    = document.getElementById('note');

      const titleInput       = document.getElementById('auto_title');
      const endDateInput     = document.getElementById('auto_end_date');
      const maxPerDayInput   = document.getElementById('auto_max_per_day');
      const preferencesInput = document.getElementById('auto_preferences');

      if (!prefSelect || !startInput || !daysSelect || !budgetSelect) {
        return;
      }

      const prefText   = prefSelect.options[prefSelect.selectedIndex].text;
      const daysVal    = parseInt(daysSelect.value, 10) || 1;
      const budgetText = budgetSelect.options[budgetSelect.selectedIndex].text;
      const noteText   = noteInput ? noteInput.value : '';

      const startVal = startInput.value;
      if (!startVal) {
        return; // biar HTML5 required yang handle
      }

      const startDate = new Date(startVal);
      const endDate   = new Date(startDate);
      endDate.setDate(endDate.getDate() + (daysVal - 1));
      const endStr = endDate.toISOString().slice(0, 10);

      titleInput.value   = `Quick Bandung Trip - ${prefText} (${daysVal} hari)`;
      endDateInput.value = endStr;

      if (daysVal <= 2) {
        maxPerDayInput.value = 4;
      } else {
        maxPerDayInput.value = 3;
      }

      preferencesInput.value =
        'Quick Planner Input:\n' +
        `- Jenis wisata: ${prefText}\n` +
        `- Lama perjalanan: ${daysVal} hari\n` +
        `- Budget: ${budgetText}\n` +
        (noteText ? `- Catatan: ${noteText}\n` : '');
    });
  });
  </script>

<?php include 'footer.php'; ?>
