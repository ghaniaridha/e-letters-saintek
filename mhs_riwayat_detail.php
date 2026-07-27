<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
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
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        
        -- Detail Surat Riset
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        
        -- Detail Surat Magang
        dsm.lokasi_magang,
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        
        -- Detail SK Aktif Kuliah Kembali
        dak.lama_cuti,
        dak.ta_mulai_cuti,
        dak.ta_selesai_cuti,
        dak.tahun_akademik,
        
        COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester,
        COALESCE(dsr.surat_ditujukan, dsm.surat_ditujukan) AS surat_ditujukan
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    WHERE sp.id_surat = '$id_surat' AND sp.id_mhs = '$id_mhs'
");

$data = mysqli_fetch_assoc($query_detail);

if (!$data) {
    echo "<script>alert('Data permohonan tidak ditemukan atau Anda tidak memiliki hak akses.'); window.location='mhs_riwayat.php';</script>";
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="en">

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
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_lacak.php">Lacak Surat</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= ($namaLengkap) ?></span>
                        <span class="user-role"><?= $idLogin ?> - <?= $role ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="table-card">
        <h3 class="section-title-verif">Detail Permohonan Surat Akademik</h3>

        <table class="table-detail">
            <tr>
                <th>Jenis Surat</th>
                <td><?= htmlspecialchars($data['nama_surat']); ?></td>
            </tr>
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

            <!-- DETAIL SPESIFIK JENIS SURAT AKADEMIK -->
            <?php if (strpos($namaSurat, 'riset') !== false) { ?>
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
                        <?= htmlspecialchars($data['tanggal_mulai_magang'] ?? '-'); ?> s/d
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
            <?php } ?>

            <?php
            $status_lower = strtolower($data['status_akhir']);
            $status_ditolak = (strpos($status_lower, 'ditolak') !== false);
            $status_menunggu = (strpos($status_lower, 'menunggu') !== false);

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

            <?php if ($status_ditolak && !empty($data['catatan'])) { ?>
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
            <p><i class="fa-solid fa-file-circle-check"></i> Klik tombol di bawah untuk memeriksa lampiran atau hasil surat.</p>

            <div class="document-buttons">
                <?php
                $fileSystemPreview = "preview_surat_riset_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                    $fileSystemPreview = "preview_surat_magang_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } else if (strpos($namaSurat, 'aktif') !== false) {
                    $fileSystemPreview = "preview_sk_aktif_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                }
                ?>
                <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $fileSystemPreview; ?>')">
                    Surat Permohonan
                </a>

                <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                    <?php if (!empty($file_lampiran['proposal'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['proposal']); ?>')">Proposal Penelitian</a><?php } ?>
                    <?php if (!empty($file_lampiran['khs'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a><?php } ?>
                    <?php if (!empty($file_lampiran['ukt'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti UKT</a><?php } ?>
                <?php } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                    <?php if (!empty($file_lampiran['ktm'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ktm']); ?>')">KTM</a><?php } ?>
                    <?php if (!empty($file_lampiran['ukt'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti UKT</a><?php } ?>
                    <?php if (!empty($file_lampiran['khs'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a><?php } ?>
                <?php } elseif (strpos($namaSurat, 'aktif') !== false) { ?>
                    <?php if (!empty($file_lampiran['sk_cuti'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['sk_cuti']); ?>')">SK Cuti</a><?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="action-panel">
            <a href="mhs_riwayat.php" class="btn-styled btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- MODAL PREVIEW IFRAME -->
    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-btn" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" class="iframe-preview"></iframe>
        </div>
    </div>

    <script>
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

        //fungsi dropdown menu user
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

        document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.navbar-nav')?.classList.toggle('active');
        });
    </script>

</body>

</html>