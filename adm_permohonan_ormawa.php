<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$keyword         = $_GET['keyword'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

/* ===========================
   PROSES JADWAL & TOLAK
=========================== */
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    if ($aksi == "jadwal") {
        $tanggal = $_POST['tanggal_kegiatan'];
        $mulai   = $_POST['jam_mulai'];
        $selesai = $_POST['jam_selesai'];
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);

        $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT js.nama_surat FROM surat_pengajuan sp
            JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'
        "));

        if (stripos($cekJenis['nama_surat'], 'dana') !== false) {
            mysqli_query($koneksi, "
                UPDATE detail_pengajuan_dana
                SET tanggal_jadwal='$tanggal', jam_mulai='$mulai', jam_selesai='$selesai', catatan='$catatan'
                WHERE id_surat='$id_surat'
            ");
        } else {
            mysqli_query($koneksi, "
                UPDATE detail_peminjaman_ruangan
                SET tanggal_mulai='$tanggal', tanggal_selesai='$tanggal', jam_mulai='$mulai', jam_selesai='$selesai', catatan='$catatan'
                WHERE id_surat='$id_surat'
            ");
        }

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Selesai', status_keputusan='Disetujui', posisi_sekarang='Selesai'
            WHERE id_surat='$id_surat'
        ");

        header("Location: adm_permohonan_ormawa.php");
        exit;
    }

    if ($aksi == "tolak_ormawa") {
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);
        $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT js.nama_surat FROM surat_pengajuan sp
            JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'
        "));

        if (stripos($cekJenis['nama_surat'], 'dana') !== false) {
            mysqli_query($koneksi, "UPDATE detail_pengajuan_dana SET catatan='$catatan' WHERE id_surat='$id_surat'");
        } else {
            mysqli_query($koneksi, "UPDATE detail_peminjaman_ruangan SET catatan='$catatan' WHERE id_surat='$id_surat'");
        }

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Ditolak Admin', status_keputusan='Ditolak', posisi_sekarang='Selesai'
            WHERE id_surat='$id_surat'
        ");
        header("Location: adm_permohonan_ormawa.php");
        exit;
    }
}

/* ===========================
   PROSES HAPUS DATA
=========================== */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = $id");
    echo "<script>alert('Permohonan berhasil dihapus'); window.location='adm_permohonan_ormawa.php';</script>";
    exit;
}

/* ===========================
   PENGATURAN QUERY, FILTER & SEARCH
=========================== */
$where = "WHERE sp.status_akhir = 'Menunggu Admin' AND sp.tujuan_admin = 'admin1' AND sp.id_ormawa IS NOT NULL";

if ($keyword != '') {
    $keywordAman = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (o.nama_ormawa LIKE '%$keywordAman%' OR js.nama_surat LIKE '%$keywordAman%')";
}

if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

$params = [];
if ($keyword != '') $params['keyword'] = $keyword;
if ($id_jenis_filter != '') $params['id_jenis'] = $id_jenis_filter;
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
    SELECT sp.id_surat, o.nama_ormawa, js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where 
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
");

$jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat LIKE '%Dana%' OR nama_surat LIKE '%Ruangan%' ORDER BY nama_surat ASC");

$detail = null;
if ($detail_id != "") {
    $detail_id = (int) $detail_id;
    $query_detail = mysqli_query($koneksi, "
        SELECT 
            sp.*, o.nama_ormawa, js.nama_surat,
            
            -- Data Peminjaman Ruangan
            dpr.nama_kegiatan, dpr.ruangan_yang_diajukan, dpr.tanggal_mulai, dpr.proposal,
            dpr.jam_mulai AS jam_mulai_ruangan, dpr.jam_selesai AS jam_selesai_ruangan, dpr.catatan AS catatan_ruangan,
            
            -- Data Pengajuan Dana
            dpd.nama_kegiatan AS nama_kegiatan_dana, dpd.tema_kegiatan, dpd.tempat_kegiatan, dpd.tanggal_kegiatan AS tanggal_kegiatan_dana, dpd.proposal AS proposal_dana,
            dpd.tanggal_jadwal AS tanggal_jadwal_dana, dpd.jam_mulai AS jam_mulai_dana, dpd.jam_selesai AS jam_selesai_dana, dpd.catatan AS catatan_dana
            
        FROM surat_pengajuan sp
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
        LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
        WHERE sp.id_surat = '$detail_id'
    ");
    $detail = mysqli_fetch_assoc($query_detail);
}

$array_bulan = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];

