<?php
include "koneksi.php";

$hash = $_GET['hash'] ?? '';

// 1. Cek apakah yang di-scan adalah hash milik Ketua atau Sekretaris di tabel ormawa
$query_ormawa = mysqli_query($koneksi, "
    SELECT * FROM ormawa 
    WHERE kode_ttd_qr_ketua = '$hash' OR kode_ttd_qr_sekretaris = '$hash'
");
$data_ormawa = mysqli_fetch_assoc($query_ormawa);

// 2. Cek apakah yang di-scan adalah hash milik Dosen Pembina di tabel dosen
$query_dosen = mysqli_query($koneksi, "
    SELECT * FROM dosen 
    WHERE kode_ttd_qr = '$hash'
");
$data_dosen = mysqli_fetch_assoc($query_dosen);

// Menentukan validitas dan menyusun data dinamis sesuai struktur SIPATU
$valid = false;
$nama_surat = "Surat Permohonan Peminjaman Ruangan";
$nama_pihak = "";
$nomor_identitas = "";
$jabatan = "";

if ($data_ormawa) {
    $valid = true;
    if ($data_ormawa['kode_ttd_qr_ketua'] == $hash) {
        $nama_pihak = $data_ormawa['nama_ketua'];
        $nomor_identitas = $data_ormawa['npm_ketua'];
        $jabatan = "Ketua Umum " . $data_ormawa['nama_ormawa'];
    } else {
        $nama_pihak = $data_ormawa['nama_sekretaris'];
        $nomor_identitas = $data_ormawa['npm_sekretaris'];
        $jabatan = "Sekretaris Umum " . $data_ormawa['nama_ormawa'];
    }
} elseif ($data_dosen) {
    $valid = true;
    $nama_pihak = $data_dosen['nama_dosen'];
    $nomor_identitas = $data_dosen['nip'];
    $jabatan = "Dosen Pembina " . ($data_dosen['nama_ormawa'] ?? 'Ormawa');
}

function formatTanggalIndo($tanggal)
{
    $nama_bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
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
        <?php if ($valid) { ?>
            <!-- BOX STATUS TERVERIFIKASI (HIJAU) -->
            <div class="status-box valid">
                <i class="fa-solid fa-circle-check"></i>
                <h2>TERVERIFIKASI</h2>
                <p class="status-message">Dokumen ini sah dan dikeluarkan oleh E-LETTERS SAINTEK UIN RIL</p>
            </div>

            <!-- INFORMASI DOKUMEN -->
            <div class="info-group">
                <h4>Informasi Dokumen</h4>
                <p><strong>Jenis Surat :</strong> <?= htmlspecialchars($nama_surat); ?></p>
                <p><strong>Kode Dokumen :</strong> <span class="hash-code"><?= substr($hash, 0, 16); ?>...</span></p>
            </div>

            <!-- INFORMASI PENANDATANGAN (Menggantikan Info Mahasiswa karena ini TTD Elektronik) -->
            <div class="info-group">
                <h4>Informasi Penandatangan</h4>
                <p><strong>Nama :</strong> <?= htmlspecialchars($nama_pihak); ?></p>
                <p><strong>NPM / NIP :</strong> <?= htmlspecialchars($nomor_identitas); ?></p>
                <p><strong>Jabatan :</strong> <?= htmlspecialchars($jabatan); ?></p>
            </div>

            <!-- METADATA WAKTU -->
            <div class="footer-meta">
                <strong>Dibuat pada:</strong>
                <?= formatTanggalIndo(date('Y-m-d H:i:s')); ?> WIB<br>

                <strong>Waktu Pindai:</strong>
                <?php
                $bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo date('d') . ' ' . $bulan_indo[date('n') - 1] . ' ' . date('Y, H:i:s');
                ?> WIB
            </div>

        <?php } else { ?>
            <!-- BOX STATUS INVALID (MERAH) -->
            <div class="status-box invalid">
                <i class="fa-solid fa-circle-xmark"></i>
                <h2>DOKUMEN TIDAK VALID</h2>
                <p>Data tidak ditemukan. Dokumen mungkin palsu atau telah dihapus.</p>
            </div>
        <?php } ?>
    </div>
</body>

</html>