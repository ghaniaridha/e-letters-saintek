<?php
include "koneksi.php";

if (isset($_GET['prodi'])) {
    $id_prodi = mysqli_real_escape_string($koneksi, $_GET['prodi']);

    $query = "SELECT id_dosen, nama_dosen FROM dosen WHERE id_prodi = '$id_prodi' ORDER BY nama_dosen ASC";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        echo '<option value="" disabled selected>Pilih Pembimbing Akademik</option>';
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . $row['id_dosen'] . '">' . htmlspecialchars($row['nama_dosen']) . '</option>';
        }
    } else {
        echo '<option value="" disabled selected>Belum ada data dosen</option>';
    }
}
