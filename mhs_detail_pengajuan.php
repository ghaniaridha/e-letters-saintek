<?php
session_start();
include "koneksi.php";

// 1. Cek Login
if (!isset($_SESSION['npm'])) {
    header("Location: index.php");
    exit;
}

// 2. Ambil ID dari URL
$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 3. Ambil data dari database
// Kita gunakan JOIN agar bisa mengambil nama jenis surat dan data mahasiswa
$query = mysqli_query($koneksi, "
    SELECT sp.*, js.nama_surat, m.nama_mhs, m.npm
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    WHERE sp.id_surat = '$id_surat' AND sp.id_mhs = '{$_SESSION['id_mhs']}'
");

$data = mysqli_fetch_assoc($query);

// Jika surat tidak ditemukan atau bukan milik mahasiswa yang login
if (!$data) {
    echo "<script>alert('Data tidak ditemukan atau akses ditolak!'); window.location='mhs_riwayat.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pengajuan Surat</title>
    <link rel="stylesheet" href="style.css"> <!-- Sesuaikan dengan file CSS Anda -->
    <style>
        .container-detail { width: 80%; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .table-detail { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table-detail td { padding: 12px; border-bottom: 1px solid #ddd; }
        .status-badge { padding: 5px 10px; border-radius: 4px; color: #fff; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6c757d; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container-detail">
    <h2>Detail Pengajuan Surat</h2>
    <hr>
    <table class="table-detail">
        <tr>
            <td width="200"><strong>Nama Mahasiswa</strong></td>
            <td>: <?= htmlspecialchars($data['nama_mhs']); ?></td>
        </tr>
        <tr>
            <td><strong>NPM</strong></td>
            <td>: <?= htmlspecialchars($data['npm']); ?></td>
        </tr>
        <tr>
            <td><strong>Jenis Surat</strong></td>
            <td>: <?= htmlspecialchars($data['nama_surat']); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Pengajuan</strong></td>
            <td>: <?= $data['tanggal_pengajuan']; ?></td>
        </tr>
        <tr>
            <td><strong>Status Akhir</strong></td>
            <td>: 
                <span class="status-badge" style="background: <?= ($data['status_akhir'] == 'Ditolak Admin') ? '#dc3545' : '#28a745'; ?>">
                    <?= $data['status_akhir']; ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong>Catatan Admin</strong></td>
            <td>: <?= !empty($data['catatan']) ? htmlspecialchars($data['catatan']) : 'Tidak ada catatan'; ?></td>
        </tr>
    </table>

    <a href="mhs_riwayat.php" class="btn-back">Kembali ke Riwayat</a>
</div>

</body>
</html>