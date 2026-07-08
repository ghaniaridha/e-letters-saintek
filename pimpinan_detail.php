<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai pimpinan'); window.location='index.php';</script>";
    exit;
}

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'Pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'Pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_surat = $_GET['id'] ?? '';
$id_dosen = $_SESSION['id_dosen'] ?? 0;

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
        
        -- Detail SK Aktif Kuliah
        dak.lama_cuti,
        dak.ta_mulai_cuti,
        dak.ta_selesai_cuti,
        dak.tahun_akademik,
        
        -- Gabungan Kolom
        COALESCE(dsm.surat_ditujukan, dsr.surat_ditujukan) AS surat_ditujukan,
        COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    
    -- JOIN ke tiga tabel detail
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    
    WHERE sp.id_surat = '$id_surat'
");

$data = mysqli_fetch_assoc($query_detail);

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

$namaSurat = strtolower($data['nama_surat']);

// Penentuan Rute Preview Surat Secara Dinamis
if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
    $filePreview = "preview_magang.php?id=" . $data['id_surat'];
} else if (strpos($namaSurat, 'aktif') !== false) {
    $filePreview = "mhs_preview_sk_aktif.php?id=" . $data['id_surat'];
} else {
    $filePreview = "preview_surat.php?id=" . $data['id_surat'];
}

if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

    // Gunakan trim untuk menghindari bug spasi kosong pada database
    $status_skrg = trim($data['status_akhir']);

    if ($aksi == 'setujui') {

        // --- KONDISI: MENUNGGU WADEK 1 ---
        if ($status_skrg == 'Menunggu Wadek 1') {
            if (strpos($namaSurat, 'aktif') !== false) {
                // SK Aktif Kuliah -> Selesai
                mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Selesai', status_pimpinan = 'Disetujui', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");
                echo "<script>alert('SK Aktif Kuliah disetujui.'); window.location='generate_sk_aktif_resmi.php?id=$id_surat';</script>";
            } else {
                // Surat Lain -> Surat Balasan
                mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Surat Balasan', status_pimpinan = 'Disetujui', status_balasan = 'Draft', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");
                echo "<script>alert('Surat disetujui. Silakan buat surat balasan fakultas.'); window.location='generate_balasan_fakultas.php?id=$id_surat';</script>";
            }
            exit;
        }

        // --- KONDISI: MENUNGGU DEKAN ---
        elseif ($status_skrg == 'Menunggu Dekan') {
            if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Surat Balasan', status_pimpinan = 'Disetujui', status_balasan = 'Draft', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");
                echo "<script>alert('Surat disetujui. Silakan buat surat balasan.'); window.location='generate_balasan_magang.php?id=$id_surat';</script>";
            } else {
                mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Selesai', status_pimpinan = 'Disetujui', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");
                echo "<script>alert('Surat berhasil disetujui.'); window.location='pimpinan_verif.php';</script>";
            }
            exit;
        }

        // --- KONDISI: MENUNGGU WADEK 2 ATAU KASUBAG ---
        elseif ($status_skrg == 'Menunggu Wadek 2' || $status_skrg == 'Menunggu Kasubag') {
            mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Dekan', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");
            echo "<script>alert('Surat berhasil disetujui dan diteruskan ke Dekan.'); window.location='pimpinan_verif.php';</script>";
            exit;
        }

        // Jika status tidak terdeteksi
        else {
            echo "<script>alert('Status surat tidak valid (saat ini: $status_skrg).'); window.location='pimpinan_verif.php';</script>";
            exit;
        }
    }

    // --- AKSI TOLAK (Diletakkan SEJAJAR dengan setujui) ---
    elseif ($aksi == 'tolak') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Ditolak Pimpinan' WHERE id_surat = '$id_surat'");
        echo "<script>alert('Surat berhasil ditolak.'); window.location='pimpinan_verif.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Verifikasi Pimpinan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .pimpinan-content {
            padding: 130px 7% 60px;
            background: #f3f4f6;
            min-height: 100vh;
        }

        .pimpinan-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);
        }

        .action-row {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .modal-preview {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .65);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-content-preview {
            width: 88%;
            max-width: 1050px;
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            position: relative;
        }

        .close-preview {
            position: absolute;
            right: 18px;
            top: 8px;
            font-size: 30px;
            cursor: pointer;
        }
    </style>
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
            <a href="pimpinan_tracking.php">Tracking</a>
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
                </div>
            </div>
        </div>
    </nav>

    <section class="pimpinan-content">
        <div class="pimpinan-card">
            <h2>Detail Permohonan Surat</h2>
            <br>

            <table>
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
                            <?= htmlspecialchars($data['tanggal_mulai_magang'] ?? '-'); ?>
                            s/d
                            <?= htmlspecialchars($data['tanggal_selesai_magang'] ?? '-'); ?>
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

                <?php } ?>

                <tr>
                    <th>Status Saat Ini</th>
                    <td><?= htmlspecialchars($data['status_akhir']); ?></td>
                </tr>
            </table>
        </div>

        <div class="action-row">
            <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                Preview Surat
            </a>

            <form method="POST" style="display:inline;">
                <button type="submit" name="aksi" value="setujui" class="btn btn-edit">
                    Setujui
                </button>

                <button type="submit" name="aksi" value="tolak" class="btn btn-delete"
                    onclick="return confirm('Yakin ingin menolak surat ini?')">
                    Tolak
                </button>
            </form>

            <a href="pimpinan_verif.php" class="btn btn-detail">
                Kembali
            </a>
        </div>
        </div>
    </section>

    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-preview" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" width="100%" height="620px" style="border:none;"></iframe>
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
    </script>

</body>

</html>