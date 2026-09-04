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
        o.jenis_organisasi,
        js.nama_surat,
        
        -- Detail Peminjaman Ruangan
        dpr.nama_kegiatan,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        dpr.proposal,              

        -- Detail Pengajuan Dana
        dpd.nama_kegiatan AS nama_kegiatan_dana,
        dpd.tempat_kegiatan,
        dpd.tanggal_kegiatan,
        dpd.proposal AS proposal_dana,

        -- GABUNGAN JADWAL (Ruangan & Dana)
        COALESCE(dpr.tanggal_mulai, dpd.tanggal_jadwal) AS jadwal_tanggal,
        COALESCE(dpr.jam_mulai, dpd.jam_mulai) AS jadwal_jam_mulai,
        COALESCE(dpr.jam_selesai, dpd.jam_selesai) AS jadwal_jam_selesai,
        
        COALESCE(dpr.catatan, dpd.catatan) AS catatan_admin_jadwal
        
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
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
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
            <a href="ormawa_riwayat.php">Riwayat Permohonan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <a href="mhs_profile.php" class="user-info-link-mhs">
                        <div class="user-info-mhs">
                            <span class="user-name-mhs"><?= htmlspecialchars($namaLengkap) ?></span>
                            <span class="user-role-mhs"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                        </div>
                    </a>
                    <div class="divider"></div>
                    <a href="logout.php" class="logout-btn" onclick="confirmLogout(event, this.href)">
                        <span>Keluar</span>
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
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
                    <th>Tempat Kegiatan</th>
                    <td><?= htmlspecialchars($data['tempat_kegiatan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th> Hari & Tanggal Kegiatan</th>
                    <td><?php
                        if (!empty($data['tanggal_kegiatan'])) {
                            $timestamp = strtotime($data['tanggal_kegiatan']);
                            echo $hari_array[date('w', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $array_bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
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
                    <th> Hari & Tanggal Pelaksanaan</th>
                    <td>
                        <?php
                        if (!empty($data['tanggal_mulai'])) {
                            $tgl = strtotime($data['tanggal_mulai']);
                            echo $hari_array[date('w', $tgl)] . ', ' . date('d', $tgl) . ' ' . $array_bulan[(int)date('m', $tgl)] . ' ' . date('Y', $tgl);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>

            <?php
            $status_asli = $data['status_akhir'];
            $jenis_org = $data['jenis_organisasi'] ?? 'Ormawa';

            $tampil_status = $status_asli;

            if ($jenis_org == 'Ormawa') {
                $tampil_status = str_ireplace('Pembina', 'Kaprodi', $status_asli);
            }

            $status_lower = strtolower($status_asli);
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
                    <?= htmlspecialchars($tampil_status); ?>
                </td>
            </tr>

            <?php
            if ($status_selesai) {
            ?>
                <tr>
                    <th>Jadwal Audiensi</th>
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
            $catatan_admin = $data['catatan_admin_jadwal'] ?? '';

            if (!empty($catatan_admin)):
            ?>
                <tr>
                    <th>Keterangan Audiensi</th>
                    <td class="keterangan-audiensi-cell">
                        <?= nl2br(htmlspecialchars($catatan_admin)); ?>
                    </td>
                </tr>
            <?php endif; ?>

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

            <div class="document-buttons-custom">
                <?php
                if ($isDana) {
                    $fileSystemPreview = "preview_pengajuan_dana.php?id=" . $data['id_surat'] . "&mode=view";
                    $proposalFile = $data['proposal_dana'] ?? '';
                } else {
                    $fileSystemPreview = "preview_peminjaman_ruangan.php?id=" . $data['id_surat'] . "&mode=view";
                    $proposalFile = $data['proposal'] ?? '';
                }
                ?>

                <!-- Surat Hasil Sistem -->
                <div class="document-item-row document-item-draft">
                    <a href="#" class="document-link-item" onclick="bukaPreview('<?= $fileSystemPreview; ?>')">
                        <i class="fa-solid fa-file-lines document-icon-blue"></i> Surat Permohonan
                    </a>
                    <span class="document-system-note">Dihasilkan oleh sistem</span>
                </div>

                <!-- File Proposal -->
                <?php if (!empty($proposalFile)) {

                    $posisi = $data['posisi_sekarang'];
                    $statusAkhir = strtolower($data['status_akhir']);

                    if (strpos($statusAkhir, 'ditolak') !== false || strpos($statusAkhir, 'perbaikan') !== false) {
                        $statusVal = 'Tidak Valid';
                    } elseif ($posisi == 'Pimpinan' || strtolower($posisi) == 'selesai' || strpos($statusAkhir, 'disposisi') !== false) {
                        $statusVal = 'Valid';
                    } else {
                        $statusVal = 'Menunggu';
                    }
                ?>
                    <div class="document-item-row">
                        <a href="#" class="document-link-item" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($proposalFile); ?>')">
                            <i class="fa-solid fa-paperclip document-icon-amber"></i> Proposal Kegiatan
                        </a>

                        <!-- Badge Status Proposal -->
                        <?php if ($statusVal == 'Valid') { ?>
                            <span class="badge-status-doc badge-valid">
                                <i class="fa-solid fa-circle-check"></i> Sesuai
                            </span>
                        <?php } elseif ($statusVal == 'Tidak Valid') { ?>
                            <span class="badge-status-doc badge-invalid">
                                <i class="fa-solid fa-circle-xmark"></i> Perlu Perbaikan
                            </span>
                        <?php } else { ?>
                            <span class="badge-status-doc badge-waiting">
                                <i class="fa-solid fa-clock"></i> Menunggu Pengecekan Fakultas
                            </span>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <p class='empty-document-text'>
                        <i class="fa-solid fa-triangle-exclamation"></i> Proposal Kegiatan Belum Ada.
                    </p>
                <?php } ?>
            </div>
        </div>

        <div class="action-panel">
            <a href="ormawa_riwayat.php" class="btn-styled btn-back">
                Kembali
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
        // fungsi dropdown menu pengguna
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const dropdown = document.getElementById('user-dropdown');

            userBtn.addEventListener('click', function(event) {
                dropdown.classList.toggle('show');
                event.stopPropagation();
            });

            window.addEventListener('click', function(event) {
                if (!event.target.matches('#user-btn') && !event.target.closest('#user-btn')) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            });
        });

        // Fungsi untuk menampilkan konfirmasi sebelum logout
        function confirmLogout(event, url) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: "Anda harus masuk kembali untuk mengakses halaman ini.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

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