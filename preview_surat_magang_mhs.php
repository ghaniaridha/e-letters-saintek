<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,              /* Ambil nama prodi dari tabel prodi */
        dsm.semester,              /* Ambil semester dari tabel detail magang */
        dsm.lokasi_magang,         /* Ambil lokasi magang dari tabel detail magang */
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        dsm.surat_ditujukan
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi /* Hubungkan ke tabel prodi */
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat /* Hubungkan ke tabel detail magang */
    WHERE sp.id_surat = '$id'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='mhs_lacak.php';</script>";
    exit;
}

$semester = $data['semester'];

$base_url = "http://192.168.1.4/e-letters-saintek";
$link_verifikasi = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Preview Surat Magang</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <style>
        body {
            background: #f3f4f6;
            font-family: "Times New Roman", serif;
            padding: 30px;
        }

        .surat {
            background: white;
            max-width: 850px;
            margin: auto;
            padding: 60px;
            line-height: 1.7;
            color: black;
        }

        table {
            width: 100%;
        }

        td {
            vertical-align: top;
            padding: 4px;
        }

        .ttd {
            width: 260px;
            margin-left: auto;
            text-align: center;
            margin-top: 40px;
        }

        .qr-img {
            width: 85px;
            height: 85px;
        }

        .action-buttons-container {
            text-align: center;
            margin: 40px auto;
            padding-bottom: 50px;
            display: flex;
            justify-content: center;
            gap: 15px;
            /* Jarak antar tombol */
        }

        .btn {
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #5c636a;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .btn-success {
            background-color: #198754;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #157347;
        }

        /* KUNCI PENTING: Menyembunyikan tombol saat halaman di-print/cetak ke PDF */
        @media print {
            .action-buttons-container {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="surat">

        <p>Perihal : Permohonan Izin Magang</p>

        <br>

        <p>Kepada Yth,</p>
        <p><strong><?= htmlspecialchars($data['surat_ditujukan']); ?></strong></p>
        <p>di-</p>
        <p style="margin-left:40px;"><?= htmlspecialchars($data['lokasi_magang']); ?></p>

        <br>

        <p>Assalamu’alaikum wr. wb.</p>
        <p>Saya yang bertanda tangan dibawah ini :</p>

        <table>
            <tr>
                <td width="220">Nama / NPM</td>
                <td width="10">:</td>
                <td><?= htmlspecialchars($data['nama_mhs']); ?> / <?= htmlspecialchars($data['npm']); ?></td>
            </tr>
            <tr>
                <td>Semester / Program Studi</td>
                <td>:</td>
                <td><?= htmlspecialchars($semester); ?> / <?= htmlspecialchars($data['nama_prodi']); ?></td>
            </tr>
            <tr>
                <td>Lokasi Magang</td>
                <td>:</td>
                <td><?= htmlspecialchars($data['lokasi_magang']); ?></td>
            </tr>
        </table>

        <br>

        <p>
            Bermaksud memohon surat izin magang dari pihak Fakultas Sains dan Teknologi
            UIN Raden Intan Lampung untuk melaksanakan kegiatan magang pada instansi
            yang Bapak/Ibu pimpin.
        </p>

        <p>Sebagai bahan pertimbangan, saya lampirkan:</p>

        <ol>
            <li>Foto Copy KTM</li>
            <li>Foto Copy Slip Pembayaran UKT Terakhir</li>
            <li>KHS Semester Terakhir</li>
        </ol>

        <p>Demikian surat permohonan ini saya buat. Atas perhatian Bapak/Ibu, saya ucapkan terima kasih.</p>

        <p>Wassalamu’alaikum wr. wb.</p>

        <br>

        <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

        <div class="ttd">
            <p>Pemohon</p>

            <?php if (!empty($data['dokumen_hash'])) { ?>
                <img src="<?= $qr_url; ?>" class="qr-img" alt="QR Verifikasi">
            <?php } else { ?>
                <br><br><br>
            <?php } ?>

            <p><strong><?= htmlspecialchars($data['nama_mhs']); ?></strong></p>
            <p><?= htmlspecialchars($data['npm']); ?></p>
        </div>

    </div>
    <div class="action-buttons-container">
        <a href="mhs_daftar_surat_akademik.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Surat
        </a>

        <a href="mhs_lacak.php?id=<?= $id ?>" class="btn btn-primary">
            <i class="fa-solid fa-route"></i> Lacak Status Surat
        </a>
    </div>

</body>

</html>