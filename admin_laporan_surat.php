<?php
include "admin_header.php";

/** @var mysqli $koneksi */

$jenis = $_GET['jenis'] ?? '';
$prodi = $_GET['prodi'] ?? '';
$periode = $_GET['periode'] ?? '';

$where = "WHERE sp.status_akhir = 'Selesai'";

if ($jenis != '') {
    $jenisAman = mysqli_real_escape_string($koneksi, $jenis);
    $where .= " AND sp.id_jenis = '$jenisAman'";
}

if ($prodi != '') {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.prodi = '$prodiAman'";
}

if ($periode != '') {
    $tahun = date('Y', strtotime($periode));
    $bulan = date('m', strtotime($periode));

    $where .= "
        AND YEAR(sp.tanggal_pengajuan) = '$tahun'
        AND MONTH(sp.tanggal_pengajuan) = '$bulan'
    ";
}

$jenisSurat = mysqli_query($koneksi, "
    SELECT * FROM jenis_surat
    ORDER BY nama_surat ASC
");

$query = mysqli_query($koneksi, "
    SELECT
        sp.id_surat,
        sp.nomor_surat,
        sp.tanggal_pengajuan,
        sp.status_akhir,
        sp.file_surat_final,
        m.npm,
        m.nama_mhs,
        m.prodi,
        js.nama_surat
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
    <title>Laporan Surat Keluar</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <div class="page-title">
            <h1>Laporan Surat Keluar</h1>
            <p>Filter laporan berdasarkan jenis surat, prodi, dan periode bulan.</p>
        </div>

        <div class="filter-container">
            <form method="GET">
                <select name="jenis">
                    <option value="">Semua Jenis Surat</option>
                    <?php while ($j = mysqli_fetch_assoc($jenisSurat)) { ?>
                        <option value="<?= $j['id_jenis']; ?>" <?= $jenis == $j['id_jenis'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($j['nama_surat']); ?>
                        </option>
                    <?php } ?>
                </select>

                <select name="prodi">
                    <option value="">Semua Prodi</option>
                    <option value="Sistem Informasi" <?= $prodi == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                    <option value="Kimia" <?= $prodi == 'Kimia' ? 'selected' : ''; ?>>Kimia</option>
                    <option value="Biologi" <?= $prodi == 'Biologi' ? 'selected' : ''; ?>>Biologi</option>
                    <option value="Sains Data" <?= $prodi == 'Sains Data' ? 'selected' : ''; ?>>Sains Data</option>
                </select>

                <input type="month" name="periode" value="<?= htmlspecialchars($periode); ?>">

                <button type="submit" class="btn btn-detail">
                    Filter
                </button>

                <a href="admin_laporan_surat.php" class="btn btn-delete">
                    Reset
                </a>
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Surat</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Prodi</th>
                        <th>Jenis Surat</th>
                        <th>Tanggal</th>
                        <th>File</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nomor_surat']); ?></td>
                                <td><?= htmlspecialchars($row['npm']); ?></td>
                                <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                <td><?= htmlspecialchars($row['prodi']); ?></td>
                                <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td>
                                    <?php if (!empty($row['file_surat_final'])) { ?>
                                        <a href="uploads/surat_final/<?= htmlspecialchars($row['file_surat_final']); ?>" target="_blank" class="btn btn-detail">
                                            Lihat
                                        </a>
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">
                                Data surat keluar tidak ditemukan.
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