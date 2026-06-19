<?php
session_start();
include "koneksi.php";

$id_jenis = $_POST['id_jenis'];
$semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
$judul_skripsi = mysqli_real_escape_string($koneksi, $_POST['judul_skripsi']);
$lokasi_penelitian = mysqli_real_escape_string($koneksi, $_POST['lokasi_penelitian']);
$surat_ditujukan = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);
$pembimbing_1 = $_POST['pembimbing_1'];
$pembimbing_2 = $_POST['pembimbing_2'];

$id_mhs = $_SESSION['id_mhs'];

$mhs = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM mahasiswa
    WHERE id_mhs = '$id_mhs'
"));

$jenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    WHERE id_jenis = '$id_jenis'
"));

$dospem1 = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM dosen
    WHERE id_dosen = '$pembimbing_1'
"));

$dospem2 = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT * FROM dosen
    WHERE id_dosen = '$pembimbing_2'
"));

$tanggal = date('Y-m-d H:i:s');

mysqli_query($koneksi, "
    INSERT INTO surat_pengajuan
    (
        id_mhs,
        id_jenis,
        nomor_surat,
        tanggal_pengajuan,
        status_akhir,
        judul_skripsi,
        lokasi_penelitian,
        surat_ditujukan,
        pembimbing_1,
        pembimbing_2,
        status_dospem1,
        status_dospem2,
        status_pimpinan
    )
    VALUES
    (
        '$id_mhs',
        '$id_jenis',
        '',
        '$tanggal',
        'Menunggu Dospem 1',
        '$judul_skripsi',
        '$lokasi_penelitian',
        '$surat_ditujukan',
        '$pembimbing_1',
        '$pembimbing_2',
        'Menunggu',
        'Menunggu',
        'Menunggu'
    )
");

$id_surat = mysqli_insert_id($koneksi);

$dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());

mysqli_query($koneksi, "
    UPDATE surat_pengajuan
    SET dokumen_hash = '$dokumen_hash'
    WHERE id_surat = '$id_surat'
");

$link_verifikasi = "http://192.168.1.4/e-letters-saintek/verifikasi_surat.php?hash=" . $dokumen_hash;

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Preview Surat</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body>

<div class="surat-preview">
    <div class="surat-kertas">

        <p>Perihal : Permohonan Surat Rekomendasi Riset</p>

        <br>

        <p>Kepada Yth,</p>
        <p><strong>Dekan Fakultas Sains dan Teknologi</strong></p>
        <p><strong>UIN Raden Intan Lampung</strong></p>
        <p>di-</p>
        <p style="margin-left:40px;">Bandar Lampung</p>

        <br>

        <p>Assalamu’alaikum wr. wb.</p>
        <p>Saya yang bertanda tangan dibawah ini :</p>

        <table class="surat-table">
            <tr>
                <td>Nama / NPM</td>
                <td>:</td>
                <td><?= htmlspecialchars($mhs['nama_mhs']); ?> / <?= htmlspecialchars($mhs['npm']); ?></td>
            </tr>
            <tr>
                <td>Semester / Program Studi</td>
                <td>:</td>
                <td><?= htmlspecialchars($semester); ?> / <?= htmlspecialchars($mhs['prodi']); ?></td>
            </tr>
            <tr>
                <td>Judul Skripsi</td>
                <td>:</td>
                <td><?= htmlspecialchars($_POST['judul_skripsi']); ?></td>
            </tr>
            <tr>
                <td>Lokasi Penelitian</td>
                <td>:</td>
                <td><?= htmlspecialchars($_POST['lokasi_penelitian']); ?></td>
            </tr>
            <tr>
                <td>Surat Ditujukan Kepada</td>
                <td>:</td>
                <td><?= htmlspecialchars($_POST['surat_ditujukan']); ?></td>
            </tr>
        </table>

        <br>

        <p>
            Bermaksud memohon surat Rekomendasi Riset dari pihak Fakultas,
            sebagai bahan pertimbangan Bapak, saya lampirkan:
        </p>

        <ol>
            <li>Proposal Penelitian</li>
            <li>Foto Copy Slip pembayaran SPP Terakhir</li>
            <li>KHS Semester terakhir</li>
        </ol>

        <p>Atas perhatian Bapak, saya ucapkan terima kasih</p>
        <p>Wassalamu’alaikum Wr. Wb.</p>

        <br>

        <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

        <div class="ttd-area">
            <div>
                <p>Mengetahui,</p>
                <p>Pembimbing I</p>
                <br><br><br>
                <p><strong><?= htmlspecialchars($dospem1['nama_dosen']); ?></strong></p>
                <p>NIP. <?= htmlspecialchars($dospem1['nip']); ?></p>
            </div>

            <div>
                <p>&nbsp;</p>
                <p>Pembimbing II</p>
                <br><br><br>
                <p><strong><?= htmlspecialchars($dospem2['nama_dosen']); ?></strong></p>
                <p>NIP. <?= htmlspecialchars($dospem2['nip']); ?></p>
            </div>

            <div>
                <p>&nbsp;</p>
                <p>Pemohon</p>

                <img src="<?= $qr_url; ?>" width="85" height="85" alt="QR Verifikasi">

                <p><strong><?= htmlspecialchars($mhs['nama_mhs']); ?></strong></p>
                <p><?= htmlspecialchars($mhs['npm']); ?></p>
            </div>
        </div>
    </div>

    <div class="preview-actions">
        <p>Status surat: <strong>Menunggu Dospem 1</strong></p>
        <p>QR pemohon sudah dibuat sebagai tanda verifikasi digital.</p>

        <a href="mhs_riwayat.php" class="btn-generate">Kirim Pengajuan</a>
        <a href="daftar_surat.php" class="btn-back-form">Kembali</a>
    </div>
</div>

</body>
</html>