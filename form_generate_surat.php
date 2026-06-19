<?php
session_start();
include "koneksi.php";

$id_jenis = $_GET['id_jenis'] ?? 1;

$surat = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

if (!$surat) {
    echo "<script>alert('Jenis surat tidak ditemukan'); window.location='daftar_surat.php';</script>";
    exit;
}

$dosen1 = mysqli_query($koneksi, "
    SELECT * FROM dosen
    ORDER BY nama_dosen ASC
");

$dosen2 = mysqli_query($koneksi, "
    SELECT * FROM dosen
    ORDER BY nama_dosen ASC
");
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
        <p>Silakan lengkapi data berikut untuk membuat permohonan surat riset.</p>

        <form action="proses_generate_surat.php" method="POST">
            <input type="hidden" name="id_jenis" value="<?= htmlspecialchars($id_jenis); ?>">

            <div class="form-group">
                <label>Semester</label>
                <input type="text" name="semester" placeholder="Contoh: Semester 8" required>
            </div>

            <div class="form-group">
                <label>Judul Skripsi</label>
                <textarea name="judul_skripsi" required placeholder="Masukkan judul skripsi"></textarea>
            </div>

            <div class="form-group">
                <label>Lokasi Penelitian</label>
                <input type="text" name="lokasi_penelitian" required placeholder="Contoh: Kantor Fakultas Sains dan Teknologi">
            </div>

            <div class="form-group">
                <label>Surat Ditujukan Kepada</label>
                <input type="text" name="surat_ditujukan" required placeholder="Contoh: Kepala Instansi / Perusahaan">
            </div>

            <div class="form-group">
                <label>Pembimbing I</label>
                <select name="pembimbing_1" required>
                    <option value="">Pilih Pembimbing I</option>
                    <?php while ($d1 = mysqli_fetch_assoc($dosen1)) { ?>
                        <option value="<?= $d1['id_dosen']; ?>">
                            <?= htmlspecialchars($d1['nama_dosen']); ?> - NIP. <?= htmlspecialchars($d1['nip']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pembimbing II</label>
                <select name="pembimbing_2" required>
                    <option value="">Pilih Pembimbing II</option>
                    <?php while ($d2 = mysqli_fetch_assoc($dosen2)) { ?>
                        <option value="<?= $d2['id_dosen']; ?>">
                            <?= htmlspecialchars($d2['nama_dosen']); ?> - NIP. <?= htmlspecialchars($d2['nip']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <button type="submit" class="btn-generate">
                Generate Surat
            </button>

            <a href="daftar_surat.php" class="btn-back-form">
                Kembali
            </a>
        </form>
    </div>
</div>

</body>
</html>