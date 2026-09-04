<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$role_admin = $_SESSION['role_admin'] ?? '';
$is_akademik = ($role_admin === 'admin2');

$filter_role = $is_akademik ? " AND sp.id_mhs IS NOT NULL " : " AND sp.id_ormawa IS NOT NULL ";

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$where_clause = " WHERE sp.status_akhir = 'Selesai' AND sp.waktu_selesai IS NOT NULL $filter_role ";

if (!empty($filter_bulan)) {
    $where_clause .= " AND MONTH(sp.waktu_selesai) = '$filter_bulan' ";
}
if (!empty($filter_tahun)) {
    $where_clause .= " AND YEAR(sp.waktu_selesai) = '$filter_tahun' ";
}

// Query Statistik Grafik (Rata-rata)
$query_statistik = mysqli_query($koneksi, "
    SELECT 
        js.id_jenis,
        js.nama_surat,
        ROUND(AVG(DATEDIFF(sp.waktu_selesai, sp.tanggal_pengajuan)), 1) AS rata_rata_hari
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where_clause
    GROUP BY js.id_jenis
");

$label_surat = [];
$data_rata_rata = [];
$id_jenis_list = [];

while ($row = mysqli_fetch_assoc($query_statistik)) {
    $label_surat[] = $row['nama_surat'];
    $id_jenis_list[] = $row['id_jenis'];
    $rata_hari = (float)$row['rata_rata_hari'];
    $data_rata_rata[] = ($rata_hari == 0) ? 0.5 : $rata_hari;
}

$json_labels = json_encode($label_surat);
$json_data = json_encode($data_rata_rata);
$json_id_jenis = json_encode($id_jenis_list);

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Layanan</title>
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">
            <div class="page-title">
                <h1>Statistik Layanan Pemrosesan Surat</h1>
            </div>

            <div class="filter-container">
                <form method="GET" action="adm_statistik.php" class="filter-form">
                    <div>
                        <select name="bulan" id="bulan">
                            <option value="">Semua Bulan</option>
                            <?php foreach ($array_bulan as $num => $name): ?>
                                <option value="<?= $num; ?>" <?= ($filter_bulan == $num) ? 'selected' : ''; ?>><?= $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="tahun" id="tahun">
                            <option value="">Semua Tahun</option>
                            <?php
                            $tahun_sekarang = date('Y');
                            for ($y = 2024; $y <= $tahun_sekarang; $y++):
                            ?>
                                <option value="<?= $y; ?>" <?= ($filter_tahun == $y) ? 'selected' : ''; ?>><?= $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="adm_statistik.php" class="btn-reset-filter"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </form>

                <div class="chart-container">
                    <?php if (empty($label_surat)): ?>
                        <div class="empty-chart-message">
                            <i class="fa-solid fa-folder-open fa-3x"></i>
                            <p>Tidak ada data surat selesai pada periode ini.</p>
                        </div>
                    <?php else: ?>
                        <canvas id="grafikSLA"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include "adm_footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labelSurat = <?= $json_labels; ?>;
        const dataRataRata = <?= $json_data; ?>;
        const idJenisList = <?= $json_id_jenis; ?>;

        const currentBulan = '<?= $filter_bulan ?>';
        const currentTahun = '<?= $filter_tahun ?>';

        if (labelSurat.length > 0) {
            const ctx = document.getElementById('grafikSLA').getContext('2d');
            const grafikSLA = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelSurat,
                    datasets: [{
                        label: 'Rata-rata Waktu (Hari)',
                        data: dataRataRata,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (event, elements, chart) => {
                        if (elements.length > 0) {
                            const dataIndex = elements[0].index;
                            const idJenis = idJenisList[dataIndex];

                            let detailUrl = `adm_statistik_detail.php?id_jenis=${idJenis}`;
                            if (currentBulan) detailUrl += `&bulan=${currentBulan}`;
                            if (currentTahun) detailUrl += `&tahun=${currentTahun}`;

                            window.location.href = detailUrl;
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Hari',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Jenis Surat',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let val = context.raw;
                                    if (val === 0.5) return 'Selesai di hari yang sama (< 1 Hari)';
                                    return val + ' Hari';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>