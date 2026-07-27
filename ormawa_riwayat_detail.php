<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_ormawa = $_SESSION['id_ormawa'];

$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query_detail = mysqli_query($koneksi, "
    SELECT
        sp.*,
        o.nama_ormawa,      
        js.nama_surat,
        
        -- Detail Peminjaman Ruangan
        dpr.nama_kegiatan,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        dpr.proposal,              

        -- Detail Pengajuan Dana
        dpd.nama_kegiatan AS nama_kegiatan_dana,
        dpd.tema_kegiatan,
        dpd.tempat_kegiatan,
        dpd.tanggal_kegiatan,
        dpd.proposal AS proposal_dana,

        -- GABUNGAN JADWAL (Ruangan & Dana)
        COALESCE(dpr.tanggal_mulai, dpd.tanggal_jadwal) AS jadwal_tanggal,
        COALESCE(dpr.jam_mulai, dpd.jam_mulai) AS jadwal_jam_mulai,
        COALESCE(dpr.jam_selesai, dpd.jam_selesai) AS jadwal_jam_selesai
        
    FROM surat_pengajuan sp
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa 
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat 
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    WHERE sp.id_surat = '$id_surat' AND sp.id_ormawa = '{$_SESSION['id_ormawa']}'
");

$data = mysqli_fetch_assoc($query_detail);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan atau Anda tidak memiliki akses.'); window.location='ormawa_riwayat.php';</script>";
    exit;
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

$namaSurat = strtolower($data['nama_surat']);
$isDana = (strpos($namaSurat, 'dana') !== false);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Riwayat Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="ormawa_beranda.php">Beranda</a>
            <a href="ormawa_beranda.php#services">Pengajuan Surat</a>
            <a href="ormawa_beranda.php#status-info">Status & Informasi</a>
            <a href="ormawa_lacak.php">Lacak Surat</a>
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap) ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="table-card">
        <h3 class="section-title-verif">Detail Permohonan Surat Ormawa</h3>

        <table class="table-detail">
            <tr>
                <th>Nomor Surat</th>
                <td><?= htmlspecialchars($data['nomor_surat']); ?></td>
            </tr>
            <tr>
                <th>Jenis Surat</th>
                <td><?= htmlspecialchars($data['nama_surat']); ?></td>
            </tr>
            <tr>
                <th>Nama Organisasi</th>
                <td><?= htmlspecialchars($data['nama_ormawa']); ?></td>
            </tr>

            <?php if ($isDana) { ?>
                <!-- PENGAJUAN DANA -->
                <tr>
                    <th>Nama Kegiatan</th>
                    <td><?= htmlspecialchars($data['nama_kegiatan_dana'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tema Kegiatan</th>
                    <td><?= htmlspecialchars($data['tema_kegiatan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tempat Kegiatan</th>
                    <td><?= htmlspecialchars($data['tempat_kegiatan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal Kegiatan</th>
                    <td><?php
                        if (!empty($data['tanggal_kegiatan'])) {
                            $timestamp = strtotime($data['tanggal_kegiatan']);
                            echo date('d', $timestamp) . ' ' . $array_bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>

            <?php } else { ?>
                <!-- PEMINJAMAN RUANGAN -->
                <tr>
                    <th>Nama Kegiatan</th>
                    <td><?= htmlspecialchars($data['nama_kegiatan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Ruangan yang Dipinjam</th>
                    <td><?= htmlspecialchars($data['ruangan_yang_diajukan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal Pelaksanaan</th>
                    <td>
                        <?php
                        if (!empty($data['tanggal_mulai'])) {
                            $tgl = strtotime($data['tanggal_mulai']);
                            echo date('d', $tgl) . ' ' . $array_bulan[(int)date('m', $tgl)] . ' ' . date('Y', $tgl);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>

            <?php
            $status_lower = strtolower($data['status_akhir']);
            $status_keputusan_lower = strtolower($data['status_keputusan']);

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
                    <?= htmlspecialchars($data['status_akhir']); ?>
                </td>
            </tr>

            <?php
            if ($status_selesai) {
            ?>
                <tr>
                    <th>Jadwal Bertemu Pimpinan</th>
                    <td class="jadwal-pimpinan-cell">
                        <?php
                        if (!empty($data['jadwal_tanggal']) && !empty($data['jadwal_jam_mulai'])) {
                            $tgl_jadwal = strtotime($data['jadwal_tanggal']);
                            $tanggal = date('d', $tgl_jadwal) . ' ' . $array_bulan[(int)date('m', $tgl_jadwal)] . ' ' . date('Y', $tgl_jadwal);

                            $waktu = date('H:i', strtotime($data['jadwal_jam_mulai'])) . ' - ' . date('H:i', strtotime($data['jadwal_jam_selesai'])) . ' WIB';

                            echo '<i class="fa-regular fa-calendar-days icon-jadwal"></i>' . $tanggal;
                            echo '<span class="jadwal-separator">||</span>';
                            echo '<i class="fa-regular fa-clock icon-jadwal"></i>' . $waktu;
                        } else {
                            echo '<i class="fa-solid fa-circle-exclamation icon-jadwal"></i>Jadwal belum ditentukan';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>

            <?php
            if ($status_ditolak && !empty($data['catatan'])) {
            ?>
                <tr>
                    <th>Catatan Penolakan</th>
                    <td class="status-tolak">
                        <?= nl2br(htmlspecialchars($data['catatan'])); ?>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <h3 class="section-title mt-4">Dokumen Pendukung</h3>

        <div class="document-box">
            <p><i class="fa-solid fa-file-circle-check"></i> Klik tombol di bawah untuk memeriksa lampiran surat Anda.</p>

            <div class="document-buttons">
                <?php
                if ($isDana) {
                    $fileSystemPreview = "preview_pengajuan_dana.php?id=" . $data['id_surat'] . "&mode=view";
                } else {
                    $fileSystemPreview = "preview_peminjaman_ruangan.php?id=" . $data['id_surat'] . "&mode=view";
                }
                ?>

                <!-- Surat Hasil Sistem / Preview -->
                <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $fileSystemPreview; ?>')">
                    Surat Permohonan
                </a>

                <!-- File Proposal -->
                <?php if ($isDana) { ?>
                    <?php if (!empty($data['proposal_dana'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($data['proposal_dana']); ?>')">
                            Proposal Kegiatan
                        </a>
                    <?php } else { ?>
                        <span class="btn-disabled">Proposal Kegiatan Belum Ada</span>
                    <?php } ?>
                <?php } else { ?>
                    <?php if (!empty($data['proposal'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($data['proposal']); ?>')">
                            Proposal Kegiatan
                        </a>
                    <?php } else { ?>
                        <span class="btn-disabled">Proposal Kegiatan Belum Ada</span>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="action-panel">
            <a href="ormawa_riwayat.php" class="btn-styled btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- MODAL PREVIEW IFRAME -->
    <div id="modalPreview" class="modal-preview-sec">
        <div class="modal-content-preview-sec">
            <span class="close-btn" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" class="iframe-preview"></iframe>
        </div>
    </div>

    <script>
        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });

        function bukaPreview(url) {
            document.getElementById('previewFrame').src = url;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }
    </script>
</body>

</html>