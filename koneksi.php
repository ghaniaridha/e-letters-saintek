<?php
$koneksi = mysqli_connect("localhost", "root", "", "e_letters_saintek");
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
