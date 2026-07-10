<?php
include "koneksi.php";

$hash = $_GET['hash'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        sp.*, 
        m.nama_mhs, m.npm, 
        js.nama_surat,
        p.nama_prodi
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE sp.dokumen_hash = '$hash'
"));

function formatTanggalIndo($tanggal_db)
{
    $nama_bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal_db);
    $hari = date('d', $timestamp);
    $bulan = $nama_bulan[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    return "$hari $bulan $tahun, $jam";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi QR Code</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="verif.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <div class="verifikasi-card">
        <?php if ($data) { ?>
            <div class="status-box valid">
                <i class="fa-solid fa-circle-check"></i>
                <h2>TERVERIFIKASI</h2>
                <p class="status-message">Dokumen ini sah dan dikeluarkan oleh SIPATU FST UIN RIL</p>
            </div>

            <div class="info-group">
                <h4>Informasi Dokumen</h4>
                <p><strong>Jenis Surat :</strong> <?= htmlspecialchars($data['nama_surat']); ?></p>
                <p><strong>Kode Dokumen :</strong> <span class="hash-code"><?= substr($data['dokumen_hash'], 0, 16); ?>...</span></p>
            </div>

            <div class="info-group">
                <h4>Informasi Mahasiswa</h4>
                <p><strong>Nama :</strong> <?= htmlspecialchars($data['nama_mhs']); ?></p>
                <p><strong>NPM :</strong> <?= htmlspecialchars($data['npm']); ?></p>
                <p><strong>Prodi :</strong> <?= htmlspecialchars($data['nama_prodi']); ?></p>
            </div>

            <div class="footer-meta">
                <strong>Dibuat pada:</strong>
                <?= formatTanggalIndo($data['tanggal_pengajuan']); ?> WIB<br>

                <strong>Waktu Pindai:</strong>
                <?php
                $bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo date('d') . ' ' . $bulan_indo[date('n') - 1] . ' ' . date('Y, H:i:s');
                ?> WIB
            </div>

        <?php } else { ?>
            <div class="status-box invalid">
                <i class="fa-solid fa-circle-xmark"></i>
                <h2>DOKUMEN TIDAK VALID</h2>
                <p>Data tidak ditemukan. Dokumen mungkin palsu atau telah dihapus.</p>
            </div>
        <?php } ?>
    </div>
</body>

</html>