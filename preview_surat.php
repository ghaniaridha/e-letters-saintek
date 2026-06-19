<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        m.prodi,
        d1.nama_dosen AS nama_dospem1,
        d1.nip AS nip_dospem1,
        d2.nama_dosen AS nama_dospem2,
        d2.nip AS nip_dospem2
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN dosen d1 ON sp.pembimbing_1 = d1.id_dosen
    LEFT JOIN dosen d2 ON sp.pembimbing_2 = d2.id_dosen
    WHERE sp.id_surat = '$id'
"));

if (!$data) {
    echo "Data surat tidak ditemukan.";
    exit;
}

$link_verifikasi = "http://192.168.1.4/e-letters-saintek/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Preview Surat</title>
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
    display: flex;
    justify-content: space-between;
    text-align: center;
    margin-top: 30px;
}

.ttd div {
    width: 30%;
}

.qr-img {
    width: 85px;
    height: 85px;
    object-fit: contain;
    margin-bottom: 5px;
}
</style>
</head>
<body>

<div class="surat">
    <p>Perihal : Permohonan Surat Rekomendasi Riset</p>

    <br>

    <p>Kepada Yth,</p>
    <p><b>Dekan Fakultas Sains dan Teknologi</b></p>
    <p><b>UIN Raden Intan Lampung</b></p>
    <p>di-</p>
    <p style="margin-left:40px;">Bandar Lampung</p>

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
            <td>- / <?= htmlspecialchars($data['prodi']); ?></td>
        </tr>
        <tr>
            <td>Judul Skripsi</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['judul_skripsi']); ?></td>
        </tr>
        <tr>
            <td>Lokasi Penelitian</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['lokasi_penelitian']); ?></td>
        </tr>
        <tr>
            <td>Surat Ditujukan Kepada</td>
            <td>:</td>
            <td><?= htmlspecialchars($data['surat_ditujukan']); ?></td>
        </tr>
    </table>

    <br>

    <p>
        Bermaksud memohon surat Rekomendasi Riset dari pihak Fakultas,
        sebagai bahan pertimbangan Bapak, saya lampirkan:
    </p>

    <ol>
        <li>Proposal Penelitian</li>
        <li>Foto Copy Slip pembayaran UKT Terakhir</li>
        <li>KHS Semester terakhir</li>
    </ol>

    <p>Atas perhatian Bapak, saya ucapkan terima kasih</p>
    <p>Wassalamu’alaikum Wr. Wb.</p>

    <br>

    <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

    <div class="ttd">
        <div>
            <p>Mengetahui,</p>
            <p>Pembimbing I</p>
            <br><br><br>
            <p><b><?= htmlspecialchars($data['nama_dospem1']); ?></b></p>
            <p>NIP. <?= htmlspecialchars($data['nip_dospem1']); ?></p>
        </div>

        <div>
            <p>&nbsp;</p>
            <p>Pembimbing II</p>
            <br><br><br>
            <p><b><?= htmlspecialchars($data['nama_dospem2']); ?></b></p>
            <p>NIP. <?= htmlspecialchars($data['nip_dospem2']); ?></p>
        </div>

        <div>
            <p>&nbsp;</p>
            <p>Pemohon</p>

            <?php if (!empty($data['dokumen_hash'])) { ?>
                <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">
            <?php } else { ?>
                <br><br><br>
            <?php } ?>

            <p><b><?= htmlspecialchars($data['nama_mhs']); ?></b></p>
            <p><?= htmlspecialchars($data['npm']); ?></p>
        </div>
    </div>
</div>

</body>
</html>