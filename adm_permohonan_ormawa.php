<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SESSION['role_admin'] !== 'admin1') {
    header("Location: index.php");
    exit;
}

$id_jenis_filter = $_GET['id_jenis'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    if ($aksi == "jadwal") {
        $tanggal = $_POST['tanggal_kegiatan'];
        $mulai   = $_POST['jam_mulai'];
        $selesai = $_POST['jam_selesai'];
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);

        mysqli_query($koneksi, "
            UPDATE detail_peminjaman_ruangan
            SET tanggal_mulai='$tanggal', tanggal_selesai='$tanggal', jam_mulai='$mulai', jam_selesai='$selesai', catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Selesai', status_keputusan='Disetujui', posisi_sekarang='Selesai'
            WHERE id_surat='$id_surat'
        ");
        header("Location: adm_permohonan_ormawa.php");
        exit;
    }

    if ($aksi == "tolak_ormawa") {
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);
        $cekJenis = mysqli_fetch_assoc(mysqli_query($koneksi, "
            SELECT js.nama_surat FROM surat_pengajuan sp
            JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'
        "));

        if (stripos($cekJenis['nama_surat'], 'dana') !== false) {
            mysqli_query($koneksi, "UPDATE detail_pengajuan_dana SET catatan='$catatan' WHERE id_surat='$id_surat'");
        } else {
            mysqli_query($koneksi, "UPDATE detail_peminjaman_ruangan SET catatan='$catatan' WHERE id_surat='$id_surat'");
        }

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Ditolak Admin', status_keputusan='Ditolak', posisi_sekarang='Selesai'
            WHERE id_surat='$id_surat'
        ");
        header("Location: adm_permohonan_ormawa.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = $id");
    echo "<script>alert('Permohonan berhasil dihapus'); window.location='adm_permohonan_ormawa.php';</script>";
    exit;
}

$where = "WHERE sp.status_akhir = 'Menunggu Admin' AND sp.tujuan_admin = 'admin1' AND sp.id_ormawa IS NOT NULL";

if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}

$jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat LIKE '%Dana%' OR nama_surat LIKE '%Ruangan%' ORDER BY nama_surat ASC");

$query = mysqli_query($koneksi, "
    SELECT sp.id_surat, o.nama_ormawa, js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where ORDER BY sp.tanggal_pengajuan DESC
");

$detail = null;

if ($detail_id != "") {
    $detail_id = (int) $detail_id;
    $query_detail = mysqli_query($koneksi, "
        SELECT 
            sp.*, o.nama_ormawa, js.nama_surat,
            dpr.nama_kegiatan, dpr.ruangan_yang_diajukan, dpr.tanggal_mulai, dpr.proposal, dpr.surat_permohonan
        FROM surat_pengajuan sp
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
        WHERE sp.id_surat = '$detail_id'
    ");
    $detail = mysqli_fetch_assoc($query_detail);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Surat Ormawa</title>
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
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


        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            /* Agar width 100% tidak keluar batas */
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-batal {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: #e0e0e0;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-simpan {
            flex: 2;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: #f1c40f;
            cursor: pointer;
            font-weight: bold;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            /* Sangat penting agar tidak melebar keluar modal */
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">
            <div class="page-title">
                <h1>Kelola Surat Pengajuan Ormawa</h1>
            </div>

            <?php if ($detail) { ?>
                <div class="review-box">
                    <h2>Review Permohonan Surat Ormawa</h2><br>
                    <table>
                        <tr>
                            <th>Nama Organisasi</th>
                            <td><?= htmlspecialchars($detail['nama_ormawa']); ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Surat</th>
                            <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                        </tr>
                        <tr>
                            <th>Nama Kegiatan</th>
                            <td><?= htmlspecialchars($detail['nama_kegiatan'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Ruangan Diajukan</th>
                            <td><?= htmlspecialchars($detail['ruangan_yang_diajukan'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Kegiatan</th>
                            <td><?= date('d-m-Y', strtotime($detail['tanggal_mulai'] ?? 'now')); ?></td>
                        </tr>
                        <tr>
                            <th>Status Saat Ini</th>
                            <td><?= htmlspecialchars($detail['status_akhir']); ?></td>
                        </tr>
                    </table>

                    <div class="review-actions" style="margin-top: 20px;">
                        <?php
                        if (stripos($detail['nama_surat'], 'dana') !== false) {
                            $filePreview = "preview_pengajuan_dana.php?id=" . $detail['id_surat'] . "&mode=view";
                        } else {
                            $filePreview = "preview_peminjaman_ruangan.php?id=" . $detail['id_surat'] . "&mode=view";
                        }
                        ?>
                        <a href="#" class="btn btn-detail" onclick="bukaPreview('<?= $filePreview; ?>')">Review Surat Hasil Sistem</a>
                        <?php if (!empty($detail['surat_permohonan'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/surat_permohonan/<?= htmlspecialchars($detail['surat_permohonan']); ?>')">Dokumen Pengajuan</a><?php } ?>
                        <?php if (!empty($detail['proposal'])) { ?><a href="#" class="btn btn-edit" onclick="bukaPreview('uploads/proposal/<?= htmlspecialchars($detail['proposal']); ?>')">Proposal Kegiatan</a><?php } ?>
                    </div>
                </div>

                <form method="POST" class="review-actions">
                    <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">
                    <button type="button" class="btn btn-edit" onclick="bukaModalJadwal()">Atur Jadwal</button>
                    <button type="button" class="btn btn-delete" onclick="bukaModalTolak()">Tolak</button>
                    <a href="adm_permohonan_ormawa.php" class="btn btn-detail">Kembali</a>
                </form>
            <?php } ?>

            <div class="table-card-table">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Organisasi</th>
                            <th>Jenis Surat</th>
                            <th>Tanggal Diajukan</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><span class="badge-warning"><?= htmlspecialchars($row['status_akhir']); ?></span></td>
                                    <td>
                                        <a href="adm_permohonan_ormawa.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">Review</a>
                                        <a href="#" class="btn btn-delete" onclick="hapusData(<?= $row['id_surat']; ?>)">Hapus</a>
                                    </td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL PREVIEW -->
    <div id="modalPreview" class="modal-preview">
        <div class="modal-content-preview">
            <span class="close-preview" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" width="100%" height="620px" style="border:none;"></iframe>
        </div>
    </div>

    <!-- MODAL ATUR JADWAL (ORMAWA) -->
    <div id="modalJadwal" class="modal-preview">
        <div class="modal-content-preview">
            <h3>Atur Jadwal Ruangan</h3>
            <form method="POST">
                <input type="hidden" name="id_surat" value="<?= $detail['id_surat'] ?? ''; ?>">
                <input type="hidden" name="aksi_admin" value="jadwal">

                <div class="form-group"><label>Tanggal Kegiatan</label><input type="date" name="tanggal_kegiatan" class="form-control" required></div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;"><label>Jam Mulai</label><input type="time" name="jam_mulai" class="form-control" required></div>
                    <div class="form-group" style="flex:1;"><label>Jam Selesai</label><input type="time" name="jam_selesai" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Catatan Admin</label><textarea name="catatan" class="form-control" rows="3"></textarea></div>
                <div class="modal-actions">
                    <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TOLAK ORMAWA -->
    <div id="modalTolak" class="modal-preview">
        <div class="modal-content-preview">
            <h3>Alasan Penolakan</h3>
            <form method="POST">
                <input type="hidden" name="id_surat" value="<?= $detail['id_surat'] ?? ''; ?>">
                <input type="hidden" name="aksi_admin" value="tolak_ormawa">
                <textarea name="catatan" rows="5" required placeholder="Masukkan alasan penolakan"></textarea><br><br>
                <div class="modal-actions">
                    <button type="button" class="btn-batal" onclick="tutupModal()">Batal</button>
                    <button type="submit" class="btn-simpan" onclick="return confirm('Yakin ingin menyimpan?')">Simpan Penolakan</button>
                </div>
            </form>
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

        function bukaModalJadwal() {
            document.getElementById("modalJadwal").style.display = "flex";
        }

        function bukaModalTolak() {
            document.getElementById("modalTolak").style.display = "flex";
        }

        function tutupModal() {
            document.getElementById("modalJadwal").style.display = "none";
            document.getElementById("modalTolak").style.display = "none";
        }

        function hapusData(id) {
            if (confirm('Yakin ingin menghapus?')) {
                window.location.href = 'adm_permohonan_ormawa.php?hapus=' + id;
            }
        }
    </script>
</body>

</html>