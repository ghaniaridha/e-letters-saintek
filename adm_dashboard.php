<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$role_admin = $_SESSION['role_admin'] ?? '';
$is_akademik = ($role_admin === 'admin2');

$filter_role = $is_akademik
    ? " AND sp.id_mhs IS NOT NULL "
    : " AND sp.id_ormawa IS NOT NULL AND sp.status_akhir NOT LIKE '%Pembina%' AND sp.status_akhir NOT LIKE '%Pimpinan%' ";

$tahun_ini = date('Y');

// ==========================================
// QUERY KARTU INDIKATOR (CARD)
// ==========================================

// A. Query Template (Khusus Admin Akademik)
$query_template = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jenis_surat WHERE file_template != '' AND file_template IS NOT NULL");
$jumlah_template = mysqli_fetch_assoc($query_template)['total'] ?? 0;

// B. Query Permohonan Masuk
$query_masuk = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp WHERE sp.status_akhir = 'Menunggu Admin' $filter_role");
$jumlah_masuk = mysqli_fetch_assoc($query_masuk)['total'] ?? 0;

// C. Query Pemrosesan 
if ($is_akademik) {
    $query_diproses = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp WHERE sp.status_akhir NOT IN ('Selesai', 'Menunggu Admin') AND sp.status_akhir NOT LIKE '%Ditolak%' $filter_role");
} else {
    $query_diproses = mysqli_query($koneksi, "
        SELECT COUNT(*) as total 
        FROM surat_pengajuan sp 
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_ormawa IS NOT NULL 
          AND (
              sp.status_akhir LIKE '%Disposisi%' 
              OR sp.status_akhir LIKE '%Audiensi%' 
              OR sp.status_akhir LIKE '%Penjadwalan%'
          )
    ");
}
$jumlah_diproses = mysqli_fetch_assoc($query_diproses)['total'] ?? 0;

// D. Query Pengguna
$query_mhs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$jml_mhs = mysqli_fetch_assoc($query_mhs)['total'] ?? 0;

$query_dosen = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM dosen");
$jml_dosen = mysqli_fetch_assoc($query_dosen)['total'] ?? 0;

$query_ormawa = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM ormawa");
$jml_ormawa = mysqli_fetch_assoc($query_ormawa)['total'] ?? 0;

$jumlah_pengguna = $jml_mhs + $jml_dosen + $jml_ormawa;

// E. Query Surat Keluar / Selesai 
if ($is_akademik) {
    $query_keluar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp WHERE sp.nomor_surat != '' AND sp.nomor_surat IS NOT NULL $filter_role");
} else {
    $query_keluar = mysqli_query($koneksi, "
        SELECT COUNT(*) as total 
        FROM surat_pengajuan sp 
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_ormawa IS NOT NULL 
          AND (sp.status_akhir = 'Selesai' OR sp.status_akhir LIKE '%Ditolak Admin%')
    ");
}
$jumlah_keluar = mysqli_fetch_assoc($query_keluar)['total'] ?? 0;

// ==========================================
// QUERY GRAFIK (CHART.JS) DENGAN FILTER
// ==========================================
$tahun_ini = date('Y');

// A. Grafik Batang: Tren Surat per Bulan
$query_bulan = mysqli_query($koneksi, "
    SELECT MONTH(sp.tanggal_pengajuan) as bulan, COUNT(*) as total 
    FROM surat_pengajuan sp 
    WHERE YEAR(sp.tanggal_pengajuan) = '$tahun_ini' $filter_role 
    GROUP BY MONTH(sp.tanggal_pengajuan)
");
$data_bulan = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($query_bulan)) {
    $data_bulan[$row['bulan']] = (int)$row['total'];
}
$json_data_bulan = json_encode(array_values($data_bulan));

// B. Grafik Doughnut 1: Status Permohonan
$query_status = mysqli_query($koneksi, "
    SELECT 
        CASE 
            WHEN sp.status_akhir = 'Selesai' THEN 'Selesai'
            WHEN sp.status_akhir LIKE '%Ditolak%' THEN 'Ditolak'
            ELSE 'Diproses'
        END as kategori_status,
        COUNT(*) as total
    FROM surat_pengajuan sp
    WHERE 1=1 $filter_role
    GROUP BY kategori_status
");
$label_status = [];
$data_status = [];
while ($row = mysqli_fetch_assoc($query_status)) {
    $label_status[] = $row['kategori_status'];
    $data_status[] = (int)$row['total'];
}
$json_label_status = json_encode($label_status);
$json_data_status = json_encode($data_status);

// C. Grafik Doughnut 2: Komposisi Jenis Surat
$query_jenis = mysqli_query($koneksi, "
    SELECT js.nama_surat, COUNT(sp.id_surat) as total
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE 1=1 $filter_role
    GROUP BY js.id_jenis
");
$label_jenis = [];
$data_jenis = [];
while ($row = mysqli_fetch_assoc($query_jenis)) {
    $label_jenis[] = $row['nama_surat'];
    $data_jenis[] = (int)$row['total'];
}
$json_label_jenis = json_encode($label_jenis);
$json_data_jenis = json_encode($data_jenis);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Dasbor Admin</h1>
                <?php
                $namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
                ?>
                <p>Hallo <?= $namaLengkap ?>! Selamat datang kembali di Dasbor Administrator.</p>
            </div>

            <div class="card-grid">
                <a href="<?= $is_akademik ? 'adm_permohonan_akademik.php' : 'adm_permohonan_ormawa.php' ?>" class="card-link">
                    <div class="card">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <h3><?= $jumlah_masuk; ?></h3>
                        <p>Permohonan Masuk</p>
                    </div>
                </a>

                <?php if ($is_akademik): ?>
                    <a href="adm_template_surat.php" class="card-link">
                        <div class="card">
                            <i class="fa-solid fa-file-word"></i>
                            <h3><?= $jumlah_template; ?></h3>
                            <p>Template Surat</p>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="adm_riwayat_ormawa.php" class="card-link">
                        <div class="card">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <h3><?= $jumlah_diproses; ?></h3>
                            <p>Pemrosesan Surat</p>
                        </div>
                    </a>
                <?php endif; ?>

                <div class="card card-static">
                    <i class="fa-solid fa-users"></i>
                    <h3><?= $jumlah_pengguna; ?></h3>
                    <p>Pengguna</p>
                </div>

                <a href="<?= $is_akademik ? 'adm_laporan_surat.php' : 'adm_laporan_ormawa.php' ?>" class="card-link">
                    <div class="card">
                        <i class="fa-solid fa-file-export"></i>
                        <h3><?= $jumlah_keluar; ?></h3>
                        <p>Surat Keluar</p>
                    </div>
                </a>
            </div>

            <!-- DOUGHNUT CHARTS -->
            <div class="charts-row">
                <div class="chart-container sub-chart">
                    <div class="chart-title">Status Permohonan</div>
                    <canvas id="statusChart"></canvas>
                </div>

                <div class="chart-container sub-chart">
                    <div class="chart-title">Komposisi Jenis Surat</div>
                    <canvas id="jenisChart"></canvas>
                </div>
            </div>

            <!-- GRAFIK TREN PER BULAN -->
            <div class="chart-container main-chart">
                <div class="chart-title">Statistik Permohonan Surat Tahun <?= $tahun_ini; ?></div>
                <canvas id="suratChart"></canvas>
            </div>
        </main>
    </div>

    <?php include "adm_footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 1. Inisialisasi Grafik Batang (Tren Bulanan)
        const ctxSurat = document.getElementById('suratChart').getContext('2d');
        new Chart(ctxSurat, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Jumlah Permohonan',
                    data: <?= $json_data_bulan; ?>,
                    backgroundColor: '#ffcc00',
                    borderColor: '#2c4664',
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 2. Inisialisasi Grafik Doughnut (Status) 
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        const statusLabels = <?= $json_label_status; ?>;
        const statusData = <?= $json_data_status; ?>;

        const statusColors = statusLabels.map(label => {
            if (label === 'Selesai') return '#10b981';
            if (label === 'Ditolak') return '#ef4444';
            return '#f59e0b';
        });

        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 3. Inisialisasi Grafik Doughnut (Jenis Surat)
        const ctxJenis = document.getElementById('jenisChart').getContext('2d');
        new Chart(ctxJenis, {
            type: 'doughnut',
            data: {
                labels: <?= $json_label_jenis; ?>,
                datasets: [{
                    data: <?= $json_data_jenis; ?>,
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899', '#14b8a6', '#f59e0b', '#10b981'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>