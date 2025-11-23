<?php
session_start();
require "koneksi.php";

$username = $_POST['username'];
$password = md5($_POST['password']); // cocok dengan hash di DB

// cek user
$sql = "SELECT * FROM user_tbl WHERE username='$username' AND password='$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);

    // simpan session
    $_SESSION['login'] = true;
    $_SESSION['username'] = $row['username'];
    $_SESSION['name'] = $row['name'];

    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php?error=1");
    exit;
}
?>
