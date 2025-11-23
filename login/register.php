<?php
require 'koneksi.php';

$pesan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $pesan = "Username dan password wajib diisi.";
    } else {
        // cek apakah username sudah ada
        $sql = "SELECT id_user FROM user_tbl WHERE username = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $pesan = "Username sudah dipakai, silakan pilih yang lain.";
        } else {
            // hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // simpan ke database
            $sqlInsert = "INSERT INTO user_tbl (username, password) VALUES (?, ?)";
            $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
            mysqli_stmt_bind_param($stmtInsert, "ss", $username, $passwordHash);

            if (mysqli_stmt_execute($stmtInsert)) {
                $pesan = "Registrasi berhasil. Silakan login.";
            } else {
                $pesan = "Terjadi error saat menyimpan: " . mysqli_error($koneksi);
            }

            mysqli_stmt_close($stmtInsert);
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Registrasi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">MyApp</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="register.php">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-3 text-center">Registrasi</h3>

                    <?php if ($pesan !== ""): ?>
                        <div class="alert alert-info">
                            <?php echo htmlspecialchars($pesan); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Daftar</button>
                    </form>

                    <hr>
                    <p class="text-center mb-0">
                        Sudah punya akun?
                        <a href="login.php">Login di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (opsional untuk komponen interaktif) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
