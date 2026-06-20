<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        m.prodi
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    WHERE sp.id_surat = '$id'
"));

if (!$data) {
    echo "Data surat tidak ditemukan.";
    exit;
}

$semester = $data['semester'] ?? '-';

$base_url = "http://192.168.1.4/e-letters-saintek";
$link_verifikasi = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Preview Surat Magang</title>

<style>
body {
    background: #f3f4f6;
    font-family: "Times New Roman", serif;
    padding: 30px;
}

.surat {
    background: white;
    max-width: 850px;
    margin: auto;
    padding: 60px;
    line-height: 1.7;
    color: black;
}

table {
    width: 100%;
}

td {
    vertical-align: top;
    padding: 4px;
}

.ttd {
    width: 260px;
    margin-left: auto;
    text-align: center;
    margin-top: 40px;
}

.qr-img {
    width: 85px;
    height: 85px;
}
</style>
</head>

<body>

<div class="surat">

    <p>Perihal : Permohonan Izin Magang</p>

    <br>

    <p>Kepada Yth,</p>
    <p><strong><?= htmlspecialchars($data['surat_ditujukan']); ?></strong></p>
    <p>di-</p>
    <p style="margin-left:40px;"><?= htmlspecialchars($data['lokasi_magang']); ?></p>

    <br>

    <p>Assalamu’alaikum wr. wb.</p>
    <p>Saya yang bertanda tangan dibawah ini :</p>

    <table>
        <tr>
            <td width="220">Nama / NPM</td>
            <td width="10">:</td>
            <td><?= htmlspecialchars($data['nama_mhs']); ?> / <?= htmlspecialchars($data['npm']); ?></td>
        </tr>
        <tr>
            <td>Semester / Program Studi</td>
            <td>:</td>
            <td><?= htmlspecialchars($semester); ?> / <?= htmlspecialchars($data['prodi']); ?></td>
        </tr>
        <tr>
            <td>Lokasi Magang</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['lokasi_magang']); ?></td>
        </tr>
    </table>

    <br>

    <p>
        Bermaksud memohon surat izin magang dari pihak Fakultas Sains dan Teknologi
        UIN Raden Intan Lampung untuk melaksanakan kegiatan magang pada instansi
        yang Bapak/Ibu pimpin.
    </p>

    <p>Sebagai bahan pertimbangan, saya lampirkan:</p>

    <ol>
        <li>Foto Copy KTM</li>
        <li>Foto Copy Slip Pembayaran UKT Terakhir</li>
        <li>KHS Semester Terakhir</li>
    </ol>

    <p>Demikian surat permohonan ini saya buat. Atas perhatian Bapak/Ibu, saya ucapkan terima kasih.</p>

    <p>Wassalamu’alaikum wr. wb.</p>

    <br>

    <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

    <div class="ttd">
        <p>Pemohon</p>

        <?php if (!empty($data['dokumen_hash'])) { ?>
            <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">
        <?php } else { ?>
            <br><br><br>
        <?php } ?>

        <p><strong><?= htmlspecialchars($data['nama_mhs']); ?></strong></p>
        <p><?= htmlspecialchars($data['npm']); ?></p>
    </div>

</div>

</body>
</html>