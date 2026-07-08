<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];
$id_surat = $_GET['id'];

// PERBAIKAN 1: Tambahkan LEFT JOIN ke tabel detail_surat_riset
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        
        -- Detail Surat Riset
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.id_pb1,         /* <-- PERBAIKAN: Tambahkan ini */
        dsr.id_pb2,         /* <-- PERBAIKAN: Tambahkan ini */
        dsr.status_pb1,
        dsr.status_pb2,
        
        -- Detail Surat Magang
        dsm.lokasi_magang,
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        
        -- Detail SK Aktif Kuliah Kembali
        dak.id_pa,          /* <-- PERBAIKAN: Tambahkan ini agar validasi PA juga bisa jalan */
        dak.lama_cuti,
        dak.ta_mulai_cuti,
        dak.ta_selesai_cuti,
        dak.tahun_akademik,
        dak.status_pa,
        
        -- Menggabungkan kolom sejenis agar seragam dipanggil di HTML
        COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester,
        COALESCE(dsr.surat_ditujukan, dsm.surat_ditujukan) AS surat_ditujukan
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='dosen_permohonan.php';</script>";
    exit;
}

$boleh_verifikasi = false; // Inisialisasi default

// Skenario A: Jika Dosen ini adalah Pembimbing 2 Riset
if (isset($data['id_pb2']) && $data['id_pb2'] == $id_dosen && $data['status_pb2'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// Skenario B: Jika Dosen ini adalah Pembimbing 1 Riset
if (isset($data['id_pb1']) && $data['id_pb1'] == $id_dosen && isset($data['status_pb2']) && $data['status_pb2'] == 'Disetujui' && $data['status_pb1'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// PERBAIKAN: Skenario C: Jika Dosen ini adalah Pembimbing Akademik (SK Aktif Kuliah)
if (isset($data['id_pa']) && $data['id_pa'] == $id_dosen && $data['status_pa'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

// AMBIL DATA LAMPIRAN DARI TABEL LAMPIRAN_PENGAJUAN
$q_lampiran = mysqli_query($koneksi, "
    SELECT ms.nama_syarat, lp.file_upload 
    FROM lampiran_pengajuan lp
    JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
    WHERE lp.id_surat = '$id_surat'
");

// Susun file ke dalam array yang rapi agar mudah dipanggil
$file_lampiran = [];
while ($row_lamp = mysqli_fetch_assoc($q_lampiran)) {
    $nama_syarat = strtolower($row_lamp['nama_syarat']);

    if (strpos($nama_syarat, 'proposal') !== false) {
        $file_lampiran['proposal'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'khs') !== false) {
        $file_lampiran['khs'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'ukt') !== false) {
        $file_lampiran['ukt'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'ktm') !== false) {    // Tambahan untuk Surat Magang
        $file_lampiran['ktm'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'cuti') !== false) {   // Tambahan untuk SK Aktif Kuliah
        $file_lampiran['sk_cuti'] = $row_lamp['file_upload'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
</head>

<body class="dosen-page">

    <div class="admin-wrapper">
        <main class="main-content">

            <div class="page-title">
                <h1>Detail Permohonan Surat</h1>
                <p>Periksa data permohonan sebelum melakukan verifikasi.</p>
            </div>

            <div class="table-card">

                <h3>Data Permohonan</h3>
                <br>

                <table>
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

                    <?php
                    $namaSurat = strtolower($data['nama_surat']);

                    if (strpos($namaSurat, 'riset') !== false) {
                        // Kategori Surat Riset
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

                    <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                        // Kategori Surat Magang
                    ?>
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

                    <?php } else if (strpos($namaSurat, 'aktif') !== false) {
                        // Kategori SK Aktif Kuliah Kembali
                    ?>
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

                <br><br>

                <h3>Dokumen Surat</h3>

                <div style="
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:20px;
    margin-top:15px;
    margin-bottom:20px;
">
                    <p>Klik tombol di bawah untuk melihat preview surat dan dokumen pendukung mahasiswa.</p>
                    <br>

                    <?php
                    // LOGIKA PENENTUAN HALAMAN PREVIEW BERDASARKAN JENIS SURAT
                    $filePreview = "preview_surat.php?id=" . $data['id_surat']; // Default untuk riset

                    if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                        $filePreview = "preview_magang.php?id=" . $data['id_surat'];
                    } else if (strpos($namaSurat, 'aktif') !== false) {
                        $filePreview = "mhs_preview_sk_aktif.php?id=" . $data['id_surat'];
                    }
                    ?>

                    <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                        Preview Surat
                    </a>

                    <?php
                    /* ==========================================
       TAMPILAN DOKUMEN KHUSUS SURAT RISET
       ========================================== */
                    if (strpos($namaSurat, 'riset') !== false) {
                    ?>
                        <?php if (!empty($file_lampiran['proposal'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['proposal']); ?>')">Proposal</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">Proposal Belum Ada</span>
                        <?php } ?>

                        <?php if (!empty($file_lampiran['khs'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">KHS Belum Ada</span>
                        <?php } ?>

                        <?php if (!empty($file_lampiran['ukt'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti UKT</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">Bukti UKT Belum Ada</span>
                        <?php } ?>

                    <?php
                        /* ==========================================
       TAMPILAN DOKUMEN KHUSUS SURAT MAGANG
       ========================================== */
                    } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                    ?>
                        <?php if (!empty($file_lampiran['ktm'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ktm']); ?>')">KTM</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">KTM Belum Ada</span>
                        <?php } ?>

                        <?php if (!empty($file_lampiran['ukt'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')">Bukti UKT</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">Bukti UKT Belum Ada</span>
                        <?php } ?>

                        <?php if (!empty($file_lampiran['khs'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')">KHS</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">KHS Belum Ada</span>
                        <?php } ?>

                    <?php
                        /* ==========================================
       TAMPILAN DOKUMEN KHUSUS SK AKTIF KULIAH
       ========================================== */
                    } else if (strpos($namaSurat, 'aktif') !== false) {
                    ?>
                        <?php if (!empty($file_lampiran['sk_cuti'])) { ?>
                            <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['sk_cuti']); ?>')">SK Cuti</a>
                        <?php } else { ?>
                            <span class="btn btn-delete">SK Cuti Belum Ada</span>
                        <?php } ?>

                    <?php } ?>

                </div>
                <?php if ($boleh_verifikasi) { ?>
                    <form action="proses_verifikasi_dosen.php" method="POST">
                        <input type="hidden" name="id_surat" value="<?= $data['id_surat']; ?>">

                        <button type="submit" name="aksi" value="setujui" class="btn btn-edit">
                            Setujui
                        </button>

                        <button type="submit" name="aksi" value="tolak" class="btn btn-delete"
                            onclick="return confirm('Yakin ingin menolak permohonan ini?')">
                            Tolak
                        </button>

                        <a href="dosen_permohonan.php" class="btn btn-detail">
                            Kembali
                        </a>
                    </form>
                <?php } else { ?>
                    <a href="dosen_permohonan.php" class="btn btn-detail">
                        Kembali
                    </a>
                <?php } ?>

            </div>

        </main>
    </div>

    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-preview" onclick="tutupPreview()">&times;</span>

            <iframe
                id="previewFrame"
                width="100%"
                height="600px"
                style="border:none;">
            </iframe>
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
    </script>

</body>

</html>