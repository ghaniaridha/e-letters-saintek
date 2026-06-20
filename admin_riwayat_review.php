<?php
include "admin_header.php";

/** @var mysqli $koneksi */

if (!isset($koneksi)) {
    include "koneksi.php";
}

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
    WHERE sp.status_akhir != 'Menunggu Admin'
    AND (
        sp.status_akhir LIKE '%Wadek%'
        OR sp.status_akhir LIKE '%Dekan%'
        OR sp.status_akhir LIKE '%Ditolak Admin%'
        OR sp.status_akhir = 'Selesai'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Review Admin</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
</head>
<body>

<div class="admin-wrapper">
    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <div class="page-title">
            <h1>Riwayat Review Admin</h1>
            <p>Daftar surat yang sudah direview dan diteruskan oleh admin.</p>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Prodi</th>
                        <th>Jenis Surat</th>
                        <th>Tanggal</th>
                        <th>Status Akhir</th>
                        <th>Aksi</th>
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
                                <a href="admin_permohonan.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            Belum ada riwayat review admin.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>