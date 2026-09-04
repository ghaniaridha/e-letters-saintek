<?php
session_start();
include "koneksi.php";

/* ===========================
   PROSES SIMPAN JADWAL ORMAWA
=========================== */
if (isset($_POST['proses_jadwal'])) {
    $id_surat   = (int)$_POST['id_surat_jadwal'];
    $tanggal    = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $jam_mulai  = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $catatan    = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT js.nama_surat FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'
    "));

    if (stripos($cekJenis['nama_surat'], 'dana') !== false) {
        mysqli_query($koneksi, "
            UPDATE detail_pengajuan_dana
            SET tanggal_jadwal='$tanggal', jam_mulai='$jam_mulai', jam_selesai='$jam_selesai', catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");
    } else {
        mysqli_query($koneksi, "
            UPDATE detail_peminjaman_ruangan
            SET tanggal_mulai='$tanggal', tanggal_selesai='$tanggal', jam_mulai='$jam_mulai', jam_selesai='$jam_selesai', catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");
    }

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET status_akhir='Selesai', 
            status_keputusan='Disetujui', 
            posisi_sekarang='Selesai', 
            jadwal_pertemuan='$tanggal $jam_mulai:00',
            waktu_selesai=NOW() 
        WHERE id_surat='$id_surat'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Jadwal berhasil ditetapkan dan permohonan diselesaikan.';
    header("Location: adm_laporan_ormawa.php");
    exit;
}

$keyword         = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$id_jenis_filter = isset($_GET['id_jenis']) ? trim($_GET['id_jenis']) : '';

$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$batas   = 10;
$mulai   = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$where_clause = "WHERE sp.id_ormawa IS NOT NULL 
                 AND (
                     sp.status_akhir LIKE '%Disposisi%' 
                     OR sp.status_akhir LIKE '%Audiensi%' 
                     OR sp.status_akhir LIKE '%Penjadwalan%' 
                 )";

if (!empty($keyword)) {
    $keyword_esc = mysqli_real_escape_string($koneksi, $keyword);
    $where_clause .= " AND (o.nama_ormawa LIKE '%$keyword_esc%' OR js.nama_surat LIKE '%$keyword_esc%')";
}

if (!empty($id_jenis_filter)) {
    $jenis_esc = mysqli_real_escape_string($koneksi, $id_jenis_filter);
    $where_clause .= " AND sp.id_jenis = '$jenis_esc'";
}

$sql_hitung = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where_clause
");
$row_hitung = mysqli_fetch_assoc($sql_hitung);
$total_data = $row_hitung['total'];
$total_halaman = ceil($total_data / $batas);

$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat, sp.nomor_surat,
        o.nama_ormawa,
        js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir, sp.posisi_sekarang
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
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
    <title>Riwayat Permohonan Ormawa</title>

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
                <h1>Proses & Atur Jadwal Ormawa</h1>
            </div>

            <div class="table-card-table">
                <form method="GET" action="" class="filter-section">
                    <input type="text" name="keyword" placeholder="Cari Nama Organisasi..." value="<?= htmlspecialchars($keyword ?? '') ?>">

                    <select name="id_jenis">
                        <option value="">Semua Jenis Surat</option>
                        <?php
                        $jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat LIKE '%Dana%' OR nama_surat LIKE '%Ruangan%' ORDER BY nama_surat ASC");
                        while ($js = mysqli_fetch_assoc($jenisSurat)) {
                            $selected = ($id_jenis_filter == $js['id_jenis']) ? 'selected' : '';
                            echo "<option value='" . $js['id_jenis'] . "' $selected>" . htmlspecialchars($js['nama_surat']) . "</option>";
                        }
                        ?>
                    </select>

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>

                    <a href="adm_riwayat_ormawa.php" class="btn-reset-filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tgl Pengajuan</th>
                            <th>Nama Organisasi</th>
                            <th>Jenis Permohonan</th>
                            <th>Status Saat Ini</th>
                            <th>Tindakan Penjadwalan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) {
                            $no = $mulai + 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                                $status = htmlspecialchars($row['status_akhir']);
                                $status_lower = strtolower($status);

                                if (strpos($status_lower, 'ditolak') !== false || strpos($status_lower, 'perbaikan') !== false) {
                                    $badge_class = "badge-danger";
                                } elseif (strpos($status_lower, 'audiensi') !== false || strpos($status_lower, 'penjadwalan') !== false) {
                                    $badge_class = "badge-info";
                                } elseif (strpos($status_lower, 'selesai') !== false) {
                                    $badge_class = "badge-success";
                                } else {
                                    $badge_class = "badge-warning";
                                }
                        ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><b><?= htmlspecialchars($row['nama_ormawa']); ?></b></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td><span class="<?= $badge_class; ?>"><?= $status; ?></span></td>

                                    <td>
                                        <?php
                                        if (strpos($status_lower, 'audiensi') !== false || strpos($status_lower, 'penjadwalan') !== false) {
                                        ?>
                                            <button type="button" class="btn-edit btn-beri-nomor" onclick="bukaModalJadwal(<?= $row['id_surat']; ?>)">
                                                <i class="fa-solid fa-calendar-check"></i> Atur Jadwal
                                            </button>
                                        <?php
                                        } elseif (strpos($status_lower, 'disposisi') !== false || strpos($status_lower, 'pimpinan') !== false) {
                                        ?>
                                            <span class="status-menunggu-pimpinan">
                                                <i class="fa-solid fa-spinner fa-spin"></i> Menunggu Pimpinan
                                            </span>
                                        <?php } else { ?>
                                            <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <a href="adm_permohonan_ormawa.php?detail=<?= $row['id_surat']; ?>&asal=riwayat" class="btn btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    Tidak ada riwayat permohonan ormawa yang ditemukan.
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
        function bukaModalJadwal(idSurat) {
            Swal.fire({
                title: 'Tetapkan Jadwal Audiensi',
                html: `
            <div class="swal-form-container">
                <form id="formJadwalPimpinan" action="adm_riwayat_ormawa.php" method="POST">
                    <input type="hidden" name="id_surat_jadwal" value="${idSurat}">
                    <input type="hidden" name="proses_jadwal" value="1">
                    
                    <div class="swal-form-group">
                        <label class="swal-form-label">
                            Tanggal Pertemuan
                        </label>
                        <input type="date" name="tanggal" class="swal2-input swal-custom-input" required>
                    </div>
                    
                    <div class="swal-form-row">
                        <div class="swal-form-col">
                            <label class="swal-form-label">
                                Jam Mulai
                            </label>
                            <input type="time" name="jam_mulai" class="swal2-input swal-custom-input" required>
                        </div>
                        <div class="swal-form-col">
                            <label class="swal-form-label">
                                Jam Selesai
                            </label>
                            <input type="time" name="jam_selesai" class="swal2-input swal-custom-input" required>
                        </div>
                    </div>

                    <div class="swal-form-group-last">
                        <label class="swal-form-label">
                            Tempat & Catatan
                        </label>
                        <textarea name="catatan" class="swal2-textarea swal-custom-textarea" placeholder="Misal: Rapat dilakukan di Ruang Wadek 2, atau sertakan Link Zoom..."></textarea>
                    </div>
                </form>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Jadwal',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                width: '500px',
                preConfirm: () => {
                    const form = document.getElementById('formJadwalPimpinan');
                    if (!form.tanggal.value || !form.jam_mulai.value || !form.jam_selesai.value) {
                        Swal.showValidationMessage('Mohon lengkapi Tanggal, Jam Mulai, dan Jam Selesai!');
                        return false;
                    }
                    if (form.jam_mulai.value >= form.jam_selesai.value) {
                        Swal.showValidationMessage('Jam Selesai harus lebih besar dari Jam Mulai!');
                        return false;
                    }

                    form.submit();
                }
            });
        }
    </script>
</body>

</html>