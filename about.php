<?php
session_start();

// Wajib login (samakan dengan halaman lain)
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Traveler';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>About - Smart Tour Bandung</title>
  <meta name="description" content="Tentang Smart Tour Bandung - platform penyusun itinerary wisata Bandung yang terpersonalisasi.">
  <meta name="keywords" content="about, smart tour bandung, wisata bandung, itinerary">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,800;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="about-page">

  <!-- Header -->
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <!-- <img src="assets/img/logo.webp" alt=""> -->
        <h1 class="sitename">Smart Tour Bandung</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php" class="active">About</a></li>
          <li><a href="destinations.php">Destinations</a></li>
          <li><a href="itineraries.php">My Itineraries</a></li>
          <li><a href="gallery.php">Gallery</a></li>
          <li class="dropdown"><a href="#"><span>More</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="booking.html">Booking (Coming Soon)</a></li>
              <li><a href="faq.html">Frequently Asked Questions</a></li>
              <li><a href="terms.html">Terms</a></li>
              <li><a href="privacy.html">Privacy</a></li>
            </ul>
          </li>

          <!-- User menu -->
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
    <div class="page-title dark-background" data-aos="fade"
         style="background-image: url(assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>About Smart Tour Bandung</h1>
        <p>Platform perencana perjalanan yang membantu wisatawan menyusun itinerary Bandung yang efisien, menyenangkan, dan sesuai preferensi pribadi.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">About</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Travel About Section -->
    <section id="travel-about" class="travel-about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- Intro -->
        <div class="row">
          <div class="col-lg-8 mx-auto text-center mb-5">
            <div class="intro-content" data-aos="fade-up" data-aos-delay="200">
              <h2>Merangkai Pengalaman Bandung,<br>Satu Itinerary di Setiap Perjalanan</h2>
              <p class="lead">
                Smart Tour Bandung lahir dari kebutuhan sederhana: bagaimana cara menyusun rute wisata Bandung
                yang tidak boros waktu di jalan, tetap seru, dan relevan dengan gaya liburan masing-masing.
                Dari wisata alam di Lembang hingga kuliner malam di pusat kota, kami ingin semua orang
                bisa menikmati sisi terbaik Bandung dengan cara yang lebih cerdas.
              </p>
            </div>
          </div>
        </div>

        <!-- Our Story -->
        <div class="row align-items-center mb-5">
          <div class="col-lg-5" data-aos="zoom-in" data-aos-delay="300">
            <div class="hero-image">
              <img src="assets/img/destinations/bandung.jpg" class="img-fluid rounded-4" alt="Smart Tour Bandung">
              <div class="floating-stats">
                <div class="stat-item">
                  <span class="number">150+</span>
                  <span class="label">Destinasi Bandung</span>
                </div>
                <div class="stat-item">
                  <span class="number">300+</span>
                  <span class="label">Itinerary Dibuat</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6 offset-lg-1" data-aos="slide-left" data-aos-delay="400">
            <div class="story-content">
              <div class="story-badge">
                <i class="bi bi-compass"></i>
                <span>Our Story</span>
              </div>
              <h3>Dari Kerumitan Tab Browser ke Satu Layar Itinerary</h3>
              <p>
                Sebelum ada Smart Tour Bandung, menyusun rencana perjalanan berarti membuka banyak tab:
                blog, Google Maps, review, sampai catatan pribadi yang tercecer. Kami ingin merapikan
                proses itu menjadi satu pengalaman terpadu: pilih preferensi, lihat destinasi, susun itinerary,
                dan simpan semuanya di satu tempat.
              </p>
              <p>
                Aplikasi ini dirancang sebagai prototipe <em>smart tourism</em> untuk Bandung, menggabungkan
                data destinasi, preferensi pengguna, dan konsep perencanaan rute harian yang realistis.
                Fokus kami bukan hanya pada turis luar kota, tetapi juga warga Bandung yang ingin
                mengeksplor tempat baru tanpa ribet.
              </p>

              <div class="mission-box">
                <div class="mission-icon">
                  <i class="bi bi-globe-asia-australia"></i>
                </div>
                <div class="mission-text">
                  <h4>Visi</h4>
                  <p>
                    “Menjadikan Bandung destinasi wisata yang cerdas dan inklusif, melalui perencanaan perjalanan
                    digital yang membantu wisatawan, pelaku usaha lokal, dan pemerintah kota.”
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- What Makes Us Different -->
        <div class="row">
          <div class="col-lg-12">
            <div class="features-grid" data-aos="fade-up" data-aos-delay="200">
              <div class="section-header text-center mb-5">
                <h3>Kenapa Smart Tour Bandung Berbeda?</h3>
                <p>Enam pilar yang kami pegang dalam merancang fitur dan pengalaman pengguna.</p>
              </div>

              <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-people"></i>
                      </div>
                      <h4>Kolaborasi Lokal</h4>
                      <p>Menonjolkan usaha kuliner, kafe, dan destinasi lokal Bandung.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Kurasi destinasi dilakukan dengan melihat rekomendasi warga lokal,
                        komunitas, dan pelaku usaha sehingga itinerary yang terbentuk terasa “Bandung banget”.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-diagram-3"></i>
                      </div>
                      <h4>Itinerary Terstruktur</h4>
                      <p>Rute harian yang mempertimbangkan jarak & waktu tempuh.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Konsep *itinerary builder* mengelompokkan destinasi per area (Lembang, Dago, Kota,
                        Ciwidey) agar waktu di jalan lebih efisien dan pengalaman lebih maksimal.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-geo-alt"></i>
                      </div>
                      <h4>Integrasi Peta</h4>
                      <p>Lokasi destinasi terhubung dengan peta interaktif.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Setiap destinasi menyimpan koordinat sehingga pengguna mudah
                        membuka arah di peta, memperkirakan waktu tempuh, dan menyesuaikan urutan kunjungan.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-sliders"></i>
                      </div>
                      <h4>Preferensi Personal</h4>
                      <p>Itinerary mengikuti gaya liburanmu.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Pengguna dapat menuliskan preferensi seperti “coffee hopping”, “ramah anak”,
                        atau “hindari macet berat” sebagai catatan utama saat menyusun rute.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-chat-heart"></i>
                      </div>
                      <h4>Review Pengguna</h4>
                      <p>Suara langsung dari pengunjung.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Fitur ulasan membantu calon pengunjung mendapatkan gambaran suasana,
                        kisaran biaya riil, dan tips kecil yang sering tidak ada di brosur resmi.
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                  <div class="feature-card">
                    <div class="feature-front">
                      <div class="feature-icon">
                        <i class="bi bi-cpu"></i>
                      </div>
                      <h4>Fondasi Smart Tourism</h4>
                      <p>Prototipe untuk pengembangan kota cerdas.</p>
                    </div>
                    <div class="feature-back">
                      <p>
                        Smart Tour Bandung dirancang sebagai dasar eksperimen integrasi data,
                        rekomendasi otomatis, dan pengambilan keputusan berbasis data untuk
                        pemangku kepentingan pariwisata Bandung.
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Timeline / Milestones -->
        <div class="row mt-5">
          <div class="col-lg-12">
            <div class="journey-timeline" data-aos="fade-up" data-aos-delay="200">
              <div class="timeline-header text-center mb-5">
                <h3>Perjalanan Pengembangan</h3>
                <p>Beberapa tonggak yang membentuk Smart Tour Bandung.</p>
              </div>

              <div class="timeline-container">
                <div class="timeline-track"></div>

                <div class="timeline-milestone" data-aos="slide-right" data-aos-delay="300">
                  <div class="milestone-marker">
                    <span class="year">2024</span>
                  </div>
                  <div class="milestone-content">
                    <h4>Gagasan Awal</h4>
                    <p>
                      Ide lahir dari tugas magister dan observasi lapangan bahwa informasi wisata Bandung
                      masih terpencar di banyak kanal tanpa integrasi.
                    </p>
                  </div>
                </div>

                <div class="timeline-milestone" data-aos="slide-left" data-aos-delay="400">
                  <div class="milestone-marker">
                    <span class="year">2025</span>
                  </div>
                  <div class="milestone-content">
                    <h4>Prototipe Web</h4>
                    <p>
                      Dibangun versi web pertama dengan modul destinasi, review, dan itinerary builder,
                      termasuk fitur auto-itinerary sederhana berdasarkan preferensi pengguna.
                    </p>
                  </div>
                </div>

                <div class="timeline-milestone" data-aos="slide-right" data-aos-delay="500">
                  <div class="milestone-marker">
                    <span class="year">Next</span>
                  </div>
                  <div class="milestone-content">
                    <h4>Integrasi Data & Analitik</h4>
                    <p>
                      Rencana pengembangan berikutnya adalah integrasi dengan data trafik,
                      cuaca, dan event kota untuk membuat rekomendasi yang semakin kontekstual.
                    </p>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="row mt-5">
          <div class="col-lg-12">
            <div class="cta-banner" data-aos="zoom-in" data-aos-delay="300">
              <div class="cta-overlay">
                <div class="cta-content">
                  <h3>Siap Menyusun Perjalanan Bandung-mu?</h3>
                  <p>Mulai dari pilih destinasi favorit, lalu bentuk itinerary yang bisa kamu sesuaikan kapan saja.</p>
                  <div class="cta-buttons">
                    <a href="destinations.php" class="btn btn-primary me-3">Jelajahi Destinasi</a>
                    <a href="itinerary-create.php" class="btn btn-outline">Buat Itinerary Baru</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Travel About Section -->

  </main>

  <!-- Footer (samakan dengan halaman lain) -->
  <footer id="footer" class="footer position-relative dark-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="index.php" class="d-flex align-items-center">
            <span class="sitename">Smart Tour Bandung</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Bandung, Jawa Barat</p>
            <p>Indonesia</p>
            <p class="mt-3"><strong>Email:</strong> <span>support@smarttourbandung.test</span></p>
          </div>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Menu</h4>
          <ul>
            <li><i class="bi bi-chevron-right"></i> <a href="index.php">Home</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="destinations.php">Destinations</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="itineraries.php">Itineraries</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="contact.html">Contact</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Informasi</h4>
          <ul>
            <li><i class="bi bi-chevron-right"></i> <a href="about.php">About</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="terms.html">Terms of Service</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="privacy.html">Privacy Policy</a></li>
          </ul>
        </div>

        <div class="col-lg-4 col-md-12">
          <h4>Follow Us</h4>
          <p>Ikuti kami untuk update rute & destinasi menarik di Bandung.</p>
          <div class="social-links d-flex">
            <a href="#"><i class="bi bi-twitter-x"></i></a>
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">Smart Tour Bandung</strong> <span>All Rights Reserved</span></p>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
