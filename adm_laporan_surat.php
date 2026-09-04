<?php
session_start();
include "koneksi.php";

$jenis = $_GET['jenis'] ?? '';
$prodi = $_GET['prodi'] ?? '';
$periode = $_GET['periode'] ?? '';

$keyword = $_GET['keyword'] ?? '';
$jenis = $_GET['jenis'] ?? '';
$prodi = $_GET['prodi'] ?? '';

$where = "WHERE sp.status_akhir = 'Selesai'";

if ($keyword != '') {
    $keywordAman = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (m.npm LIKE '%$keywordAman%' OR m.nama_mhs LIKE '%$keywordAman%')";
}

if ($jenis != '') {
    $jenisAman = mysqli_real_escape_string($koneksi, $jenis);
    $where .= " AND sp.id_jenis = '$jenisAman'";
}

if ($prodi != '') {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.id_prodi = '$prodiAman'";
}

$params = [];
if ($keyword != '') $params['keyword'] = $keyword;
if ($jenis != '') $params['jenis'] = $jenis;
if ($prodi != '') $params['prodi'] = $prodi;
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
        sp.waktu_selesai, /* Tambahkan kolom waktu selesai */
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
    ORDER BY sp.waktu_selesai DESC
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
                <h1>Laporan Surat Keluar</h1>
                <p>Arsip surat permohonan mahasiswa.</p>
            </div>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section">
                    <input type="text" name="keyword" placeholder="Cari NPM atau Nama..." value="<?= htmlspecialchars($keyword ?? '') ?>">

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

                    <select name="jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php while ($j = mysqli_fetch_assoc($jenisSurat)) { ?>
                            <option value="<?= $j['id_jenis']; ?>" <?= $jenis == $j['id_jenis'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($j['nama_surat']); ?>
                            </option>
                        <?php } ?>
                    </select>

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
                            <th>Tanggal & Waktu</th>
                            <th>Nomor Surat</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Jenis Surat</th>
                            <th>File Surat Final</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <?php
                                        if (!empty($row['waktu_selesai']) && $row['waktu_selesai'] !== '0000-00-00 00:00:00') {
                                            echo date('d-m-Y H:i', strtotime($row['waktu_selesai'])) . ' WIB';
                                        } else {
                                            echo '<span style="color: #94a3b8;">Belum Selesai</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['nomor_surat']); ?></td>
                                    <td><?= htmlspecialchars($row['npm']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($row['file_surat_final'])) {
                                            $namaSurat = strtolower($row['nama_surat']);

                                            // Kondisi untuk Surat Magang
                                            if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                                $linkUnduh = "generate_surat_magang_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=laporan";
                                            }
                                            // Kondisi untuk SK Aktif Kuliah Kembali
                                            elseif (strpos($namaSurat, 'aktif') !== false) {
                                                $linkUnduh = "generate_sk_aktif_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=laporan";
                                            }
                                            // Kondisi untuk SK Lulus
                                            elseif (strpos($namaSurat, 'lulus') !== false) {
                                                $linkUnduh = "generate_sk_lulus_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=laporan";
                                            }
                                            // Kondisi untuk SKMK
                                            elseif (strpos($namaSurat, 'masih kuliah') !== false) {
                                                $linkUnduh = "generate_skmk_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=laporan";
                                            }
                                            // Kondisi Default untuk Surat Riset / Lainnya
                                            else {
                                                $linkUnduh = "generate_surat_riset_resmi.php?id=" . $row['id_surat'] . "&view=true&asal=laporan";
                                            }
                                        ?>
                                            <a href="<?= $linkUnduh; ?>" target="_blank" class="btn btn-detail">
                                                <i class="fa-solid fa-file-lines"></i>
                                            </a>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="adm_permohonan_akademik.php?detail=<?= $row['id_surat']; ?>&asal=laporan" class="btn btn-detail">
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

    <?php include "adm_footer.php"; ?>
</body>

</html>