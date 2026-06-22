<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];

$query = mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm,
        m.prodi,
        js.nama_surat
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE
    (
        sp.pembimbing_1 = '$id_dosen'
        AND sp.status_dospem1 != 'Menunggu'
    )
    OR
    (
        sp.pembimbing_2 = '$id_dosen'
        AND sp.status_dospem2 != 'Menunggu'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Verifikasi Dosen</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
</head>

<body class="dosen-page">

<div class="admin-wrapper">
    <main class="main-content">

        <div class="page-title">
            <h1>Riwayat Verifikasi</h1>
            <p>Daftar permohonan surat yang sudah Anda verifikasi.</p>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Mahasiswa</th>
                        <th>NPM</th>
                        <th>Jenis Surat</th>
                        <th>Status Anda</th>
                        <th>Status Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>

                        <?php
                        if ($row['pembimbing_1'] == $id_dosen) {
                            $statusAnda = $row['status_dospem1'];
                        } else {
                            $statusAnda = $row['status_dospem2'];
                        }
                        ?>

                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                            <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                            <td><?= htmlspecialchars($row['npm']); ?></td>
                            <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                            <td><?= htmlspecialchars($statusAnda); ?></td>
                            <td><?= htmlspecialchars($row['status_akhir']); ?></td>
                            <td>
                                <a href="dosen_detail_permohonan.php?id=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                    Detail
                                </a>
                            </td>
                        </tr>

                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            Belum ada riwayat verifikasi.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <br>
        <a href="dosen_beranda.php" class="btn btn-detail">Kembali</a>

    </main>
</div>

</body>
</html>