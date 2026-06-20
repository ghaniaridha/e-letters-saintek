<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_jenis = $_GET['id_jenis'] ?? 1;
$id_mhs = $_SESSION['id_mhs'];

$surat = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

if (!$surat) {
    echo "<script>alert('Jenis surat tidak ditemukan'); window.location='daftar_surat.php';</script>";
    exit;
}

$mhs = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM mahasiswa
    WHERE id_mhs = '$id_mhs'
"));

if (!$mhs || empty($mhs['id_prodi'])) {
    echo "<script>alert('Data prodi mahasiswa belum diatur. Hubungi admin.'); window.location='daftar_surat.php';</script>";
    exit;
}

$id_prodi = $mhs['id_prodi'];

$dosen1 = mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    WHERE role_akses = 'Dosen'
    AND id_prodi = '$id_prodi'
    ORDER BY nama_dosen ASC
");

$dosen2 = mysqli_query($koneksi, "
    SELECT *
    FROM dosen
    WHERE role_akses = 'Dosen'
    AND id_prodi = '$id_prodi'
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

        <p>
            Silakan lengkapi data berikut untuk membuat permohonan surat riset.
        </p>

        <form action="proses_generate_surat.php"
              method="POST"
              enctype="multipart/form-data">

            <input type="hidden"
                   name="id_jenis"
                   value="<?= htmlspecialchars($id_jenis); ?>">

            <div class="form-group">
                <label>Semester</label>
                <input type="text"
                       name="semester"
                       placeholder="Contoh: Semester 8"
                       required>
            </div>

            <div class="form-group">
                <label>Judul Skripsi</label>
                <textarea name="judul_skripsi"
                          placeholder="Masukkan judul skripsi"
                          required></textarea>
            </div>

            <div class="form-group">
                <label>Lokasi Penelitian</label>
                <input type="text"
                       name="lokasi_penelitian"
                       placeholder="Contoh: Kantor Fakultas Sains dan Teknologi"
                       required>
            </div>

            <div class="form-group">
                <label>Surat Ditujukan Kepada</label>
                <input type="text"
                       name="surat_ditujukan"
                       placeholder="Contoh: Kepala Instansi / Perusahaan"
                       required>
            </div>

            <div class="form-group">
                <label>Pembimbing I</label>
                <select name="pembimbing_1" required>
                    <option value="">Pilih Pembimbing I</option>

                    <?php if ($dosen1 && mysqli_num_rows($dosen1) > 0) { ?>
                        <?php while ($d1 = mysqli_fetch_assoc($dosen1)) { ?>
                            <option value="<?= $d1['id_dosen']; ?>">
                                <?= htmlspecialchars($d1['nama_dosen']); ?>
                                - NIP.
                                <?= htmlspecialchars($d1['nip']); ?>
                            </option>
                        <?php } ?>
                    <?php } else { ?>
                        <option value="" disabled>
                            Belum ada dosen untuk prodi ini
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pembimbing II</label>
                <select name="pembimbing_2" required>
                    <option value="">Pilih Pembimbing II</option>

                    <?php if ($dosen2 && mysqli_num_rows($dosen2) > 0) { ?>
                        <?php while ($d2 = mysqli_fetch_assoc($dosen2)) { ?>
                            <option value="<?= $d2['id_dosen']; ?>">
                                <?= htmlspecialchars($d2['nama_dosen']); ?>
                                - NIP.
                                <?= htmlspecialchars($d2['nip']); ?>
                            </option>
                        <?php } ?>
                    <?php } else { ?>
                        <option value="" disabled>
                            Belum ada dosen untuk prodi ini
                        </option>
                    <?php } ?>
                </select>
            </div>

            <hr style="margin:25px 0;">

            <h3>Dokumen Pendukung</h3>

            <div class="form-group">
                <label>Proposal Penelitian</label>
                <input type="file"
                       name="proposal_penelitian"
                       accept=".pdf,.jpg,.jpeg,.png"
                       required>
            </div>

            <div class="form-group">
                <label>KHS Semester Terakhir</label>
                <input type="file"
                       name="khs"
                       accept=".pdf,.jpg,.jpeg,.png"
                       required>
            </div>

            <div class="form-group">
                <label>Bukti Pembayaran UKT</label>
                <input type="file"
                       name="bukti_ukt"
                       accept=".pdf,.jpg,.jpeg,.png"
                       required>
            </div>

            <button type="submit" class="btn-generate">
                Generate Surat
            </button>

            <a href="daftar_surat.php"
               class="btn-back-form">
                Kembali
            </a>

        </form>

    </div>
</div>

</body>
</html>