<?php
session_start();
include "koneksi.php";

$id_dosen = $_SESSION['id_dosen'];
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

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
        p.nama_prodi,
        js.nama_surat,
        
        -- Detail Surat Riset
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.id_pb1,         
        dsr.id_pb2,         
        dsr.status_pb1,
        dsr.status_pb2,
        
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
    } elseif (strpos($nama_syarat, 'ktm') !== false) {
        $file_lampiran['ktm'] = $row_lamp['file_upload'];
    } elseif (strpos($nama_syarat, 'cuti') !== false) {
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="dosen_beranda.php">Beranda</a>
            <a href="dosen_permohonan.php">Verifikasi Permohonan</a>
            <a href="dosen_beranda.php#riwayat">Informasi</a>
            <a href="dosen_riwayat.php">Riwayat Verifikasi</a>
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

            <tr>
                <th>Status Saat Ini</th>
                <td class="status-text-amber"><?= htmlspecialchars($data['status_akhir']); ?></td>
            </tr>
        </table>

        <h3 class="section-title mt-4">Dokumen Pendukung</h3>

        <div class="document-box">
            <p><i class="fa-solid fa-file-circle-check" style="margin-right: 8px;"></i> Klik tombol di bawah untuk memeriksa lampiran mahasiswa sebelum melakukan verifikasi.</p>

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

                <?php
                //surat riset
                if (strpos($namaSurat, 'riset') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['proposal'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['proposal']); ?>')"><i class="fa-solid fa-paperclip"></i>Proposal Penelitian</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Proposal Penelitian Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['khs'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')"><i class="fa-solid fa-paperclip"></i>KHS</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KHS Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['ukt'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')"><i class="fa-solid fa-paperclip"></i>Bukti Pembayaran UKT</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Bukti Pembayaran UKT Belum Ada</span>
                    <?php } ?>

                <?php
                    //surat magang
                } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['ktm'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ktm']); ?>')"><i class="fa-solid fa-paperclip"></i>KTM</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KTM Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['ukt'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['ukt']); ?>')"><i class="fa-solid fa-paperclip"></i>Bukti Pembayaran UKT</a>
                    <?php } else { ?>
                        <span class="btn-disabled">Bukti Pembayaran UKT Belum Ada</span>
                    <?php } ?>

                    <?php if (!empty($file_lampiran['khs'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['khs']); ?>')"><i class="fa-solid fa-paperclip"></i>KHS</a>
                    <?php } else { ?>
                        <span class="btn-disabled">KHS Belum Ada</span>
                    <?php } ?>

                <?php
                    //sk aktif kuliah
                } else if (strpos($namaSurat, 'aktif') !== false) {
                ?>
                    <?php if (!empty($file_lampiran['sk_cuti'])) { ?>
                        <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($file_lampiran['sk_cuti']); ?>')"><i class="fa-solid fa-paperclip"></i> SK Cuti</a>
                    <?php } else { ?>
                        <span class="btn-disabled">SK Cuti Belum Ada</span>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="action-panel">
            <?php
            $asal = $_GET['asal'] ?? 'permohonan';

            if ($asal == 'riwayat') {
                $link_kembali = 'dosen_riwayat.php';
            } else {
                $link_kembali = 'dosen_permohonan.php';
            }
            ?>

            <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>

            <?php if ($boleh_verifikasi) { ?>
                <form action="proses_verifikasi_dosen.php" method="POST" id="formVerifikasi">
                    <input type="hidden" name="id_surat" value="<?= $data['id_surat']; ?>">

                    <input type="hidden" name="aksi" id="aksiInput" value="">
                    <button type="button" class="btn-styled btn-reject" onclick="konfirmasiAksi('tolak', 'Yakin ingin menolak permohonan ini?', 'error')">
                        <i class="fa-solid fa-xmark"></i> Tolak
                    </button>

                    <button type="button" class="btn-styled btn-approve" onclick="konfirmasiAksi('setujui', 'Yakin ingin menyetujui permohonan ini?', 'success')">
                        <i class="fa-solid fa-check"></i> Setujui
                    </button>
                </form>
            <?php } ?>
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

        window.onclick = function(event) {
            const modal = document.getElementById('modalPreview');
            if (event.target == modal) {
                tutupPreview();
            }
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
</body>

</html>