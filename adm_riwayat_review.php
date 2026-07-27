<?php
session_start();
include "koneksi.php";

if (!isset($koneksi)) {
    include "koneksi.php";
}

$role = $_SESSION['role_admin'] ?? '';
$filter_admin = "";

if ($role == 'admin1') {
    $filter_admin = "AND sp.tujuan_admin = 'admin1'";
} elseif ($role == 'admin2') {
    $filter_admin = "AND sp.tujuan_admin = 'admin2'";
}

$keyword         = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$prodi_filter    = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';
$id_jenis_filter = isset($_GET['id_jenis']) ? trim($_GET['id_jenis']) : '';

$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$batas   = 10;
$mulai   = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$where_clause = "WHERE sp.status_akhir != 'Menunggu Admin' AND sp.id_ormawa IS NULL";

$where_clause .= " AND (
    sp.status_akhir LIKE '%Wadek%' 
    OR sp.status_akhir LIKE '%Dekan%' 
    OR sp.status_akhir LIKE '%Kasubbag%' 
    OR sp.status_akhir = 'Menunggu Penomoran'
    OR sp.status_akhir = 'Menunggu Surat Balasan'
)";

if (!empty($keyword)) {
    $keyword_esc = mysqli_real_escape_string($koneksi, $keyword);
    $where_clause .= " AND (m.npm LIKE '%$keyword_esc%' OR m.nama_mhs LIKE '%$keyword_esc%')";
}

if (!empty($prodi_filter)) {
    $prodi_esc = mysqli_real_escape_string($koneksi, $prodi_filter);
    $where_clause .= " AND m.id_prodi = '$prodi_esc'";
}

if (!empty($id_jenis_filter)) {
    $jenis_esc = mysqli_real_escape_string($koneksi, $id_jenis_filter);
    $where_clause .= " AND sp.id_jenis = '$jenis_esc'";
}

$sql_hitung = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    $where_clause
");
$row_hitung = mysqli_fetch_assoc($sql_hitung);
$total_data = $row_hitung['total'];
$total_halaman = ceil($total_data / $batas);

$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat, sp.file_surat_final, sp.nomor_surat,
        m.npm, m.nama_mhs, p.nama_prodi,
        js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    $where_clause
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $mulai, $batas
");

$data_get = $_GET;
unset($data_get['page']);
$query_string = '&' . http_build_query($data_get);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Review</title>

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
        <?php unset($_SESSION['pesan']);
        unset($_SESSION['status']); ?>
    <?php endif; ?>

    <div class="adm-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">
            <div class="page-title">
                <h1>Proses & Penomoran Surat Akademik</h1>
            </div>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section">
                    <input type="text" name="keyword" placeholder="Cari NPM atau Nama..." value="<?= htmlspecialchars($keyword ?? '') ?>" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">

                    <select name="prodi">
                        <option value="">Semua Prodi</option>
                        <?php
                        $queryProdi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
                        while ($prd = mysqli_fetch_assoc($queryProdi)) {
                            $selected = ($prodi_filter == $prd['id_prodi']) ? 'selected' : '';
                            echo "<option value='" . $prd['id_prodi'] . "' $selected>" . htmlspecialchars($prd['nama_prodi']) . "</option>";
                        }
                        ?>
                    </select>

                    <select name="id_jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php
                        $jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat NOT LIKE '%Dana%' AND nama_surat NOT LIKE '%Ruangan%' ORDER BY nama_surat ASC");
                        while ($js = mysqli_fetch_assoc($jenisSurat)) {
                            $selected = ($id_jenis_filter == $js['id_jenis']) ? 'selected' : '';
                            echo "<option value='" . $js['id_jenis'] . "' $selected>" . htmlspecialchars($js['nama_surat']) . "</option>";
                        }
                        ?>
                    </select>

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>

                    <a href="adm_riwayat_review.php" class="btn-reset-filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tgl & Waktu Pengajuan</th>
                            <th>NPM</th>
                            <th>Nama Mhs</th>
                            <th>Prodi</th>
                            <th>Jenis Surat</th>
                            <th>Status Akhir</th>
                            <th>Tindakan Penomoran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) {
                            $no = $mulai + 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                                $status = htmlspecialchars($row['status_akhir']);

                                if (strpos($status, 'Ditolak') !== false) {
                                    $badge_class = "badge-danger";
                                } elseif ($status == 'Menunggu Penomoran') {
                                    $badge_class = "badge-info";
                                } else {
                                    $badge_class = "badge-warning";
                                }
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><?= !empty($row['npm']) ? htmlspecialchars($row['npm']) : '-'; ?></td>
                                    <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                    <td><?= !empty($row['nama_prodi']) ? htmlspecialchars($row['nama_prodi']) : '-'; ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td><span class="<?= $badge_class; ?>"><?= $status; ?></span></td>

                                    <td>
                                        <?php if ($status == 'Menunggu Penomoran') { ?>
                                            <button type="button" class="btn-edit btn-beri-nomor" onclick="inputNomorSurat(<?= $row['id_surat']; ?>)">
                                                <i class="fa-solid fa-pen-to-square"></i> Beri Nomor
                                            </button>
                                        <?php } else { ?>
                                            <span class="status-menunggu-pimpinan">
                                                <i class="fa-solid fa-spinner fa-spin"></i> Menunggu Pimpinan
                                            </span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <a href="adm_permohonan_akademik.php?detail=<?= $row['id_surat']; ?>&asal=review" class="btn btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    Tidak ada surat akademik yang sedang dalam proses atau menunggu penomoran.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($total_halaman) && $total_halaman > 1): ?>
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
        </main>
    </div>

    <script>
        function inputNomorSurat(idSurat) {
            const d = new Date();
            const tahunSekarang = d.getFullYear();
            const bulanRomawi = ["", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"][d.getMonth() + 1];

            const formatDefault = `B-/Un.16/FST/PP.009/${bulanRomawi}/${tahunSekarang}`;

            Swal.fire({
                title: 'Penomoran Surat Resmi',
                html: `
            <div class="swal-nomor-label">Sesuaikan nomor surat dari Tata Usaha:</div>
            <input id="swal-input-nomor" class="swal2-input swal-input-custom" value="${formatDefault}">
            <div class="swal-nomor-help">
                *Bulan dan Tahun otomatis berganti mengikuti sistem.<br>
                *Pilih <b>Generate Otomatis</b> jika ingin sistem penuh yang membuatkan.
            </div>
        `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fa-solid fa-save"></i> Simpan Manual',
                denyButtonText: 'Generate Otomatis',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#3b82f6',

                preConfirm: () => {
                    const nomor = document.getElementById('swal-input-nomor').value;
                    if (!nomor) {
                        Swal.showValidationMessage('Nomor surat tidak boleh kosong!');
                    }
                    return nomor;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const nomorManual = result.value;
                    window.location.href = `adm_proses_penomoran.php?id=${idSurat}&nomor=${encodeURIComponent(nomorManual)}&metode=manual`;
                } else if (result.isDenied) {
                    window.location.href = `adm_proses_penomoran.php?id=${idSurat}&metode=auto`;
                }
            });
        }
    </script>
</body>

</html>