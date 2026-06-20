<?php
include "admin_header.php";

/** @var mysqli $koneksi */

$prodi = $_GET['prodi'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id = $_GET['detail'] ?? '';

/* PROSES ADMIN LANJUTKAN / TOLAK */
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi = $_POST['aksi_admin'];

    $dataSurat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT sp.*, js.nama_surat
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    "));

    if ($dataSurat) {
        if ($aksi == 'lanjut') {
            $namaSurat = strtolower($dataSurat['nama_surat']);

            if (strpos($namaSurat, 'riset') !== false) {
                $statusBaru = 'Menunggu Wadek 1';
            } elseif (strpos($namaSurat, 'aktif') !== false) {
                $statusBaru = 'Menunggu Wadek 1';
            } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                $statusBaru = 'Menunggu Dekan';
            } else {
                $statusBaru = 'Menunggu Dekan';
            }

            mysqli_query($koneksi, "
                UPDATE surat_pengajuan
                SET status_akhir = '$statusBaru'
                WHERE id_surat = '$id_surat'
            ");

            echo "<script>alert('Surat berhasil diteruskan ke $statusBaru'); window.location='admin_permohonan.php';</script>";
            exit;
        }

        if ($aksi == 'tolak') {
            mysqli_query($koneksi, "
                UPDATE surat_pengajuan
                SET status_akhir = 'Ditolak Admin'
                WHERE id_surat = '$id_surat'
            ");

            echo "<script>alert('Surat berhasil ditolak'); window.location='admin_permohonan.php';</script>";
            exit;
        }
    }
}

/* HAPUS DATA */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    mysqli_query($koneksi, "
        DELETE FROM surat_pengajuan
        WHERE id_surat = $id
    ");

    echo "<script>alert('Permohonan berhasil dihapus'); window.location='admin_permohonan.php';</script>";
    exit;
}

/* FILTER */
$where = "WHERE sp.status_akhir = 'Menunggu Admin'";

if ($prodi != "") {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.prodi = '$prodiAman'";
}

if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

/* DATA JENIS SURAT UNTUK FILTER */
$jenisSurat = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    ORDER BY nama_surat ASC
");

/* DATA TABEL */
$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.id_jenis,
        m.npm,
        m.nama_mhs,
        m.prodi,
        js.nama_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where
    ORDER BY sp.tanggal_pengajuan DESC
");

/* DETAIL REVIEW */
$detail = null;
if ($detail_id != "") {
    $detail_id = (int) $detail_id;

    $detail = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT 
            sp.*,
            m.npm,
            m.nama_mhs,
            m.prodi,
            js.nama_surat
        FROM surat_pengajuan sp
        JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$detail_id'
    "));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Permohonan Surat</title>

    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .review-box {
            background: #fff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(0,0,0,.08);
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
            background: rgba(0,0,0,.65);
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

    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">

        <div class="page-title">
            <h1>Kelola Permohonan Surat</h1>
            <p>Permohonan yang sudah disetujui dosen dan menunggu verifikasi admin.</p>
        </div>

        <?php if ($detail) { ?>
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
                        <td><?= htmlspecialchars($detail['prodi']); ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Surat</th>
                        <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                    </tr>
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
                    <tr>
                        <th>Status</th>
                        <td><?= htmlspecialchars($detail['status_akhir']); ?></td>
                    </tr>
                </table>

                <div class="review-actions">

    <?php
    if ($detail['id_jenis'] == 4) {
        $filePreview = "preview_magang.php?id=" . $detail['id_surat'];
    } else {
        $filePreview = "preview_surat.php?id=" . $detail['id_surat'];
    }
    ?>

    <a href="#" class="btn btn-detail"
       onclick="bukaPreview('<?= $filePreview; ?>')">
        Review Surat
    </a>

                    <?php if (!empty($detail['proposal_penelitian'])) { ?>
                        <a href="#" class="btn btn-edit"
                           onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($detail['proposal_penelitian']); ?>')">
                            Proposal
                        </a>
                    <?php } ?>

                    <?php if (!empty($detail['khs'])) { ?>
                        <a href="#" class="btn btn-edit"
                           onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($detail['khs']); ?>')">
                            KHS
                        </a>
                    <?php } ?>

                    <?php if (!empty($detail['bukti_ukt'])) { ?>
                        <a href="#" class="btn btn-edit"
                           onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($detail['bukti_ukt']); ?>')">
                            Bukti UKT
                        </a>
                    <?php } ?>
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

                    <a href="admin_permohonan.php" class="btn btn-detail">
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

                <a href="admin_permohonan.php" class="btn btn-delete">Reset</a>
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
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['npm']); ?></td>
                                <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                <td><?= htmlspecialchars($row['prodi']); ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td>
                                    <span class="badge-warning">
                                        <?= htmlspecialchars($row['status_akhir']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="admin_permohonan.php?detail=<?= $row['id_surat']; ?>"
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
        window.location.href = 'admin_permohonan.php?hapus=' + id;
    }
}
</script>

</body>
</html>