<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$keyword  = $_GET['keyword'] ?? '';
$id_jenis = $_GET['id_jenis'] ?? '';

$where = "WHERE sp.id_ormawa IS NOT NULL 
          AND (sp.status_akhir = 'Selesai' OR sp.status_akhir LIKE '%Ditolak Admin%')";

if ($keyword != '') {
    $keywordAman = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (o.nama_ormawa LIKE '%$keywordAman%' OR js.nama_surat LIKE '%$keywordAman%')";
}

if ($id_jenis != '') {
    $idJenisAman = (int) $id_jenis;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

$params = [];
if ($keyword != '') $params['keyword'] = $keyword;
if ($id_jenis != '') $params['id_jenis'] = $id_jenis;
$query_string = !empty($params) ? '&' . http_build_query($params) : '';

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$query_count = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
");
$row_count = mysqli_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$query = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        o.nama_ormawa,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
");

$jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat LIKE '%Dana%' OR nama_surat LIKE '%Ruangan%' ORDER BY nama_surat ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Surat Ormawa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Laporan Surat Ormawa</h1>
                <p>Arsip permohonan peminjaman ruangan dan pengajuan dana ormawa.</p>
            </div>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section">
                    <input type="text" name="keyword" placeholder="Cari Nama Ormawa..." value="<?= htmlspecialchars($keyword ?? '') ?>">

                    <select name="id_jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php while ($j = mysqli_fetch_assoc($jenisSurat)) { ?>
                            <option value="<?= $j['id_jenis']; ?>" <?= $id_jenis == $j['id_jenis'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($j['nama_surat']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>

                    <a href="adm_laporan_ormawa.php" class="btn-reset-filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal & Waktu Pengajuan</th>
                            <th>Nama Organisasi</th>
                            <th>Jenis Surat</th>
                            <th>Status Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?> WIB</td>
                                    <td><?= htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td>
                                        <?php if (strpos(strtolower($row['status_akhir']), 'ditolak') !== false) { ?>
                                            <span class="badge-danger"><?= htmlspecialchars($row['status_akhir']); ?></span>
                                        <?php } else { ?>
                                            <span class="badge-success"><?= htmlspecialchars($row['status_akhir']); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="adm_permohonan_ormawa.php?detail=<?= $row['id_surat']; ?>&asal=laporan" class="btn btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6" class="text-center" style="text-align:center; padding: 20px;">
                                    Data arsip surat ormawa tidak ditemukan.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <!-- PAGINATION -->
                <?php if (isset($total_halaman) && $total_halaman > 0): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($halaman > 1): ?>
                                <li><a href="?page=<?= $halaman - 1 ?><?= $query_string ?>">Sebelumnya</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Sebelumnya</span></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                <?php if ($i == $halaman): ?>
                                    <li class="active"><span><?= $i ?></span></li>
                                <?php else: ?>
                                    <li><a href="?page=<?= $i ?><?= $query_string ?>"><?= $i ?></a></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($halaman < $total_halaman): ?>
                                <li><a href="?page=<?= $halaman + 1 ?><?= $query_string ?>">Selanjutnya</a></li>
                            <?php else: ?>
                                <li class="disabled"><span>Selanjutnya</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>