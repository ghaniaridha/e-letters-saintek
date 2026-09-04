<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$role_admin = $_SESSION['role_admin'] ?? '';
$is_akademik = ($role_admin === 'admin2');

$id_jenis = isset($_GET['id_jenis']) ? (int)$_GET['id_jenis'] : 0;
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Array bulan untuk filter
$array_bulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

// nama surat untuk Judul
$query_nama = mysqli_query($koneksi, "SELECT nama_surat FROM jenis_surat WHERE id_jenis = '$id_jenis'");
$data_nama = mysqli_fetch_assoc($query_nama);
$nama_surat = $data_nama ? $data_nama['nama_surat'] : 'Data Tidak Ditemukan';

// Kueri Detail
$filter_role = $is_akademik ? " AND sp.id_mhs IS NOT NULL " : " AND sp.id_ormawa IS NOT NULL ";
$where_clause = " WHERE sp.status_akhir = 'Selesai' AND sp.waktu_selesai IS NOT NULL AND sp.id_jenis = '$id_jenis' $filter_role ";

if (!empty($filter_bulan)) {
    $where_clause .= " AND MONTH(sp.waktu_selesai) = '$filter_bulan' ";
}
if (!empty($filter_tahun)) {
    $where_clause .= " AND YEAR(sp.waktu_selesai) = '$filter_tahun' ";
}
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($koneksi, $search);
    $where_clause .= " AND (sp.kode_pelacakan LIKE '%$search_esc%' OR m.nama_mhs LIKE '%$search_esc%' OR m.npm LIKE '%$search_esc%' OR o.nama_ormawa LIKE '%$search_esc%') ";
}

$query_detail = mysqli_query($koneksi, "
    SELECT 
        sp.kode_pelacakan,
        sp.tanggal_pengajuan,
        sp.waktu_selesai,
        m.npm,
        DATEDIFF(sp.waktu_selesai, sp.tanggal_pengajuan) AS lama_hari,
        COALESCE(m.nama_mhs, o.nama_ormawa, 'Pemohon') AS nama_pemohon
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    $where_clause
    ORDER BY sp.waktu_selesai DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Statistik - <?= htmlspecialchars($nama_surat); ?></title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title detail-page-title">
                <div>
                    <h1>Rincian Waktu Pemrosesan</h1>
                    <p class="page-subtitle">Surat <b><?= htmlspecialchars($nama_surat); ?></b></p>
                </div>
                <div class="page-header-actions">
                    <button onclick="window.print()" class="btn-print">
                        <i class="fa-solid fa-print"></i> Cetak Laporan
                    </button>
                </div>
            </div>

            <div class="filter-container detail-filter-wrapper">
                <form method="GET" action="adm_statistik_detail.php" class="filter-form detail-filter-flex">
                    <input type="hidden" name="id_jenis" value="<?= $id_jenis; ?>">

                    <div class="filter-search-group">
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari Kode Pelacakan / Nama Pemohon / NPM..." class="filter-input-custom">
                    </div>

                    <div>
                        <select name="bulan" class="filter-input-custom">
                            <option value="">Semua Bulan</option>
                            <?php foreach ($array_bulan as $num => $name): ?>
                                <option value="<?= $num; ?>" <?= ($filter_bulan == $num) ? 'selected' : ''; ?>>
                                    <?= $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <select name="tahun" class="filter-input-custom">
                            <option value="">Semua Tahun</option>
                            <?php
                            $tahun_sekarang = date('Y');
                            for ($y = 2024; $y <= $tahun_sekarang; $y++):
                            ?>
                                <option value="<?= $y; ?>" <?= ($filter_tahun == $y) ? 'selected' : ''; ?>>
                                    <?= $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-detail-search">
                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                    </button>
                    <a href="adm_statistik_detail.php?id_jenis=<?= $id_jenis; ?>" class="btn-detail-reset">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </form>

                <table class="statistik-detail-table">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th class="text-center">Kode Pelacakan</th>
                            <th class="text-center">Pemohon</th>

                            <?php if ($is_akademik): ?>
                                <th class="text-center">NPM</th>
                            <?php endif; ?>

                            <th class="text-center">Tanggal Pengajuan</th>
                            <th class="text-center">Tanggal Selesai</th>
                            <th class="text-center">Waktu Proses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($query_detail) > 0) {
                            while ($row = mysqli_fetch_assoc($query_detail)) {
                                $lama_hari = $row['lama_hari'] == 0 ? '< 1 Hari' : $row['lama_hari'] . ' Hari';
                        ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['kode_pelacakan']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pemohon']); ?></td>

                                    <?php if ($is_akademik): ?>
                                        <td class="text-center"><?= htmlspecialchars($row['npm'] ?? '-'); ?></td>
                                    <?php endif; ?>

                                    <td class="text-center"><?= date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td class="text-center"><?= date('d M Y', strtotime($row['waktu_selesai'])); ?></td>
                                    <td class="text-center"><b><?= $lama_hari; ?></b></td>
                                </tr>
                        <?php
                            }
                        } else {
                            $colspan = $is_akademik ? 7 : 6;
                            echo "<tr><td colspan='$colspan' class='empty-detail-row'>Tidak ada rincian data ditemukan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>