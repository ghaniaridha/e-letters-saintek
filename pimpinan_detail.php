<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai pimpinan'); window.location='index.php';</script>";
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_surat = $_GET['id'] ?? '';

$query_detail = mysqli_query($koneksi, "
    SELECT 
        sp.*, 
        m.nama_mhs, m.npm, p.nama_prodi, o.nama_ormawa, js.nama_surat,
        
        COALESCE(dpr.tanggal_mulai, dpd.tanggal_kegiatan) AS jadwal_tanggal,
        COALESCE(dpr.jam_mulai, dpd.jam_mulai) AS jadwal_jam_mulai,
        COALESCE(dpr.jam_selesai, dpd.jam_selesai) AS jadwal_jam_selesai,
        COALESCE(dpr.catatan, dpd.catatan) AS catatan_admin_jadwal,

        -- Detail Surat Riset
        dsr.judul_skripsi, dsr.lokasi_penelitian, 
        
        -- Detail Surat Magang
        dsm.lokasi_magang, dsm.tanggal_mulai_magang, dsm.tanggal_selesai_magang,
        
        -- Detail SK Aktif Kuliah
        dak.lama_cuti, dak.ta_mulai_cuti, dak.ta_selesai_cuti, dak.tahun_akademik,

        -- Detail SK Lulus
        dsl.tempat_lahir,
        dsl.tanggal_lahir,
        dsl.tahun_akademik AS tahun_akademik_lulus,
        dsl.tanggal_lulus,
        dsl.ipk,
        dsl.nilai_skripsi,
        dsl.predikat_kelulusan,
        dsl.keperluan,

        -- Detail SK Masih Kuliah (SKMK)
        dsk.tahun_akademik AS tahun_akademik_skmk,
        dsk.keperluan AS keperluan_skmk,
        dsk.nama_ortu,
        dsk.nip_ortu,
        dsk.instansi_ortu,
        dsk.alamat_ortu,

        -- Detail Peminjaman Ruangan (Ormawa)
        dpr.nama_kegiatan,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai AS tanggal_mulai_ruangan,
        dpr.tanggal_selesai AS tanggal_selesai_ruangan,
        dpr.jam_mulai AS jam_mulai_ruangan,
        dpr.jam_selesai AS jam_selesai_ruangan,
        dpr.proposal AS proposal_ruangan,
        
        -- Detail Pengajuan Dana (Ormawa)
        dpd.nama_kegiatan AS nama_kegiatan_dana,
        dpd.tempat_kegiatan,
        dpd.tanggal_kegiatan AS tanggal_kegiatan_dana,
        dpd.proposal AS proposal_dana,

       COALESCE(dsm.surat_ditujukan, dsr.surat_ditujukan) AS surat_ditujukan,
        COALESCE(dsr.semester, dsm.semester, dak.semester, dsl.semester, dsk.semester) AS semester,
        COALESCE(dsl.keperluan, dsk.keperluan) AS keperluan
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    LEFT JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat
    LEFT JOIN detail_skmk dsk ON sp.id_surat = dsk.id_surat
    
    WHERE sp.id_surat = '$id_surat'
");

$data = mysqli_fetch_assoc($query_detail);

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

$namaSurat = strtolower($data['nama_surat']);
$isOrmawa = !empty($data['id_ormawa']);
$isDana = (strpos($namaSurat, 'dana') !== false);

// Rute File Preview Dokumen
if ($isOrmawa) {
    if ($isDana) {
        $filePreview = "preview_pengajuan_dana.php?id=" . $data['id_surat'] . "&mode=view";
    } else {
        $filePreview = "preview_peminjaman_ruangan.php?id=" . $data['id_surat'] . "&mode=view";
    }
} else {
    if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
        $filePreview = "preview_magang.php?id=" . $data['id_surat'];
    } else if (strpos($namaSurat, 'aktif') !== false) {
        $filePreview = "mhs_preview_sk_aktif.php?id=" . $data['id_surat'];
    } else if (strpos($namaSurat, 'lulus') !== false) {
        $filePreview = "preview_sk_lulus_mhs.php?id=" . $data['id_surat'] . "&mode=view";
    } else if (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
        $filePreview = "preview_skmk_mhs.php?id=" . $data['id_surat'] . "&mode=view";
    } else {
        $filePreview = "preview_surat.php?id=" . $data['id_surat'];
    }
}

