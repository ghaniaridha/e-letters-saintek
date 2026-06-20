<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_jenis = $_GET['id_jenis'] ?? 4;

$surat = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

if (!$surat) {
    echo "<script>alert('Jenis surat tidak ditemukan'); window.location='daftar_surat.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form <?= htmlspecialchars($surat['nama_surat']); ?></title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body>

<div class="generate-wrapper">
    <div class="generate-card">

        <h2><?= htmlspecialchars($surat['nama_surat']); ?></h2>
        <p>Silakan lengkapi data berikut untuk membuat permohonan izin magang.</p>

        <form action="proses_generate_magang.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_jenis" value="<?= htmlspecialchars($id_jenis); ?>">

            <div class="form-group">
                <label>Semester</label>
                <input type="text" name="semester" placeholder="Contoh: Semester 6" required>
            </div>

            <div class="form-group">
                <label>Lokasi Magang</label>
                <input type="text" name="lokasi_magang" placeholder="Contoh: PT Telkom Indonesia" required>
            </div>

            <div class="form-group">
                <label>Surat Ditujukan Kepada</label>
                <input type="text" name="surat_ditujukan" placeholder="Contoh: Kepala PT Telkom Indonesia" required>
            </div>

            <hr style="margin:25px 0;">

            <h3>Dokumen Pendukung</h3>

            <div class="form-group">
                <label>Upload KTM</label>
                <input type="file" name="ktm" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div class="form-group">
                <label>Upload KHS Semester Terakhir</label>
                <input type="file" name="khs" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div class="form-group">
                <label>Upload Slip Pembayaran UKT</label>
                <input type="file" name="bukti_ukt" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <button type="submit" class="btn-generate">
                Generate Surat Magang
            </button>

            <a href="daftar_surat.php" class="btn-back-form">
                Kembali
            </a>
        </form>

    </div>
</div>

</body>
</html>