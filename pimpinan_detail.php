<?php
session_start();
include "koneksi.php";

$id_dosen = $_SESSION['id_dosen'] ?? 0;
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

// Rute Surat Permohonan Mahasiswa
if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
    $filePreview = "preview_magang.php?id=" . $data['id_surat'];
} else if (strpos($namaSurat, 'aktif') !== false) {
    $filePreview = "mhs_preview_sk_aktif.php?id=" . $data['id_surat'];
} else {
    $filePreview = "preview_surat.php?id=" . $data['id_surat'];
}

// Aksi Setuju/Tolak Permoonan
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
            catatan='$catatan'
        WHERE id_surat='$id_surat'
    ";

    mysqli_query($koneksi, $sql);

    $_SESSION['status'] = 'success';
    $_SESSION['pesan'] = 'Permohonan berhasil ditolak';

    header("Location: pimpinan_riwayat.php");
    exit;
}


    // ==========================
    // BARU PROSES SETUJU
    // ==========================
    $hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

    $status_skrg = trim($data['status_akhir']);

  

    // Aksi Setuju
    // Kondisi Menunggu Wadek 1
    if ($status_skrg == 'Menunggu Wadek 1') {
        if (strpos($namaSurat, 'aktif') !== false) {
            // SK Aktif Kuliah -> Selesai
            mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Selesai', status_pimpinan = 'Disetujui', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Permohonan berhasil disetujui.';
            header("Location: generate_sk_aktif_resmi.php?id=$id_surat");
            exit;
        } else {
            // Surat Lain -> Surat Balasan
            mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Surat Balasan', status_pimpinan = 'Disetujui', status_balasan = 'Draft', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Permohonan berhasil disetujui.';
            header("Location: generate_surat_riset_resmi.php?id=$id_surat");
            exit;
        }
    }

    // Kondisi Menunggu Dekan
    elseif ($status_skrg == 'Menunggu Dekan') {
        if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
            mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Surat Balasan', status_pimpinan = 'Disetujui', status_balasan = 'Draft', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Permohonan berhasil disetujui.';
            header("Location: generate_surat_magang_resmi.php?id=$id_surat");
            exit;
        } else {
            mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Selesai', status_pimpinan = 'Disetujui', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Permohonan berhasil disetujui.';
            header("Location: pimpinan_riwayat.php");
            exit;
        }
    }

    // Kondisi Menunggu Wadek 2 atau Kasubbag TU
    elseif ($status_skrg == 'Menunggu Wadek 2' || $status_skrg == 'Menunggu Kasubag') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Dekan', ttd_pimpinan = '$hash_ttd' WHERE id_surat = '$id_surat'");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat berhasil disetujui dan diteruskan ke Dekan.';
        header("Location: pimpinan_riwayat.php");
        exit;
    }

    // Jika status tidak terdeteksi
    else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = "Status surat tidak valid (saat ini: $status_skrg).";
        header("Location: pimpinan_verif.php");
        exit;
    }
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

    <div class="table-card">
        <h3 class="section-title-verif">Detail Permohonan Surat Mahasiswa</h3>
        <table class="table-detail">
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
                <td class="status-text-amber"><?= htmlspecialchars($data['status_akhir']); ?></td>
            </tr>
        </table>

        <h3 class="section-title mt-4">Dokumen Pendukung</h3>

        <div class="document-buttons">
            <?php
            $filePreview = "preview_surat_riset_mhs.php?id=" . $data['id_surat'] . "&mode=view";

            if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                $filePreview = "preview_surat_magang_mhs.php?id=" . $data['id_surat'] . "&mode=view";
            } else if (strpos($namaSurat, 'aktif') !== false) {
                $filePreview = "preview_sk_aktif_mhs.php?id=" . $data['id_surat'] . "&mode=view";
            }
            ?>

            <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                <i class="fa-regular fa-eye"></i> Surat Permohonan
            </a>
        </div>

        <div class="action-panel">
            <a href="pimpinan_verif.php" class="btn-styled btn-back">
                Kembali
            </a>
            <form method="POST" id="formPimpinan" style="display:inline;">
                <!-- Input hidden untuk menangkap aksi -->
                <input type="hidden" name="aksi" id="aksiInput" value="">
                <input type="hidden" name="catatan" id="catatanInput" value="">

                <button type="button" class="btn-styled btn-reject" 
                        onclick="konfirmasiTolakPimpinan()">
                    Tolak
                </button>

                <button type="button" class="btn-styled btn-approve" 
                        onclick="konfirmasiAksiPimpinan('setujui', 'Yakin ingin menyetujui permohonan ini?', 'success')">
                    Setujui
                </button>
            </form>
        </div>
    </div>

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

        function konfirmasiAksi(aksi, pesan, icon) {
            Swal.fire({
                title: 'Konfirmasi',
                text: pesan,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: (aksi === 'setujui') ? '#10b981' : '#ef4444',
                cancelButtonColor: '#94a3b8',
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


<script>
function konfirmasiTolakPimpinan() {
    Swal.fire({
        title: 'Alasan Penolakan',
        input: 'textarea',
        inputPlaceholder: 'Masukkan alasan penolakan...',
        showCancelButton: true,
        confirmButtonText: 'Kirim',
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
        confirmButtonText: 'Ya, Lanjutkan!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('aksiInput').value = aksi;
            document.getElementById('formPimpinan').submit();
        }
    });
}
</script>
</body>

</html>