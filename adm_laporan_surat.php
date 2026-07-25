<?php
session_start();
include "koneksi.php";

$jenis = $_GET['jenis'] ?? '';
$prodi = $_GET['prodi'] ?? '';
$periode = $_GET['periode'] ?? '';

$where = "WHERE sp.status_akhir = 'Selesai'";

if ($jenis != '') {
    $jenisAman = mysqli_real_escape_string($koneksi, $jenis);
    $where .= " AND sp.id_jenis = '$jenisAman'";
}

if ($prodi != '') {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.id_prodi = '$prodiAman'";
}

if ($periode != '') {
    $tahun = date('Y', strtotime($periode));
    $bulan = date('m', strtotime($periode));

    $where .= "
        AND YEAR(sp.tanggal_pengajuan) = '$tahun'
        AND MONTH(sp.tanggal_pengajuan) = '$bulan'
    ";
}

$params = [];
if ($jenis != '') $params['jenis'] = $jenis;
if ($prodi != '') $params['prodi'] = $prodi;
if ($periode != '') $params['periode'] = $periode;
$query_string = !empty($params) ? '&' . http_build_query($params) : '';

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$query_count = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
");
$row_count = mysqli_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$query = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.file_surat_final,
        m.npm,
        m.nama_mhs,
        p.nama_prodi,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
");

$jenisSurat = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    ORDER BY nama_surat ASC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Surat Keluar</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>

    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Laporan Surat Keluar</h1>
                <p>Filter laporan berdasarkan jenis surat, prodi, dan periode bulan.</p>
            </div>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section">
                    <select name="jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php while ($j = mysqli_fetch_assoc($jenisSurat)) { ?>
                            <option value="<?= $j['id_jenis']; ?>" <?= $jenis == $j['id_jenis'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($j['nama_surat']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="prodi">
                        <option value="">Semua Prodi</option>
                        <?php
                        $queryProdi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
                        while ($prd = mysqli_fetch_assoc($queryProdi)) {
                            $selected = ($prodi == $prd['id_prodi']) ? 'selected' : '';
                            echo "<option value='" . $prd['id_prodi'] . "' $selected>" . htmlspecialchars($prd['nama_prodi']) . "</option>";
                        }
                        ?>
                    </select>

                    <input type="month" name="periode" value="<?= htmlspecialchars($periode); ?>">

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>

                    <a href="adm_laporan_surat.php" class="btn-reset-filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nomor Surat</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Prodi</th>
                            <th>Jenis Surat</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><?= htmlspecialchars($row['nomor_surat']); ?></td>
                                    <td><?= htmlspecialchars($row['npm']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td>
                                        <?php if (!empty($row['file_surat_final'])) { ?>
                                            <a href="uploads/surat_final/<?= htmlspecialchars($row['file_surat_final']); ?>" target="_blank" class="btn btn-detail">
                                                Lihat
                                            </a>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)"
                                            onclick="bukaDetail('adm_detail_surat_popup.php?id=<?= $row['id_surat']; ?>')"
                                            class="btn btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="9" class="text-center" style="text-align:center;">
                                    Data surat keluar tidak ditemukan.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

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