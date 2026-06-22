<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];
$id_surat = $_GET['id'];

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        m.prodi,
        js.nama_surat,
        d1.nama_dosen AS nama_dospem1,
        d1.nip AS nip_dospem1,
        d2.nama_dosen AS nama_dospem2,
        d2.nip AS nip_dospem2
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN dosen d1 ON sp.pembimbing_1 = d1.id_dosen
    LEFT JOIN dosen d2 ON sp.pembimbing_2 = d2.id_dosen
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='dosen_permohonan.php';</script>";
    exit;
}

$boleh_verifikasi = false;

if ($data['pembimbing_1'] == $id_dosen && $data['status_dospem1'] == 'Menunggu') {
    $boleh_verifikasi = true;
}

if ($data['pembimbing_2'] == $id_dosen && $data['status_dospem1'] == 'Disetujui' && $data['status_dospem2'] == 'Menunggu') {
    $boleh_verifikasi = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Permohonan</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
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
                    <td><?= htmlspecialchars($data['prodi']); ?></td>
                </tr>
                <tr>
                    <th>Judul Skripsi</th>
                    <td><?= htmlspecialchars($data['judul_skripsi']); ?></td>
                </tr>
                <tr>
                    <th>Lokasi Penelitian</th>
                    <td><?= htmlspecialchars($data['lokasi_penelitian']); ?></td>
                </tr>
                <tr>
                    <th>Surat Ditujukan Kepada</th>
                    <td><?= htmlspecialchars($data['surat_ditujukan']); ?></td>
                </tr>
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

                <a href="#" class="btn btn-detail"
                   onclick="bukaPreview('preview_surat.php?id=<?= $data['id_surat']; ?>')">
                    Preview Surat
                </a>

<?php if (!empty($data['proposal_penelitian'])) { ?>
    <a href="#" class="btn btn-edit"
       onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($data['proposal_penelitian']); ?>')">
        Proposal
    </a>
<?php } else { ?>
    <span class="btn btn-delete">Proposal Belum Ada</span>
<?php } ?>

<?php if (!empty($data['khs'])) { ?>
    <a href="#" class="btn btn-edit"
       onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($data['khs']); ?>')">
        KHS
    </a>
<?php } else { ?>
    <span class="btn btn-delete">KHS Belum Ada</span>
<?php } ?>

<?php if (!empty($data['bukti_ukt'])) { ?>
    <a href="#" class="btn btn-edit"
       onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($data['bukti_ukt']); ?>')">
        Bukti UKT
    </a>
<?php } else { ?>
    <span class="btn btn-delete">Bukti UKT Belum Ada</span>
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