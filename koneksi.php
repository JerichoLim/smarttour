<?php
$host     = "localhost";
$user     = "root";
$password = "";
$dbname   = "smart_tour";

$koneksi = mysqli_connect($host, $user, $password, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
