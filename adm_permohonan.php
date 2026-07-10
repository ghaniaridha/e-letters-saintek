<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$prodi           = $_GET['prodi'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

/*Proses Lanjutkan/Tolak Permohonan*/
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    $dataSurat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT sp.*, js.nama_surat
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    "));

    if ($dataSurat) {
        if ($aksi == 'lanjut') {
            $namaSurat = strtolower($dataSurat['nama_surat']);

            //Arah Surat Berdasarkan Jenis 
            if (strpos($namaSurat, 'riset') !== false || strpos($namaSurat, 'aktif') !== false) {
                $statusBaru = 'Menunggu Wadek 1';
            } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                $statusBaru = 'Menunggu Dekan';
            } else {
                $statusBaru = 'Menunggu Dekan'; //Default
            }

            mysqli_query($koneksi, "
                UPDATE surat_pengajuan
                SET status_akhir = '$statusBaru'
                WHERE id_surat = '$id_surat'
            ");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Surat berhasil diteruskan ke $statusBaru';
            header("Location: adm_riwayat_review.php");
            exit;
        }

        if ($aksi == 'tolak') {
            mysqli_query($koneksi, "
                UPDATE surat_pengajuan
                SET status_akhir = 'Ditolak Admin'
                WHERE id_surat = '$id_surat'
            ");

            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Surat berhasil ditolak';
            header("Location: adm_riwayat_review.php");
            exit;
        }
    }
}

/*Proses Hapus Data*/
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = $id");

    echo "<script>alert('Permohonan berhasil dihapus'); window.location='adm_permohonan.php';</script>";
    exit;
}

/*Filter Pencarian Data*/
$where = "WHERE sp.status_akhir = 'Menunggu Admin'";

if ($prodi != "") {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.id_prodi = '$prodiAman'";
}

if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

// Data untuk dropdown filter
$jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat ORDER BY nama_surat ASC");

/*Query Utama*/
$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.id_jenis,
        m.npm,
        m.nama_mhs,
        p.nama_prodi,
        js.nama_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
    ORDER BY sp.tanggal_pengajuan DESC
");

/*Query Detail Review*/
$detail = null;
$lampiran = [];