// Aksi Setuju/Tolak Permohonan oleh Pimpinan
if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $hash_ttd = hash('sha256', $id_surat . $id_dosen . time());
    $status_skrg = trim($data['status_akhir']);

    if ($aksi == 'tolak') {
        $sql = "
            UPDATE surat_pengajuan
            SET
                status_akhir='Ditolak Pimpinan',
                status_pimpinan='Ditolak',
                catatan='$catatan',
                waktu_verif_pimpinan='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ";
        mysqli_query($koneksi, $sql);

        $_SESSION['status'] = 'success';
        $_SESSION['pesan'] = 'Permohonan berhasil ditolak';
        header("Location: pimpinan_riwayat.php");
        exit;
    }

    // PROSES SETUJU
    if ($isOrmawa) {
        // Alur untuk Ormawa: Kasubbag TU atau Wadek 2 menyetujui -> Selesai -> Siap Atur Jadwal
        mysqli_query($koneksi, "
            UPDATE surat_pengajuan 
            SET status_akhir = 'Menunggu Penjadwalan oleh Admin', 
                status_pimpinan = 'Disetujui', 
                ttd_pimpinan = '$hash_ttd',
                waktu_verif_pimpinan='$waktu_sekarang'
            WHERE id_surat = '$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan disetujui. Diteruskan ke Admin untuk pengaturan jadwal.';
        header("Location: pimpinan_riwayat.php");
        exit;
    } else {
        // Alur Akademik
        if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
            $url_preview = "generate_surat_magang_resmi.php?id=$id_surat&asal=pimpinan&view=true";
        } else if (strpos($namaSurat, 'riset') !== false || strpos($namaSurat, 'penelitian') !== false) {
            $url_preview = "generate_surat_riset_resmi.php?id=$id_surat&asal=pimpinan&view=true";
        } else if (strpos($namaSurat, 'aktif') !== false) {
            $url_preview = "generate_sk_aktif_resmi.php?id=$id_surat&asal=pimpinan&view=true";
        } else if (strpos($namaSurat, 'lulus') !== false) {
            $url_preview = "generate_sk_lulus_resmi.php?id=$id_surat&asal=pimpinan&view=true";
        } else if (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
            $url_preview = "generate_skmk_resmi.php?id=$id_surat&asal=pimpinan&view=true"; // Sesuaikan nama file generator resmi SKMK Anda jika berbeda
        } else {
            $url_preview = "pimpinan_riwayat.php";
        }

        if ($status_skrg == 'Menunggu Wadek 1' || $status_skrg == 'Menunggu Dekan') {
            mysqli_query($koneksi, "
                UPDATE surat_pengajuan 
                SET status_akhir = 'Menunggu Penomoran', 
                    status_pimpinan = 'Disetujui', 
                    ttd_pimpinan = '$hash_ttd',
                    waktu_verif_pimpinan='$waktu_sekarang' 
                WHERE id_surat = '$id_surat'
            ");
            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Permohonan disetujui. Menampilkan pratinjau surat.';
            header("Location: " . $url_preview);
            exit;
        } elseif ($status_skrg == 'Menunggu Wadek 2' || $status_skrg == 'Menunggu Kasubag') {
            mysqli_query($koneksi, "
                UPDATE surat_pengajuan 
                SET status_akhir = 'Menunggu Dekan', 
                    ttd_pimpinan = '$hash_ttd',
                    waktu_verif_pimpinan='$waktu_sekarang'
                WHERE id_surat = '$id_surat'
            ");
            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Surat disetujui dan diteruskan ke Dekan.';
            header("Location: " . $url_preview);
            exit;
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['pesan']  = "Status surat tidak valid (saat ini: $status_skrg).";
            header("Location: pimpinan_verif.php");
            exit;
        }
    }
}

function tgl_indo($tanggal)
{
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }

    $array_hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

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

    $timestamp = strtotime($tanggal);
    if (!$timestamp) {
        return $tanggal;
    }

    $hari_inggris = date('l', $timestamp);
    $nama_hari = $array_hari[$hari_inggris];

    $tgl = date('d', $timestamp);
    $bulan = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    return $nama_hari . ', ' . $tgl . ' ' . $array_bulan[$bulan] . ' ' . $tahun;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="pimpinan_beranda.php#home">Beranda</a>
            <a href="pimpinan_verif.php">Disposisi & Verifikasi</a>
            <a href="pimpinan_riwayat.php">Riwayat Verifikasi</a>
            <div class="nav-dropdown">
                <a href="#" class="<?= basename($_SERVER['PHP_SELF']) == 'pimpinan_tracking.php' ? 'active' : ''; ?>">
                    Tracking <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <div class="dropdown-content">
                    <a href="pimpinan_tracking.php?kategori=akademik">Surat Akademik</a>
                    <a href="pimpinan_tracking.php?kategori=ormawa">Surat Organisasi</a>
                </div>
            </div>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial); ?></span>
                </button>

                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap); ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin); ?> - <?= htmlspecialchars($role); ?></span>
                    </div>

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
        <h3 class="section-title-verif">
            <?= $isOrmawa ? 'Detail Permohonan Surat Organisasi' : 'Detail Permohonan Surat Mahasiswa'; ?>
        </h3>
        <table class="table-detail">
            <?php if ($isOrmawa): ?>
                <!-- TAMPILAN KHUSUS ORMAWA -->
                <tr>
                    <th>Nama Organisasi</th>
                    <td><?= htmlspecialchars($data['nama_ormawa']); ?></td>
                </tr>
                <tr>
                    <th>Jenis Permohonan</th>
                    <td><?= htmlspecialchars($data['nama_surat']); ?></td>
                </tr>

                <?php if ($isDana): ?>
                    <tr>
                        <th>Nama Kegiatan</th>
                        <td><?= htmlspecialchars($data['nama_kegiatan_dana'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Tempat Kegiatan</th>
                        <td><?= htmlspecialchars($data['tempat_kegiatan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Kegiatan</th>
                        <td><?= !empty($data['tanggal_kegiatan_dana']) ? tgl_indo($data['tanggal_kegiatan_dana']) : '-'; ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <th>Nama Kegiatan</th>
                        <td><?= htmlspecialchars($data['nama_kegiatan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Ruangan Diajukan</th>
                        <td><?= htmlspecialchars($data['ruangan_yang_diajukan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                    <tr>
                        <th>Tanggal Kegiatan</th>
                        <td>
                            <?= !empty($data['tanggal_mulai_ruangan']) ? tgl_indo($data['tanggal_mulai_ruangan']) : '-'; ?>
                        </td>
                    </tr>
                    </tr>
                    <tr>
                        <th>Waktu (Jam)</th>
                        <td>
                            <?= htmlspecialchars($data['jam_mulai_ruangan'] ?? ''); ?> s/d
                            <?= htmlspecialchars($data['jam_selesai_ruangan'] ?? ''); ?>
                        </td>
                    </tr>
                <?php endif; ?>

            <?php else: ?>
                <!-- TAMPILAN AKADEMIK -->
                <tr>
                    <th>NPM</th>
                    <td><?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <th>Nama Mahasiswa</th>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                </tr>
                <tr>
                    <th>Program Studi</th>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <th>Jenis Surat</th>
                    <td><?= htmlspecialchars($data['nama_surat']); ?></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td><?= htmlspecialchars($data['semester'] ?? '-'); ?></td>
                </tr>

                <?php
                if (strpos($namaSurat, 'riset') !== false) {
                ?>
                    <tr>
                        <th>Judul Skripsi</th>
                        <td><?= htmlspecialchars($data['judul_skripsi'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Lokasi Penelitian</th>
                        <td><?= htmlspecialchars($data['lokasi_penelitian'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Ditujukan Kepada</th>
                        <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                    </tr>

                <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                    <tr>
                        <th>Lokasi Magang</th>
                        <td><?= htmlspecialchars($data['lokasi_magang'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Magang</th>
                        <td>
                            <?= tgl_indo($data['tanggal_mulai_magang'] ?? ''); ?> s/d
                            <?= tgl_indo($data['tanggal_selesai_magang'] ?? ''); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Ditujukan Kepada</th>
                        <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                    </tr>

                <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                    <tr>
                        <th>Lama Cuti</th>
                        <td><?= htmlspecialchars($data['lama_cuti'] ?? '-'); ?> Semester</td>
                    </tr>
                    <tr>
                        <th>Periode Masa Cuti</th>
                        <td>
                            Gasal: <?= htmlspecialchars($data['ta_mulai_cuti'] ?? '-'); ?> <br>
                            Genap: <?= htmlspecialchars($data['ta_selesai_cuti'] ?? '-'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tahun Akademik Aktif</th>
                        <td><?= htmlspecialchars($data['tahun_akademik'] ?? '-'); ?></td>
                    </tr>

                <?php } else if (strpos($namaSurat, 'lulus') !== false) { ?>
                    <tr>
                        <th>Tempat, Tanggal Lahir</th>
                        <td>
                            <?= htmlspecialchars($data['tempat_lahir'] ?? '-'); ?>,
                            <?= (!empty($data['tanggal_lahir']) && function_exists('tgl_indo')) ? tgl_indo($data['tanggal_lahir']) : ($data['tanggal_lahir'] ?? '-'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tahun Akademik Kelulusan</th>
                        <td><?= htmlspecialchars($data['tahun_akademik_lulus'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Lulus (Munaqasah)</th>
                        <td><?= (!empty($data['tanggal_lulus']) && function_exists('tgl_indo')) ? tgl_indo($data['tanggal_lulus']) : ($data['tanggal_lulus'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>IPK Terakhir</th>
                        <td><?= htmlspecialchars($data['ipk'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Nilai Skripsi</th>
                        <td><?= htmlspecialchars($data['nilai_skripsi'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Predikat Kelulusan</th>
                        <td><?= htmlspecialchars($data['predikat_kelulusan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Keperluan</th>
                        <td><?= htmlspecialchars($data['keperluan'] ?? '-'); ?></td>
                    </tr>

                <?php } else if (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) { ?>
                    <tr>
                        <th>Tahun Akademik</th>
                        <td><?= htmlspecialchars($data['tahun_akademik_skmk'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Keperluan</th>
                        <td><?= htmlspecialchars($data['keperluan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Orang Tua / Wali</th>
                        <td><?= htmlspecialchars($data['nama_ortu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>NIP Orang Tua</th>
                        <td><?= htmlspecialchars($data['nip_ortu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Instansi Orang Tua</th>
                        <td><?= htmlspecialchars($data['instansi_ortu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Alamat Orang Tua</th>
                        <td><?= nl2br(htmlspecialchars($data['alamat_ortu'] ?? '-')); ?></td>
                    </tr>
                <?php } ?>
            <?php endif; ?>

            <?php
            $status_asli = $data['status_akhir'];
            $status_lower = strtolower($status_asli);

            $status_pimpinan_lower = isset($data['status_pimpinan']) ? strtolower($data['status_pimpinan']) : '';

            $status_ditolak = (strpos($status_lower, 'ditolak') !== false || $status_pimpinan_lower == 'ditolak');
            $status_selesai = ($status_lower == 'selesai' || $status_pimpinan_lower == 'disetujui');

            if ($status_ditolak) {
                $warna_status = 'color: #ef4444; font-weight: 700;';
            } elseif ($status_selesai) {
                $warna_status = 'color: #10b981; font-weight: 700;';
            } else {
                $warna_status = 'color: #f59e0b; font-weight: 700;';
            }
            ?>
            <tr>
                <th>Status Saat Ini</th>
                <td style="<?= $warna_status; ?>">
                    <?= htmlspecialchars($status_asli); ?>
                </td>
            </tr>

            <?php
            $status_lower = strtolower($data['status_akhir']);
            $status_pimpinan_lower = isset($data['status_pimpinan']) ? strtolower($data['status_pimpinan']) : '';
            $status_selesai = ($status_lower == 'selesai' || $status_pimpinan_lower == 'disetujui');

            if ($isOrmawa && $status_selesai):
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
            ?>
                <tr>
                    <th>Jadwal Audiensi</th>
                    <td class="jadwal-pimpinan-cell">
                        <?php
                        if (!empty($data['jadwal_tanggal']) && !empty($data['jadwal_jam_mulai'])) {
                            $tgl_jadwal = strtotime($data['jadwal_tanggal']);
                            $tanggal = date('d', $tgl_jadwal) . ' ' . $array_bulan[(int)date('m', $tgl_jadwal)] . ' ' . date('Y', $tgl_jadwal);

                            $waktu = date('H:i', strtotime($data['jadwal_jam_mulai'])) . ' - ' . date('H:i', strtotime($data['jadwal_jam_selesai'])) . ' WIB';

                            echo '<i class="fa-regular fa-calendar-days icon-jadwal"></i>' . $tanggal . '';
                            echo '<span class="jadwal-separator">||</span>';
                            echo '<i class="fa-regular fa-clock icon-jadwal"></i>' . $waktu . '';
                        } else {
                            echo '<i class="fa-solid fa-circle-exclamation icon-jadwal"></i> Jadwal belum ditentukan';
                        }
                        ?>
                    </td>
                </tr>

                <?php
                $catatan_admin = $data['catatan_admin_jadwal'] ?? '';

                if (!empty($catatan_admin)):
                ?>
                    <tr>
                        <th>Keterangan Audiensi</th>
                        <td class="jadwal-pimpinan-cell">
                            <?= nl2br(htmlspecialchars($catatan_admin)); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>

        <h3 class="section-title mt-4">Dokumen Pendukung</h3>

        <div class="document-buttons">
            <?php
            if ($isOrmawa) {
                // Logika Surat Organisasi
                if ($isDana) {
                    $filePreview = "preview_pengajuan_dana.php?id=" . $data['id_surat'] . "&mode=view";
                } else {
                    $filePreview = "preview_peminjaman_ruangan.php?id=" . $data['id_surat'] . "&mode=view";
                }
                $teks_tombol = "Surat Permohonan Organisasi";
            } else {
                // Logika Surat Akademik
                $namaSurat = strtolower($data['nama_surat']);
                $filePreview = "preview_surat_riset_mhs.php?id=" . $data['id_surat'] . "&mode=view"; // Default (Riset)

                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                    $filePreview = "preview_surat_magang_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'aktif') !== false) {
                    $filePreview = "preview_sk_aktif_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'lulus') !== false) {
                    $filePreview = "preview_sk_lulus_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
                    $filePreview = "preview_skmk_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                }
                $teks_tombol = "Surat Permohonan Mahasiswa";
            }
            ?>

            <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                <?= $teks_tombol; ?>
            </a>

            <?php if ($isOrmawa): ?>
                <?php
                $proposalFile = $isDana ? ($data['proposal_dana'] ?? '') : ($data['proposal_ruangan'] ?? '');
                if (!empty($proposalFile)):
                ?>
                    <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($proposalFile); ?>')">
                        Proposal Kegiatan
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            $asal = $_GET['asal'] ?? '';
            if ($asal == 'tracking' && !$isOrmawa && !empty($data['file_surat_final'])):
                $namaSurat = strtolower($data['nama_surat'] ?? '');

                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                    $link_surat_final = "generate_surat_magang_resmi.php?id=" . $data['id_surat'] . "&view=true&asal=tracking";
                } elseif (strpos($namaSurat, 'aktif') !== false) {
                    $link_surat_final = "generate_sk_aktif_resmi.php?id=" . $data['id_surat'] . "&view=true&asal=tracking";
                } elseif (strpos($namaSurat, 'lulus') !== false) {
                    $link_surat_final = "generate_sk_lulus_resmi.php?id=" . $data['id_surat'] . "&view=true&asal=tracking";
                } else {
                    $link_surat_final = "generate_surat_riset_resmi.php?id=" . $data['id_surat'] . "&view=true&asal=tracking";
                }
            ?>
                <a href="#" class="btn btn-success btn-surat-final" onclick="bukaPreview('<?= $link_surat_final; ?>')">
                    Surat Final
                </a>
            <?php endif; ?>

            <div class="action-panel">
                <?php
                if ($asal == 'tracking') {
                    $link_kembali = 'pimpinan_tracking.php';
                } elseif ($asal == 'riwayat') {
                    $link_kembali = 'pimpinan_riwayat.php';
                } else {
                    $link_kembali = 'pimpinan_verif.php';
                }

                $tampilkan_aksi = ($asal != 'riwayat' && $asal != 'tracking');
                ?>

                <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">
                    Kembali
                </a>

                <?php if ($tampilkan_aksi): ?>
                    <form method="POST" id="formPimpinan" class="form-pimpinan-inline">
                        <input type="hidden" name="aksi" id="aksiInput" value="">
                        <input type="hidden" name="catatan" id="catatanInput" value="">

                        <div class="action-buttons-group">
                            <button type="button" class="btn-styled btn-reject"
                                onclick="konfirmasiTolakPimpinan()" title="Tolak permohonan">
                                Tolak Permohonan
                            </button>

                            <button type="button" class="btn-styled btn-approve"
                                onclick="konfirmasiAksiPimpinan('setujui', 'Yakin ingin menyetujui permohonan ini?', 'success')">
                                Setujui
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-btn" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" class="iframe-preview"></iframe>
        </div>
    </div>

    <script>
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

        function bukaPreview(file) {
            document.getElementById('previewFrame').src = file;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        function konfirmasiTolakPimpinan() {
            Swal.fire({
                title: 'Alasan Penolakan',
                input: 'textarea',
                inputPlaceholder: 'Masukkan alasan penolakan...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                inputValidator: (value) => {
                    if (!value) return 'Anda harus mengisi alasan penolakan!';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('catatanInput').value = result.value;
                    document.getElementById('aksiInput').value = 'tolak';
                    document.getElementById('formPimpinan').submit();
                }
            });
        }

        function konfirmasiAksiPimpinan(aksi, pesan, icon) {
            Swal.fire({
                title: 'Konfirmasi',
                text: pesan,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('aksiInput').value = aksi;
                    document.getElementById('formPimpinan').submit();
                }
            });
        }

        function confirmLogout(event, url) {
            event.preventDefault();

            Swal.fire({
                title: 'Yakin ingin keluar?',
                text: 'Anda harus login kembali untuk mengakses layanan akademik.',
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
    </script>
</body>

</html>