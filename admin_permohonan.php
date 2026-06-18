<?php
include "admin_header.php";

/** @var mysqli $koneksi */

$prodi = $_GET['prodi'] ?? '';
$notifHapus = false;

/* HAPUS DATA */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    mysqli_query($koneksi, "
        DELETE FROM surat_pengajuan
        WHERE id_surat = $id
    ");

    $notifHapus = true;
}

/* FILTER PRODI */
$where = "";
if ($prodi != "") {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where = "WHERE m.prodi = '$prodiAman'";
}

/* AMBIL DATA */
$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Permohonan Surat</title>

    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">

    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">

        <div class="page-title">
            <h1>Kelola Permohonan Surat</h1>
            <p>Data seluruh permohonan surat mahasiswa berdasarkan program studi.</p>
        </div>

        <div class="filter-container">
            <form method="GET">
                <select name="prodi">
                    <option value="">Semua Prodi</option>
                    <option value="Sistem Informasi" <?= $prodi == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                    <option value="Kimia" <?= $prodi == 'Kimia' ? 'selected' : ''; ?>>Kimia</option>
                    <option value="Biologi" <?= $prodi == 'Biologi' ? 'selected' : ''; ?>>Biologi</option>
                    <option value="Sains Data" <?= $prodi == 'Sains Data' ? 'selected' : ''; ?>>Sains Data</option>
                </select>

                <button type="submit" class="btn btn-detail">
                    Filter
                </button>

                <a href="admin_permohonan.php" class="btn btn-delete">
                    Reset
                </a>
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
                        <th>Tanggal Surat Diajukan</th>
                        <th>Status</th>
                        <th width="160">Aksi</th>
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
                                    <?php if ($row['status_akhir'] == 'Selesai') { ?>
                                        <span class="badge-success">Selesai</span>
                                    <?php } elseif ($row['status_akhir'] == 'Ditolak') { ?>
                                        <span class="badge-danger">Ditolak</span>
                                    <?php } else { ?>
                                        <span class="badge-warning"><?= htmlspecialchars($row['status_akhir']); ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="admin_detail_permohonan.php?id=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                        Detail
                                    </a>

                                    <a href="#" class="btn btn-delete" onclick="hapusData(<?= $row['id_surat']; ?>)">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">
                                Data permohonan tidak ditemukan.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($notifHapus) { ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Permohonan berhasil dihapus',
        timer: 1500,
        showConfirmButton: false
    }).then(() => {
        window.location = 'admin_permohonan.php';
    });
});
</script>
<?php } ?>

<script>
function hapusData(id) {
    Swal.fire({
        title: 'Hapus Permohonan?',
        text: 'Data yang dihapus tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'admin_permohonan.php?hapus=' + id;
        }
    });
}
</script>

</body>
</html>