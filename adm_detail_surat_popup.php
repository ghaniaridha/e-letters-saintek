<?php
session_start();
include "koneksi.php";

if (!isset($_GET['id'])) {
    die("ID surat tidak ditemukan.");
}

$id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);

$query = mysqli_query($koneksi,"
    SELECT
        sp.*,
        js.nama_surat,
        m.nama_mhs,
        m.npm,
        o.nama_ormawa,
        dpr.nama_kegiatan,
        dpr.ruangan_yang_diajukan,
        dpr.tanggal_mulai,
        dpr.tanggal_selesai,
        dpr.catatan,
        dpd.tema_kegiatan,
        dpd.tempat_kegiatan,
        dpd.tanggal_kegiatan,
        dpd.nominal_pengajuan,
        dpd.deskripsi_kegiatan
    FROM surat_pengajuan sp
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    LEFT JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    WHERE sp.id_surat='$id_surat'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data surat tidak ditemukan.");
}

$isDana = !empty($data['nominal_pengajuan']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detail Surat</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            padding:30px;
        }

        h2{
            margin-top:0;
            color:#1e293b;
        }

        table{
            width:100%;
            border-collapse: collapse;
        }

        td{
            padding:10px;
            border-bottom:1px solid #ddd;
            vertical-align: top;
        }

        td:first-child{
            width:220px;
            font-weight:bold;
        }
    </style>
</head>
<body>

<h2>Detail Surat</h2>

<table>
    <tr>
        <td>ID Surat</td>
        <td><?= $data['id_surat'] ?></td>
    </tr>

    <tr>
        <td>Jenis Surat</td>
        <td><?= $data['nama_surat'] ?></td>
    </tr>

    <tr>
        <td>Pengirim</td>
        <td>
            <?= !empty($data['nama_mhs'])
                ? $data['nama_mhs']." (".$data['npm'].")"
                : $data['nama_ormawa']; ?>
        </td>
    </tr>

    <tr>
        <td>Tanggal Pengajuan</td>
        <td><?= $data['tanggal_pengajuan'] ?></td>
    </tr>

    <tr>
        <td>Status Akhir</td>
        <td><?= $data['status_akhir'] ?></td>
    </tr>

<?php if($isDana){ ?>

    <tr>
        <td>Nama Kegiatan</td>
        <td><?= $data['nama_kegiatan'] ?></td>
    </tr>

    <tr>
        <td>Tema Kegiatan</td>
        <td><?= $data['tema_kegiatan'] ?></td>
    </tr>

    <tr>
        <td>Tanggal Kegiatan</td>
        <td><?= $data['tanggal_kegiatan'] ?></td>
    </tr>

    <tr>
        <td>Tempat Kegiatan</td>
        <td><?= $data['tempat_kegiatan'] ?></td>
    </tr>

    <tr>
        <td>Nominal Pengajuan</td>
        <td>Rp <?= number_format($data['nominal_pengajuan'],0,',','.') ?></td>
    </tr>

    <tr>
        <td>Deskripsi</td>
        <td><?= nl2br($data['deskripsi_kegiatan']) ?></td>
    </tr>

<?php } else { ?>

    <tr>
        <td>Nama Kegiatan</td>
        <td><?= $data['nama_kegiatan'] ?></td>
    </tr>

    <tr>
        <td>Ruangan</td>
        <td><?= $data['ruangan_yang_diajukan'] ?></td>
    </tr>

    <tr>
        <td>Tanggal Mulai</td>
        <td><?= $data['tanggal_mulai'] ?></td>
    </tr>

    <tr>
        <td>Tanggal Selesai</td>
        <td><?= $data['tanggal_selesai'] ?></td>
    </tr>

    <tr>
        <td>Catatan</td>
        <td><?= $data['catatan'] ?: '-' ?></td>
    </tr>

<?php } ?>

</table>

</body>
</html>