$tglDana = !empty($detail['tanggal_kegiatan_dana'])
    ? date('d', strtotime($detail['tanggal_kegiatan_dana'])) . ' ' . $array_bulan[(int)date('m', strtotime($detail['tanggal_kegiatan_dana']))] . ' ' . date('Y', strtotime($detail['tanggal_kegiatan_dana']))
    : '-';

$tglMulai = !empty($detail['tanggal_mulai'])
    ? date('d', strtotime($detail['tanggal_mulai'])) . ' ' . $array_bulan[(int)date('m', strtotime($detail['tanggal_mulai']))] . ' ' . date('Y', strtotime($detail['tanggal_mulai']))
    : '-';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">

            <?php if ($detail) {
                $isDana = (stripos($detail['nama_surat'], 'dana') !== false);
            ?>
                <!-- TAMPILAN DETAIL / REVIEW -->
                <div class="table-card-table">
                    <h3 class="section-title-verif">Detail Permohonan Surat Ormawa</h3>

                    <table class="table-detail">
                        <tr>
                            <th>Nama Organisasi</th>
                            <td><?= htmlspecialchars($detail['nama_ormawa']); ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Surat</th>
                            <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                        </tr>

                        <?php if ($isDana) { ?>
                            <tr>
                                <th>Nama Kegiatan</th>
                                <td><?= htmlspecialchars($detail['nama_kegiatan_dana'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tema Kegiatan</th>
                                <td><?= htmlspecialchars($detail['tema_kegiatan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tempat Kegiatan</th>
                                <td><?= htmlspecialchars($detail['tempat_kegiatan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kegiatan</th>
                                <td><?= $tglDana; ?></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <th>Nama Kegiatan</th>
                                <td><?= htmlspecialchars($detail['nama_kegiatan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Ruangan Diajukan</th>
                                <td><?= htmlspecialchars($detail['ruangan_yang_diajukan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kegiatan</th>
                                <td><?= $tglMulai; ?></td>
                            </tr>
                        <?php } ?>

                        <?php
                        $tgl_jadwal_db  = $isDana ? ($detail['tanggal_jadwal_dana'] ?? '') : ($detail['tanggal_mulai'] ?? '');
                        $jam_mulai_db   = $isDana ? ($detail['jam_mulai_dana'] ?? '')      : ($detail['jam_mulai_ruangan'] ?? '');
                        $jam_selesai_db = $isDana ? ($detail['jam_selesai_dana'] ?? '')    : ($detail['jam_selesai_ruangan'] ?? '');
                        $catatan_db     = $isDana ? ($detail['catatan_dana'] ?? '')        : ($detail['catatan_ruangan'] ?? '');

                        $status_lower = strtolower($detail['status_akhir']);
                        $status_keputusan_lower = strtolower($detail['status_keputusan'] ?? '');

                        $status_ditolak = (strpos($status_lower, 'ditolak') !== false);
                        $status_menunggu = (strpos($status_lower, 'menunggu') !== false);
                        $status_selesai = ($status_lower == 'selesai' || $status_keputusan_lower == 'disetujui');

                        if ($status_ditolak) {
                            $warna_status = 'color: #dc2626; font-weight: 700;';
                        } elseif ($status_menunggu) {
                            $warna_status = 'color: #d97706; font-weight: 700;';
                        } else {
                            $warna_status = 'color: #10b981; font-weight: 700;';
                        }
                        ?>

                        <tr>
                            <th>Status Akhir Permohonan</th>
                            <td style="<?= $warna_status; ?>">
                                <?= htmlspecialchars($detail['status_akhir']); ?>
                            </td>
                        </tr>

                        <?php if ($status_selesai) { ?>
                            <tr>
                                <th>Jadwal Bertemu Pimpinan</th>
                                <td class="jadwal-pimpinan-cell">
                                    <?php
                                    if (!empty($tgl_jadwal_db) && !empty($jam_mulai_db)) {
                                        $tgl_jadwal = strtotime($tgl_jadwal_db);
                                        $tanggal = date('d', $tgl_jadwal) . ' ' . $array_bulan[(int)date('m', $tgl_jadwal)] . ' ' . date('Y', $tgl_jadwal);

                                        $waktu = date('H:i', strtotime($jam_mulai_db)) . ' - ' . date('H:i', strtotime($jam_selesai_db)) . ' WIB';

                                        echo '<i class="fa-regular fa-calendar-days icon-jadwal"></i> ' . $tanggal;
                                        echo '<span class="jadwal-separator" style="margin: 0 10px;">||</span>';
                                        echo '<i class="fa-regular fa-clock icon-jadwal"></i> ' . $waktu;
                                    } else {
                                        echo '<i class="fa-solid fa-circle-exclamation icon-jadwal"></i> Jadwal belum ditentukan';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php if ($status_ditolak && !empty($catatan_db)) { ?>
                            <tr>
                                <th>Catatan Penolakan</th>
                                <td class="status-tolak" style="color: #dc2626;">
                                    <?= nl2br(htmlspecialchars($catatan_db)); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <h3 class="section-title mt-4">Dokumen Pendukung</h3>
                    <div class="document-box">
                        <p><i class="fa-solid fa-file-circle-check icon-spacing"></i> Klik tombol di bawah untuk memeriksa lampiran sebelum menindaklanjuti permohonan.</p>

                        <div class="document-buttons">
                            <?php
                            if ($isDana) {
                                $filePreview = "preview_pengajuan_dana.php?id=" . $detail['id_surat'] . "&mode=view";
                                $proposalFile = $detail['proposal_dana'] ?? '';
                            } else {
                                $filePreview = "preview_peminjaman_ruangan.php?id=" . $detail['id_surat'] . "&mode=view";
                                $proposalFile = $detail['proposal'] ?? '';
                            }
                            ?>
                            <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                                Surat Permohonan
                            </a>

                            <?php if (!empty($proposalFile)) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($proposalFile); ?>')">
                                    Proposal Kegiatan
                                </a>
                            <?php } else { ?>
                                <span class="btn-disabled">Proposal Belum Ada</span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="action-panel">
                        <?php
                        $asal_halaman = $_GET['asal'] ?? '';

                        if ($asal_halaman == 'laporan') {
                            $link_kembali = 'adm_laporan_ormawa.php';
                        } else {
                            $link_kembali = 'adm_permohonan_ormawa.php';
                        }
                        ?>

                        <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">
                            Kembali
                        </a>

                        <?php
                        if ($asal_halaman != 'laporan'):
                        ?>
                            <div class="form-action-group">
                                <button type="button" class="btn-styled btn-reject" onclick="konfirmasiTolak()">
                                    Tolak
                                </button>
                                <button type="button" class="btn-styled btn-approve" onclick="bukaModalJadwal()">
                                    Atur Jadwal
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php } else { ?>
                <!-- TAMPILAN TABEL UTAMA DENGAN FILTER & SEARCH -->
                <div class="page-title">
                    <h1>Kelola Surat Pengajuan Ormawa</h1>
                </div>

                <div class="table-card-table">
                    <form method="GET" action="" class="filter-section">
                        <input type="text" name="keyword" placeholder="Cari Nama Ormawa / Jenis Surat..." value="<?= htmlspecialchars($keyword ?? '') ?>">

                        <select name="id_jenis">
                            <option value="">Semua Jenis Surat</option>
                            <?php
                            mysqli_data_seek($jenisSurat, 0);
                            while ($js = mysqli_fetch_assoc($jenisSurat)) {
                                $selected = ($id_jenis_filter == $js['id_jenis']) ? 'selected' : '';
                                echo "<option value='" . $js['id_jenis'] . "' $selected>" . htmlspecialchars($js['nama_surat']) . "</option>";
                            }
                            ?>
                        </select>

                        <button type="submit" class="btn-filter">
                            <i class="fa-solid fa-search"></i> Cari
                        </button>
                        <a href="adm_permohonan_ormawa.php" class="btn-reset-filter">
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
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                                <?php
                                $no = $offset + 1;
                                while ($row = mysqli_fetch_assoc($query)) {
                                ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?> WIB</td>
                                        <td><?= htmlspecialchars($row['nama_ormawa']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                        <td><span class="badge-warning"><?= htmlspecialchars($row['status_akhir']); ?></span></td>
                                        <td>
                                            <div class="action-group-table">
                                                <a href="adm_permohonan_ormawa.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">Review</a>
                                                <a href="#" class="btn btn-delete" onclick="hapusData(<?= $row['id_surat']; ?>)">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada permohonan yang perlu ditindaklanjuti.</td>
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
            <?php } ?>
        </main>
    </div>

    <!-- MODAL PREVIEW -->
    <div id="modalPreview" class="modal-preview-sec">
        <div class="modal-content-preview-sec">
            <span class="close-preview" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" class="iframe-preview"></iframe>
        </div>
    </div>

    <!-- MODAL ATUR JADWAL -->
    <div id="modalJadwal" class="modal-preview">
        <div class="modal-content-preview">
            <h3>Atur Jadwal Pertemuan Ormawa dan Pimpinan</h3>

            <form method="POST" onsubmit="konfirmasiAturJadwal(event, this); return false;">
                <input type="hidden" name="id_surat" value="<?= $detail['id_surat'] ?? ''; ?>">
                <input type="hidden" name="aksi_admin" value="jadwal">

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal_kegiatan" class="form-control" required>
                </div>

                <div class="modal-row-time">
                    <div class="form-group modal-col-time">
                        <label>Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control" required>
                    </div>
                    <div class="form-group modal-col-time">
                        <label>Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Admin</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan catatan atau keterangan tambahan (opsional)"></textarea>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn-modal-cancel" onclick="konfirmasiBatal('modalJadwal')">
                        Batal
                    </button>
                    <button type="submit" class="btn-modal-submit">
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaPreview(url) {
            document.getElementById('previewFrame').src = url;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        function bukaModalJadwal() {
            document.getElementById('modalJadwal').style.display = 'flex';
        }

        function tutupModalJadwal() {
            document.getElementById('modalJadwal').style.display = 'none';
        }

        // Fungsi Konfirmasi simpan jadwal Permohonan
        function konfirmasiAturJadwal(event, formElement) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Jadwal',
                text: 'Yakin ingin menyimpan jadwal dan menyelesaikan permohonan ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    formElement.submit();
                }
            });
        }

        // Konfirmasi membatalkan pengisian form
        function konfirmasiBatal(modalId) {
            Swal.fire({
                title: 'Batalkan?',
                text: 'Data yang sudah diisi tidak akan disimpan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Lanjutkan Mengisi'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(modalId).style.display = 'none';
                }
            });
        }

        // Fungsi Konfirmasi Tolak Permohonan
        function konfirmasiTolak() {
            Swal.fire({
                title: 'Alasan Penolakan',
                input: 'textarea',
                inputPlaceholder: 'Masukkan alasan spesifik penolakan permohonan...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus mengisi alasan penolakan terlebih dahulu!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('catatanInput').value = result.value;
                    document.getElementById('aksiInput').value = 'tolak';
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }

        // Fungsi Konfirmasi hapus Permohonan
        function hapusData(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: 'Data permohonan ini akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_permohonan_ormawa.php?hapus=' + id;
                }
            });
        }
    </script>
</body>

</html>