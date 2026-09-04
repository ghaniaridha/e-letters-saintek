<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='index.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$is_pembina_ukm = false;
$cek_pembina = mysqli_query($koneksi, "SELECT id_ormawa FROM ormawa WHERE id_pembina = '$id_dosen'");
if ($cek_pembina && mysqli_num_rows($cek_pembina) > 0) {
    $is_pembina_ukm = true;
}

$is_kaprodi = false;
$cek_kaprodi = mysqli_query($koneksi, "SELECT id_prodi FROM prodi WHERE id_kaprodi = '$id_dosen'");
if ($cek_kaprodi && mysqli_num_rows($cek_kaprodi) > 0) {
    $is_kaprodi = true;
}

$punya_akses_ormawa = ($is_pembina_ukm || $is_kaprodi);

$is_pembina_akademik = false;
$cek_akademik = mysqli_query($koneksi, "SELECT id_surat FROM detail_surat_riset WHERE id_pb1 = '$id_dosen' OR id_pb2 = '$id_dosen' LIMIT 1");
if ($cek_akademik && mysqli_num_rows($cek_akademik) > 0) {
    $is_pembina_akademik = true;
}

$punya_keduanya = ($is_pembina_akademik && $punya_akses_ormawa);

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Dosen';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Dosen';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_surat = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        o.nama_ormawa,
        o.jenis_organisasi,    
        p.nama_prodi,
        p.id_kaprodi,
        js.nama_surat,
        
        -- Detail Surat Riset
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.id_pb1,         
        dsr.id_pb2,         
        dsr.status_pb1,
        dsr.status_pb2,
        dsr.catatan_pb1,   
        dsr.catatan_pb2,
        
        -- Detail Surat Magang
        dsm.lokasi_magang,
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        
        -- Detail SK Aktif Kuliah Kembali
        dak.id_pa,          
        dak.lama_cuti,
        dak.ta_mulai_cuti,
        dak.ta_selesai_cuti,
        dak.tahun_akademik,
        dak.status_pa,
        dak.catatan_pa,
        
        -- Detail Peminjaman Ruangan (Ormawa)
        dpr.nama_kegiatan,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        dpr.proposal,              

        -- Detail Pengajuan Dana
        dpd.nama_kegiatan AS nama_kegiatan_dana,
        dpd.tempat_kegiatan,
        dpd.tanggal_kegiatan,
        dpd.proposal AS proposal_dana,
        
        COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester,
        COALESCE(dsr.surat_ditujukan, dsm.surat_ditujukan) AS surat_ditujukan
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa 
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis

    LEFT JOIN prodi p ON (m.id_prodi = p.id_prodi OR o.id_prodi = p.id_prodi)
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat 
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='dosen_permohonan.php';</script>";
    exit;
}

$boleh_verifikasi = false;