if ($detail_id != "") {
    $detail_id = (int) $detail_id;

    $query_detail = mysqli_query($koneksi, "
        SELECT 
            sp.*, 
            m.nama_mhs, 
            m.npm, 
            p.nama_prodi, 
            js.nama_surat,
            
            -- Detail Riset
            dsr.judul_skripsi, 
            dsr.lokasi_penelitian, 
            
            -- Detail Magang
            dsm.lokasi_magang,
            dsm.tanggal_mulai_magang,
            dsm.tanggal_selesai_magang,
            
            -- Detail Aktif Kuliah
            dak.lama_cuti,
            dak.ta_mulai_cuti,
            dak.ta_selesai_cuti,
            dak.tahun_akademik,
            
            -- Menggabungkan kolom sejenis
            COALESCE(dsm.surat_ditujukan, dsr.surat_ditujukan) AS surat_ditujukan,
            COALESCE(dsr.semester, dsm.semester, dak.semester) AS semester
        FROM surat_pengajuan sp
        JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        JOIN prodi p ON m.id_prodi = p.id_prodi
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
        LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
        LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
        WHERE sp.id_surat = '$detail_id'
    ");

    $detail = mysqli_fetch_assoc($query_detail);

    if ($detail) {
        $q_lampiran = mysqli_query($koneksi, "
            SELECT ms.nama_syarat, lp.file_upload 
            FROM lampiran_pengajuan lp
            JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
            WHERE lp.id_surat = '$detail_id'
        ");

        while ($row_lamp = mysqli_fetch_assoc($q_lampiran)) {
            $nama_syarat = strtolower($row_lamp['nama_syarat']);

            if (strpos($nama_syarat, 'proposal') !== false) {
                $lampiran['proposal'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'khs') !== false) {
                $lampiran['khs'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'ukt') !== false) {
                $lampiran['ukt'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'ktm') !== false) {
                $lampiran['ktm'] = $row_lamp['file_upload'];
            } elseif (strpos($nama_syarat, 'cuti') !== false) {
                $lampiran['sk_cuti'] = $row_lamp['file_upload'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permohonan Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        .review-box {
            background: #fff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
        }

        .review-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
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
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">

            <div class="page-title">
                <h1>Kelola Permohonan Surat</h1>
                <p>Permohonan yang sudah disetujui dosen dan menunggu verifikasi admin.</p>
            </div>

            <?php if ($detail) {
                $namaSurat = strtolower($detail['nama_surat']);
            ?>
                <div class="review-box">
                    <h2>Review Permohonan Surat</h2>
                    <br>

                    <table>
                        <tr>
                            <th>NPM</th>
                            <td><?= htmlspecialchars($detail['npm']); ?></td>
                        </tr>
                        <tr>
                            <th>Nama Mahasiswa</th>
                            <td><?= htmlspecialchars($detail['nama_mhs']); ?></td>
                        </tr>
                        <tr>
                            <th>Program Studi</th>
                            <td><?= htmlspecialchars($detail['nama_prodi']); ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Surat</th>
                            <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                        </tr>
                        <tr>
                            <th>Semester</th>
                            <td><?= htmlspecialchars($detail['semester'] ?? '-'); ?></td>
                        </tr>

                        <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                            <tr>
                                <th>Judul Skripsi</th>
                                <td><?= htmlspecialchars($detail['judul_skripsi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi Penelitian</th>
                                <td><?= htmlspecialchars($detail['lokasi_penelitian'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                            <tr>
                                <th>Lokasi Magang</th>
                                <td><?= htmlspecialchars($detail['lokasi_magang'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Magang</th>
                                <td>
                                    <?= htmlspecialchars($detail['tanggal_mulai_magang'] ?? '-'); ?> s/d <?= htmlspecialchars($detail['tanggal_selesai_magang'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                            <tr>
                                <th>Lama Cuti</th>
                                <td><?= htmlspecialchars($detail['lama_cuti'] ?? '-'); ?> Semester</td>
                            </tr>
                            <tr>
                                <th>Periode Masa Cuti</th>
                                <td>
                                    Gasal: <?= htmlspecialchars($detail['ta_mulai_cuti'] ?? '-'); ?> <br>
                                    Genap: <?= htmlspecialchars($detail['ta_selesai_cuti'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Tahun Akademik Aktif</th>
                                <td><?= htmlspecialchars($detail['tahun_akademik'] ?? '-'); ?></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <th>Status Saat Ini</th>
                            <td><?= htmlspecialchars($detail['status_akhir']); ?></td>
                        </tr>
                    </table>

                    <div class="review-actions" style="margin-top: 20px;">
                        <?php
                        // Penentuan halaman preview
                        $filePreview = "preview_surat.php?id=" . $detail['id_surat'];
                        if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                            $filePreview = "preview_magang.php?id=" . $detail['id_surat'];
                        } else if (strpos($namaSurat, 'aktif') !== false) {
                            $filePreview = "mhs_preview_sk_aktif.php?id=" . $detail['id_surat'];
                        }
                        ?>

                        <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">
                            Review Surat
                        </a>

                        <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                            <?php if (!empty($lampiran['proposal'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['proposal']); ?>')">Proposal</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['khs'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['khs']); ?>')">KHS</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['ukt'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['ukt']); ?>')">Bukti UKT</a>
                            <?php } ?>

                        <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                            <?php if (!empty($lampiran['ktm'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['ktm']); ?>')">KTM</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['ukt'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['ukt']); ?>')">Bukti UKT</a>
                            <?php } ?>
                            <?php if (!empty($lampiran['khs'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['khs']); ?>')">KHS</a>
                            <?php } ?>

                        <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                            <?php if (!empty($lampiran['sk_cuti'])) { ?>
                                <a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lampiran['sk_cuti']); ?>')">SK Cuti</a>
                            <?php } ?>
                        <?php } ?>

                    </div>
                </div>

                <form method="POST" class="review-actions">
                    <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">

                    <button type="submit" name="aksi_admin" value="lanjut" class="btn btn-edit">
                        Lanjutkan ke Pimpinan
                    </button>

                    <button type="submit" name="aksi_admin" value="tolak" class="btn btn-delete"
                        onclick="return confirm('Yakin ingin menolak surat ini?')">
                        Tolak
                    </button>

                    <a href="adm_permohonan.php" class="btn btn-detail">
                        Kembali
                    </a>
                </form>
    </div>
<?php } ?>

<div class="filter-container">
    <form method="GET">
        <select name="prodi">
            <option value="">Semua Prodi</option>
            <option value="Sistem Informasi" <?= $prodi == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
            <option value="Kimia" <?= $prodi == 'Kimia' ? 'selected' : ''; ?>>Kimia</option>
            <option value="Biologi" <?= $prodi == 'Biologi' ? 'selected' : ''; ?>>Biologi</option>
            <option value="Sains Data" <?= $prodi == 'Sains Data' ? 'selected' : ''; ?>>Sains Data</option>
        </select>

        <select name="id_jenis">
            <option value="">Semua Jenis Surat</option>
            <?php while ($js = mysqli_fetch_assoc($jenisSurat)) { ?>
                <option value="<?= $js['id_jenis']; ?>" <?= $id_jenis_filter == $js['id_jenis'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($js['nama_surat']); ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit" class="btn btn-detail">Filter</button>

        <a href="adm_permohonan.php" class="btn btn-delete">Reset</a>
    </form>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NPM</th>
                <th>Nama Mahasiswa</th>
                <th>Program Studi</th>
                <th>Jenis Surat</th>
                <th>Tanggal Diajukan</th>
                <th>Status</th>
                <th width="180">Aksi</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($query)) { ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['npm']); ?></td>
                        <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                        <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                        <td>
                            <span class="badge-warning">
                                <?= htmlspecialchars($row['status_akhir']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="adm_permohonan.php?detail=<?= $row['id_surat']; ?>"
                                class="btn btn-detail">
                                Review
                            </a>

                            <a href="#" class="btn btn-delete"
                                onclick="hapusData(<?= $row['id_surat']; ?>)">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="8" style="text-align:center;">
                        Data permohonan yang menunggu admin tidak ditemukan.
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</main>
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

    function hapusData(id) {
        if (confirm('Yakin ingin menghapus permohonan ini?')) {
            window.location.href = 'adm_permohonan.php?hapus=' + id;
        }
    }
</script>

</body>

</html>