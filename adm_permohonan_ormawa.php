<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

$keyword         = $_GET['keyword'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

/* ===========================
   PROSES TERUSKAN & KEMBALIKAN
=========================== */
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    // AKSI: TERUSKAN KE PIMPINAN
    if ($aksi == "teruskan") {
        $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT js.nama_surat FROM surat_pengajuan sp
            JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'
        "));

        if (stripos($cekJenis['nama_surat'], 'dana') !== false) {
            // Surat DANA -> Untuk Wadek 2
            $status_pimpinan = 'Menunggu Disposisi Wadek 2';
        } else {
            // Surat RUANGAN -> Untuk Kasubbag TU
            $status_pimpinan = 'Menunggu Disposisi Kasubbag TU';
        }

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir = '$status_pimpinan', 
                posisi_sekarang = 'Pimpinan',
                tujuan_admin = NULL,
                waktu_verif_admin='$waktu_sekarang'
            WHERE id_surat = '$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil diteruskan ke Pimpinan terkait.';
        header("Location: adm_riwayat_ormawa.php");
        exit;
    }

    // AKSI: KEMBALIKAN PERMOHONAN (REVISI)
    if ($aksi == "kembalikan") {
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');

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
            SET status_akhir = 'Perbaikan', 
                posisi_sekarang = 'Mahasiswa',
                waktu_verif_admin='$waktu_sekarang'
            WHERE id_surat = '$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan'] = 'Permohonan dikembalikan ke mahasiswa untuk perbaikan.';
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
            dpd.nama_kegiatan AS nama_kegiatan_dana, dpd.tempat_kegiatan, dpd.tanggal_kegiatan AS tanggal_kegiatan_dana, dpd.proposal AS proposal_dana,
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

$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
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
    ? $hari_array[date('w', strtotime($detail['tanggal_kegiatan_dana']))] . ', ' . date('d', strtotime($detail['tanggal_kegiatan_dana'])) . ' ' . $array_bulan[(int)date('m', strtotime($detail['tanggal_kegiatan_dana']))] . ' ' . date('Y', strtotime($detail['tanggal_kegiatan_dana']))
    : '-';

$tglMulai = !empty($detail['tanggal_mulai'])
    ? $hari_array[date('w', strtotime($detail['tanggal_mulai']))] . ', ' . date('d', strtotime($detail['tanggal_mulai'])) . ' ' . $array_bulan[(int)date('m', strtotime($detail['tanggal_mulai']))] . ' ' . date('Y', strtotime($detail['tanggal_mulai']))
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
                                <th>Hari & Tanggal Kegiatan</th>
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
                                        echo '<span class="jadwal-separator">||</span>';
                                        echo '<i class="fa-regular fa-clock icon-jadwal"></i> ' . $waktu;
                                    } else {
                                        echo '<i class="fa-solid fa-circle-exclamation icon-jadwal"></i> Jadwal belum ditentukan';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php
                        $catatan_admin = !empty($detail['catatan_dana']) ? $detail['catatan_dana'] : (!empty($detail['catatan_ruangan']) ? $detail['catatan_ruangan'] : '');

                        if (!empty($catatan_admin)):
                        ?>
                            <tr>
                                <th>Keterangan Audiensi</th>
                                <td <td class="keterangan-audiensi-cell">
                                    <?= nl2br(htmlspecialchars($catatan_admin)); ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($status_ditolak && !empty($catatan_db)) { ?>
                            <tr>
                                <th>Catatan Penolakan</th>
                                <td class="status-tolak">
                                    <?= nl2br(htmlspecialchars($catatan_db)); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <?php
                    $isDana = (stripos($detail['nama_surat'], 'dana') !== false);
                    ?>

                    <form method="POST" class="document-box" id="formVerifikasi" action="adm_permohonan_ormawa.php">
                        <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">
                        <input type="hidden" name="aksi_admin" id="aksiInput" value="">
                        <input type="hidden" name="catatan" id="catatanInput" value="">

                        <?php
                        $asal_halaman = $_GET['asal'] ?? '';

                        if ($asal_halaman == 'riwayat') {
                            $link_kembali = 'adm_riwayat_ormawa.php';
                        } elseif ($asal_halaman == 'laporan') {
                            $link_kembali = 'adm_laporan_ormawa.php';
                        } else {
                            $link_kembali = 'adm_permohonan_ormawa.php';
                        }

                        $is_menunggu_admin = ($detail['status_akhir'] == 'Menunggu Admin');
                        ?>

                        <h3 class="section-title mt-4">Dokumen Pendukung</h3>
                        <div class="document-box">
                            <?php if ($is_menunggu_admin): ?>
                                <p><i class="fa-solid fa-file-circle-check icon-spacing"></i> Periksa kelengkapan berkas di bawah ini dan beri centang 'Sesuai' sebelum meneruskan permohonan.</p>
                            <?php else: ?>
                                <p><i class="fa-solid fa-file-circle-check icon-spacing"></i> Status validasi berkas lampiran yang telah diajukan.</p>
                            <?php endif; ?>

                            <div class="document-buttons-custom">
                                <?php
                                if ($isDana) {
                                    $filePreview = "preview_pengajuan_dana.php?id=" . $detail['id_surat'] . "&mode=view";
                                    $proposalFile = $detail['proposal_dana'] ?? '';
                                } else {
                                    $filePreview = "preview_peminjaman_ruangan.php?id=" . $detail['id_surat'] . "&mode=view";
                                    $proposalFile = $detail['proposal'] ?? '';
                                }
                                ?>

                                <!-- Surat Permohonan (Dihasilkan Sistem) -->
                                <div class="document-item-row document-item-draft">
                                    <a href="#" class="document-link-item" onclick="bukaPreview('<?= $filePreview; ?>')">
                                        <i class="fa-solid fa-file-lines document-icon-blue"></i> Surat Permohonan
                                    </a>
                                    <span class="document-system-note">Dihasilkan oleh sistem</span>
                                </div>

                                <!-- Proposal Kegiatan -->
                                <?php if (!empty($proposalFile)) { ?>
                                    <div class="document-item-row">
                                        <a href="#" class="document-link-item" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($proposalFile); ?>')">
                                            <i class="fa-solid fa-paperclip document-icon-amber"></i> Proposal Kegiatan
                                        </a>

                                        <?php if ($is_menunggu_admin): ?>
                                            <label class="checkbox-label-valid">
                                                <input type="checkbox" class="checkbox-input-custom required-checkbox"> Sesuai
                                            </label>
                                        <?php else: ?>
                                            <?php
                                            $statusAkhirL = strtolower($detail['status_akhir']);
                                            if (strpos($statusAkhirL, 'ditolak admin') !== false || strpos($statusAkhirL, 'perbaikan') !== false) {
                                                echo '<span class="badge-status-doc badge-invalid"><i class="fa-solid fa-circle-xmark"></i> Perlu Perbaikan</span>';
                                            } else {
                                                echo '<span class="badge-status-doc badge-valid"><i class="fa-solid fa-circle-check"></i> Sesuai</span>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                <?php } else { ?>
                                    <p class='text-error-doc'>
                                        <i class="fa-solid fa-triangle-exclamation"></i> Proposal Belum Ada.
                                    </p>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="action-panel-sec action-panel-custom-sec">
                            <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">Kembali</a>

                            <?php if ($asal_halaman != 'laporan' && $is_menunggu_admin): ?>
                                <div class="action-buttons-group">
                                    <button type="button" class="btn-styled btn-reject-amber" onclick="konfirmasiRevisi()" title="Kembalikan untuk direvisi">
                                        Kembalikan Permohonan
                                    </button>
                                    <button type="button" class="btn-styled btn-approve" onclick="konfirmasiAksi('teruskan', 'Yakin ingin meneruskan permohonan ini ke pimpinan?', 'question')">
                                        Teruskan ke Pimpinan
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
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

    <script>
        function bukaPreview(url) {
            document.getElementById('previewFrame').src = url;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
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

        function konfirmasiRevisi() {
            Swal.fire({
                title: 'Catatan Revisi Kesalahan',
                input: 'textarea',
                inputPlaceholder: 'Sebutkan bagian data atau berkas yang salah agar diperbaiki oleh Ormawa / UKM...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus menuliskan catatan atau alasan revisi terlebih dahulu!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('catatanInput').value = result.value;
                    document.getElementById('aksiInput').value = 'revisi';
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }

        function konfirmasiAksi(aksi, pesan, icon) {
            Swal.fire({
                title: 'Konfirmasi Tindakan',
                text: pesan,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('aksiInput').value = aksi;
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }
    </script>
</body>

</html>