// Skenario A: Jika Dosen ini adalah Pembimbing 2 Riset
if (isset($data['id_pb2']) && $data['id_pb2'] == $id_dosen && $data['status_pb2'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// Skenario B: Jika Dosen ini adalah Pembimbing 1 Riset
if (isset($data['id_pb1']) && $data['id_pb1'] == $id_dosen && isset($data['status_pb2']) && $data['status_pb2'] == 'Disetujui' && $data['status_pb1'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// Skenario C: Jika Dosen ini adalah Pembimbing Akademik (SK Aktif Kuliah)
if (isset($data['id_pa']) && $data['id_pa'] == $id_dosen && $data['status_pa'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// Skenario D: Jika Dosen ini adalah Penenggung Jawab Organisasi
$is_pembina_surat_ini = (isset($data['id_pembina']) && $data['id_pembina'] == $id_dosen);
$is_kaprodi_surat_ini = (isset($data['id_kaprodi']) && $data['id_kaprodi'] == $id_dosen);

if (($is_pembina_surat_ini || $is_kaprodi_surat_ini) && $data['posisi_sekarang'] == 'Pembina') {
    $boleh_verifikasi = true;
}

// DATA LAMPIRAN DARI TABEL LAMPIRAN_PENGAJUAN (Khusus Mahasiswa)
$q_lampiran = mysqli_query($koneksi, "
    SELECT ms.nama_syarat, lp.file_upload 
    FROM lampiran_pengajuan lp
    JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
    WHERE lp.id_surat = '$id_surat'
");

$file_lampiran = [];
while ($row_lamp = mysqli_fetch_assoc($q_lampiran)) {
    $nama_syarat = strtolower($row_lamp['nama_syarat']);

    if (strpos($nama_syarat, 'proposal') !== false) {
        $file_lampiran['proposal'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'khs') !== false) {
        $file_lampiran['khs'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'ukt') !== false) {
        $file_lampiran['ukt'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'ktm') !== false) {
        $file_lampiran['ktm'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'cuti') !== false) {
        $file_lampiran['sk_cuti'] = $row_lamp['file_upload'];
    }
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
$tanggal_surat = date('d') . ' ' . $array_bulan[(int)date('m')] . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="dosen_beranda.php">Beranda</a>

            <?php if ($punya_keduanya): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Verifikasi Permohonan<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_permohonan_akademik.php">Akademik</a>
                        <a href="dosen_permohonan_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php elseif ($punya_akses_ormawa): ?>
                <a href="dosen_permohonan_ormawa.php" class="navbar-nav">Verifikasi Permohonan</a>
            <?php else: ?>
                <a href="dosen_permohonan_akademik.php" class="navbar-nav">Verifikasi Permohonan</a>
            <?php endif; ?>

            <a href="dosen_beranda.php#riwayat">Informasi Persuratan</a>

            <?php if ($punya_keduanya): ?>
                <div class="nav-dropdown">
                    <a href="#" class="navbar-nav">Riwayat Verifikasi<i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
                    <div class="dropdown-content">
                        <a href="dosen_riwayat_akademik.php">Akademik</a>
                        <a href="dosen_riwayat_ormawa.php">Ormawa</a>
                    </div>
                </div>
            <?php elseif ($punya_akses_ormawa): ?>
                <a href="dosen_riwayat_ormawa.php" class="navbar-nav">Riwayat Verifikasi</a>
            <?php else: ?>
                <a href="dosen_riwayat_akademik.php" class="navbar-nav">Riwayat Verifikasi</a>
            <?php endif; ?>
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
        <!-- Judul Dinamis -->
        <h3 class="section-title-verif">Detail Permohonan Surat <?= !empty($data['id_ormawa']) ? 'Ormawa' : 'Mahasiswa'; ?></h3>

        <table class="table-detail">
            <tr>
                <th>Jenis Surat</th>
                <td><?= htmlspecialchars($data['nama_surat']); ?></td>
            </tr>

            <?php if (!empty($data['id_ormawa'])) { ?>

                <tr>
                    <th>Nama Organisasi</th>
                    <td><?= htmlspecialchars($data['nama_ormawa']); ?></td>
                </tr>

                <?php if (strpos(strtolower($data['nama_surat']), 'dana') !== false) { ?>

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
                        <th>Tanggal Kegiatan</th>
                        <td>
                            <?php
                            if (!empty($data['tanggal_kegiatan'])) {
                                $ts = strtotime($data['tanggal_kegiatan']);
                                echo $hari_array[date('w', $ts)] . ', ' . date('d', $ts) . ' ' . $array_bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
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
                        <th>Hari & Tanggal</th>
                        <td>
                            <?php
                            if (!empty($data['tanggal_mulai'])) {
                                $ts = strtotime($data['tanggal_mulai']);
                                echo $hari_array[date('w', $ts)] . ', ' . date('d', $ts) . ' ' . $array_bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Ruangan</th>
                        <td>
                            <?= htmlspecialchars($data['ruangan_yang_diajukan'] ?? '-'); ?>
                        </td>
                    </tr>

                <?php } ?>

            <?php } else { ?>
                <!-- JIKA SURAT DARI MAHASISWA -->
                <tr>
                    <th>Nama Mahasiswa</th>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                </tr>
                <tr>
                    <th>NPM</th>
                    <td><?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <th>Program Studi</th>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td><?= htmlspecialchars($data['semester'] ?? '-'); ?></td>
                </tr>
            <?php } ?>

            <?php
            $namaSurat = strtolower($data['nama_surat']);

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
                    <th>Surat Ditujukan Kepada</th>
                    <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                </tr>

            <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                <tr>
                    <th>Lokasi Magang</th>
                    <td><?= htmlspecialchars($data['lokasi_magang'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal Pelaksanaan</th>
                    <td>
                        <?= htmlspecialchars($data['tanggal_mulai_magang'] ?? '-'); ?>
                        s/d
                        <?= htmlspecialchars($data['tanggal_selesai_magang'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <th>Surat Ditujukan Kepada</th>
                    <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                </tr>

            <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                <tr>
                    <th>Lama Cuti</th>
                    <td><?= htmlspecialchars($data['lama_cuti'] ?? '-'); ?></td>
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
            <?php } ?>

            <?php
            $asal_halaman = $_GET['asal'] ?? '';
            $dari_riwayat = (strpos($asal_halaman, 'riwayat') !== false);
            $status_tampil = $data['status_akhir'];

            if (!empty($data['id_ormawa']) && ($data['jenis_organisasi'] ?? '') == 'Ormawa') {
                $status_tampil = str_ireplace('Pembina', 'Kaprodi', $status_tampil);
            }

            $status_lower = strtolower($status_tampil);

            // Deteksi Status
            $status_perbaikan = (strpos($status_lower, 'perbaikan') !== false || strpos($status_lower, 'dikembalikan') !== false);
            $status_ditolak   = (strpos($status_lower, 'ditolak') !== false || strpos($status_lower, 'tolak') !== false);
            $status_menunggu  = (strpos($status_lower, 'menunggu') !== false);

            $label_status = ($dari_riwayat && ($status_ditolak || $status_perbaikan)) ? 'Status Akhir Permohonan' : 'Status Saat Ini';

            // Penentuan Warna Status
            if ($status_perbaikan) {
                $warna_status = 'color: #d97706; font-weight: 700;';
            } elseif ($status_ditolak) {
                $warna_status = 'color: #dc2626; font-weight: 700;';
            } elseif ($status_menunggu) {
                $warna_status = 'color: #d97706; font-weight: 700;';
            } else {
                $warna_status = 'color: #10b981; font-weight: 700;';
            }
            ?>

            <tr>
                <th><?= $label_status; ?></th>
                <td style="<?= $warna_status; ?>">
                    <?= htmlspecialchars($status_tampil); ?>
                </td>
            </tr>

            <?php
            // Semua catatan
            $catatan_admin = $data['alasan_penolakan'] ?? '';
            $catatan_umum  = $data['catatan'] ?? '';
            $catatan_pb1   = $data['catatan_pb1'] ?? '';
            $catatan_pb2   = $data['catatan_pb2'] ?? '';
            $catatan_pa    = $data['catatan_pa'] ?? '';

            // Jika status adalah Perbaikan / Dikembalikan
            if ($status_perbaikan) { ?>
                <tr>
                    <th>Catatan Revisi</th>
                    <td class="catatan-perbaikan-td">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php
                        // Cek surat ORMAWA
                        if (!empty($data['id_ormawa'])) {
                            $pengirim = (($data['jenis_organisasi'] ?? '') == 'Ormawa') ? 'Kaprodi' : 'Pembina';
                            if (!empty($catatan_umum)) {
                                echo "<strong>Dari $pengirim / Admin:</strong><br>" . nl2br(htmlspecialchars($catatan_umum)) . "<br><br>";
                            }
                        } else {
                            // Surat AKADEMIK (Mahasiswa)
                            $ada_ditampilkan = false;

                            if (!empty($catatan_admin)) {
                                echo "<strong>Dari Bagian Akademik:</strong><br>" . nl2br(htmlspecialchars($catatan_admin)) . "<br><br>";
                                $ada_ditampilkan = true;
                            }
                            if (!empty($catatan_pb2)) {
                                echo "<strong>Dari Dosen Pembimbing 2:</strong><br>" . nl2br(htmlspecialchars($catatan_pb2)) . "<br><br>";
                                $ada_ditampilkan = true;
                            }
                            if (!empty($catatan_pb1)) {
                                echo "<strong>Dari Dosen Pembimbing 1:</strong><br>" . nl2br(htmlspecialchars($catatan_pb1)) . "<br><br>";
                                $ada_ditampilkan = true;
                            }
                            if (!empty($catatan_pa)) {
                                echo "<strong>Dari Pembimbing Akademik:</strong><br>" . nl2br(htmlspecialchars($catatan_pa)) . "<br><br>";
                                $ada_ditampilkan = true;
                            }

                            if (!$ada_ditampilkan && !empty($catatan_umum)) {
                                echo "<strong>Catatan:</strong><br>" . nl2br(htmlspecialchars($catatan_umum)) . "<br><br>";
                            }
                        }
                        ?>
                    </td>
                </tr>
            <?php
                // Jika statusnya Ditolak
            } elseif ($status_ditolak && !empty($catatan_umum)) { ?>
                <tr>
                    <th>Catatan Penolakan</th>
                    <td class="status-tolak">
                        <?= nl2br(htmlspecialchars($catatan_umum)); ?>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <h3 class="section-title mt-4">Dokumen Pendukung</h3>

        <div class="document-box">
            <p><i class="fa-solid fa-file-circle-check"></i> Klik tombol di bawah untuk memeriksa lampiran sebelum melakukan verifikasi.</p>

            <div class="document-buttons">
                <?php
                // Penentuan Jalur Surat Hasil Generate Sistem 
                if (!empty($data['id_ormawa'])) {

                    if (strpos(strtolower($data['nama_surat']), 'dana') !== false) {
                        $fileSystemPreview = "preview_pengajuan_dana.php?id=" . $data['id_surat'];
                    } else {
                        $fileSystemPreview = "preview_peminjaman_ruangan.php?id=" . $data['id_surat'] . "&mode=view";
                    }
                } else {
                    $fileSystemPreview = "preview_surat_riset_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                    if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                        $fileSystemPreview = "preview_surat_magang_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                    } else if (strpos($namaSurat, 'aktif') !== false) {
                        $fileSystemPreview = "preview_sk_aktif_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                    }
                }
                ?>

                <!-- Surat Hasil Sistem / Preview -->
                <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $fileSystemPreview; ?>')">
                    <?= !empty($data['id_ormawa']) ? 'Surat Permohonan' : 'Surat Permohonan'; ?>
                </a>

                <?php
                // TAMPILAN SURAT DARI ORMAWA
                if (!empty($data['id_ormawa'])) {
                ?>
                    <!-- File Proposal  -->
                    <?php if (strpos(strtolower($data['nama_surat']), 'dana') !== false) { ?>

                        <?php if (!empty($data['proposal_dana'])) { ?>
                            <a href="#"
                                class="btn btn-edit"
                                onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($data['proposal_dana']); ?>')">
                                Proposal Kegiatan
                            </a>
                        <?php } else { ?>
                            <span class="btn-disabled">Proposal Kegiatan Belum Ada</span>
                        <?php } ?>

                    <?php } else { ?>

                        <?php if (!empty($data['proposal'])) { ?>
                            <a href="#"
                                class="btn btn-edit"
                                onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($data['proposal']); ?>')">
                                Proposal Kegiatan
                            </a>
                        <?php } else { ?>
                            <span class="btn-disabled">Proposal Kegiatan Belum Ada</span>
                        <?php } ?>

                    <?php } ?>

                <?php
                    // TAMPILAN BERKAS SURAT RISET  
                } else if (strpos($namaSurat, 'riset') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['proposal'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['proposal']); ?>')">Proposal Penelitian</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Proposal Penelitian Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['khs'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KHS Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['ukt'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti Pembayaran UKT</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Bukti Pembayaran UKT Belum Ada</span>
                    <?php } ?>

                <?php
                    // TAMPILAN BERKAS SURAT MAGANG MAHASISWA 
                } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['ktm'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ktm']); ?>')">KTM</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KTM Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['ukt'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti Pembayaran UKT</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Bukti Pembayaran UKT Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['khs'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KHS Belum Ada</span>
                    <?php } ?>

                <?php
                    // --- TAMPILAN BERKAS AKTIF KULIAH 
                } else if (strpos($namaSurat, 'aktif') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['sk_cuti'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['sk_cuti']); ?>')">SK Cuti</a>
                    <?php } else { ?>
                        <span class="btn-disabled">SK Cuti Belum Ada</span>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="action-panel">
            <?php
            $asal = $_GET['asal'] ?? 'akademik';

            if ($asal == 'riwayat_akademik') {
                $link_kembali = 'dosen_riwayat_akademik.php';
            } elseif ($asal == 'riwayat_ormawa') {
                $link_kembali = 'dosen_riwayat_ormawa.php';
            } elseif ($asal == 'ormawa' || !empty($data['id_ormawa'])) {
                $link_kembali = 'dosen_permohonan_ormawa.php';
            } else {
                $link_kembali = 'dosen_permohonan_akademik.php';
            }
            ?>

            <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">
                Kembali
            </a>

            <?php if ($boleh_verifikasi) { ?>
                <form action="proses_verifikasi_dosen.php" method="POST" id="formVerifikasi">
                    <input type="hidden" name="id_surat" value="<?= $data['id_surat']; ?>">
                    <input type="hidden" name="aksi" id="aksiInput" value="">
                    <input type="hidden" name="catatan" id="catatanInput" value="">

                    <button type="button" class="btn-styled btn-reject-amber" onclick="konfirmasiKembalikan()">
                        Kembalikan Permohonan
                    </button>

                    <button type="button" class="btn-styled btn-approve" onclick="konfirmasiAksi('setujui', 'Yakin ingin menyetujui permohonan ini?', 'success')">
                        Setujui
                    </button>
                </form>
            <?php } ?>
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

        window.onclick = function(event) {
            const modal = document.getElementById('modalPreview');
            if (event.target == modal) {
                tutupPreview();
            }
        }

        function konfirmasiKembalikan() {
            Swal.fire({
                title: 'Catatan Revisi / Pengembalian',
                input: 'textarea',
                inputPlaceholder: 'Masukkan instruksi revisi atau alasan pengembalian ke mahasiswa/ormawa...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6b7280',
                inputValidator: (value) => {
                    if (!value) return 'Anda harus mengisi catatan revisi untuk mahasiswa!';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('catatanInput').value = result.value;
                    document.getElementById('aksiInput').value = 'kembalikan';
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }

        // Fungsi untuk Setujui
        function konfirmasiAksi(aksi, pesan, icon) {
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
                    document.getElementById('formVerifikasi').submit();
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
    </script>
</body>

</html>