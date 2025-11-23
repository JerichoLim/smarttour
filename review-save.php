<?php
session_start();
require 'koneksi.php';

// Jika bukan POST, arahkan kembali
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: destinations.php');
    exit;
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_user        = (int)$_SESSION['user_id'];
$destination_id = (int)($_POST['destination_id'] ?? 0);
$slug           = trim($_POST['slug'] ?? '');
$rating         = (int)($_POST['rating'] ?? 0);
$review_text    = trim($_POST['review_text'] ?? '');

$errors = [];

// Validasi dasar
if ($destination_id <= 0) {
    $errors[] = "Destinasi tidak valid.";
}

if ($rating < 1 || $rating > 5) {
    $errors[] = "Rating harus antara 1 sampai 5.";
}

if ($review_text === '') {
    $errors[] = "Review tidak boleh kosong.";
}

// Kalau slug kosong, minimal redirect ke daftar destinasi
if ($slug === '') {
    $slug = '';
}

// Jika ada error awal, simpan di session dan redirect balik
if (!empty($errors)) {
    $_SESSION['review_error'] = implode(' ', $errors);

    if ($slug !== '') {
        header("Location: destination-details.php?slug=" . urlencode($slug));
    } else {
        header("Location: destinations.php");
    }
    exit;
}

// Cek apakah destinasi benar-benar ada
$sqlCheck = "SELECT destination_id FROM destinations WHERE destination_id = ? LIMIT 1";
$stmtCheck = mysqli_prepare($koneksi, $sqlCheck);
if (!$stmtCheck) {
    $_SESSION['review_error'] = "Terjadi kesalahan sistem (cek destinasi).";
    header("Location: " . ($slug !== '' ? "destination-details.php?slug=" . urlencode($slug) : "destinations.php"));
    exit;
}

mysqli_stmt_bind_param($stmtCheck, "i", $destination_id);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_store_result($stmtCheck);

if (mysqli_stmt_num_rows($stmtCheck) === 0) {
    mysqli_stmt_close($stmtCheck);
    $_SESSION['review_error'] = "Destinasi tidak ditemukan.";
    header("Location: destinations.php");
    exit;
}
mysqli_stmt_close($stmtCheck);

// Insert review
$sqlInsert = "
    INSERT INTO destination_reviews (destination_id, id_user, rating, review_text, created_at)
    VALUES (?, ?, ?, ?, NOW())
";
$stmtInsert = mysqli_prepare($koneksi, $sqlInsert);

if (!$stmtInsert) {
    $_SESSION['review_error'] = "Terjadi kesalahan sistem (insert review).";
    header("Location: " . ($slug !== '' ? "destination-details.php?slug=" . urlencode($slug) : "destinations.php"));
    exit;
}

mysqli_stmt_bind_param($stmtInsert, "iiis", $destination_id, $id_user, $rating, $review_text);

if (mysqli_stmt_execute($stmtInsert)) {
    $_SESSION['review_success'] = "Terima kasih! Review Anda berhasil disimpan.";
} else {
    $_SESSION['review_error'] = "Gagal menyimpan review. Silakan coba lagi.";
}

mysqli_stmt_close($stmtInsert);

// Redirect kembali ke halaman detail destinasi
if ($slug !== '') {
    header("Location: destination-details.php?slug=" . urlencode($slug));
} else {
    header("Location: destinations.php");
}
exit;
