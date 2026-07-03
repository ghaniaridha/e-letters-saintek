<?php
$koneksi = mysqli_connect("localhost", "root", "", "e_letters_saintek");
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");

date_default_timezone_set('Asia/Jakarta');

define('STATUS_MENUNGGU', 0);
define('STATUS_AKTIF', 1);
define('STATUS_NONAKTIF', 2);
