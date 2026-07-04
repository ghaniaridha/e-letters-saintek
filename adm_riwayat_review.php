<?php
session_start();
include "koneksi.php";

if (!isset($koneksi)) {
    include "koneksi.php";
}

$query = mysqli_query($koneksi, "
    SELECT 
        sp.id_surat,
        sp.file_surat_final,
        m.npm,
        m.nama_mhs,
        p.nama_prodi,
        js.nama_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE sp.status_akhir != 'Menunggu Admin'
    AND (
        sp.status_akhir LIKE '%Wadek%'
        OR sp.status_akhir LIKE '%Dekan%'
        OR sp.status_akhir LIKE '%Ditolak Admin%'
        OR sp.status_akhir = 'Selesai'
        OR sp.status_akhir = 'Menunggu Surat Balasan'
    )
    ORDER BY sp.tanggal_pengajuan DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Review</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <div class="adm-wrapper">
        <?php include "adm_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Riwayat Review Admin</h1>
                <p>Daftar surat yang sudah direview admin beserta arsip surat balasan fakultas.</p>
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
                            <th>Surat Balasan Fakultas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <?php
                                $namaSurat = strtolower($row['nama_surat']);

                                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                    $linkBalasan = "generate_balasan_magang.php?id=" . $row['id_surat'];
                                } else {
                                    $linkBalasan = "generate_balasan_fakultas.php?id=" . $row['id_surat'];
                                }
                                ?>

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
                                        <?php if (!empty($row['file_surat_final'])) { ?>
                                            <a href="<?= $linkBalasan; ?>"
                                                target="_blank"
                                                class="btn btn-detail">
                                                Lihat Surat
                                            </a>
                                        <?php } else { ?>
                                            <span style="color:#94a3b8; font-style:italic;">
                                                Belum tersedia
                                            </span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <a href="adm_permohonan.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="9" style="text-align:center;">
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