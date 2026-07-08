<?php
session_start();
include "koneksi.php";

$id = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        dsr.semester,
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.surat_ditujukan,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        d1.nama_dosen AS nama_dospem1,
        d1.nip AS nip_dospem1,
        d2.nama_dosen AS nama_dospem2,
        d2.nip AS nip_dospem2
    FROM surat_pengajuan sp
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN dosen d1 ON dsr.id_pb1 = d1.id_dosen
    LEFT JOIN dosen d2 ON dsr.id_pb2 = d2.id_dosen
    WHERE sp.id_surat = '$id'
"));

if (!$data) {
    echo "Data surat tidak ditemukan.";
    exit;
}

$semester = $data['semester'] ?? '-';

$base_url = "http://192.168.1.4/e-letters-saintek";

// QR Code Mahasiswa (Validasi Dokumen)
$qr_mhs = "";
if (!empty($data['dokumen_hash'])) {
    $link_mhs = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];
    $qr_mhs = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_mhs);
}

// KUNCI PERBAIKAN QR CODE DOSEN:
// Menggunakan pengecekan 'status_dospem' == 'Disetujui' karena kolom ttd sudah dihapus
$qr_dospem1 = "";
if (isset($data['status_dospem1']) && $data['status_dospem1'] == 'Disetujui') {
    // Generate hash dinamis untuk verifikasi TTD jika disetujui
    $hash_ttd1 = hash('md5', $id . $data['nip_dospem1'] . 'ttd1');
    $link_dospem1 = $base_url . "/verifikasi_ttd.php?hash=" . $hash_ttd1;
    $qr_dospem1 = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_dospem1);
}

$qr_dospem2 = "";
if (isset($data['status_dospem2']) && $data['status_dospem2'] == 'Disetujui') {
    $hash_ttd2 = hash('md5', $id . $data['nip_dospem2'] . 'ttd2');
    $link_dospem2 = $base_url . "/verifikasi_ttd.php?hash=" . $hash_ttd2;
    $qr_dospem2 = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_dospem2);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Preview Surat Permohonan Riset</title>

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
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 30px;
        }

        .ttd div {
            width: 30%;
        }

        .qr-img {
            width: 85px;
            height: 85px;
            object-fit: contain;
            margin-bottom: 5px;
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
        <p>Perihal : Permohonan Surat Rekomendasi Riset</p>

        <br>

        <p>Kepada Yth,</p>
        <p><b>Dekan Fakultas Sains dan Teknologi</b></p>
        <p><b>UIN Raden Intan Lampung</b></p>
        <p>di-</p>
        <p style="margin-left:40px;">Bandar Lampung</p>

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
                <td>
                    <?= htmlspecialchars($data['semester']); ?> /
                    <?= htmlspecialchars($data['nama_prodi']); ?>
                </td>
            </tr>
            <tr>
                <td>Judul Skripsi</td>
                <td>:</td>
                <td><?= htmlspecialchars($data['judul_skripsi']); ?></td>
            </tr>
            <tr>
                <td>Lokasi Penelitian</td>
                <td>:</td>
                <td><?= htmlspecialchars($data['lokasi_penelitian']); ?></td>
            </tr>
            <tr>
                <td>Surat Ditujukan Kepada</td>
                <td>:</td>
                <td><?= htmlspecialchars($data['surat_ditujukan']); ?></td>
            </tr>
        </table>

        <br>

        <p>
            Bermaksud memohon surat Rekomendasi Riset dari pihak Fakultas,
            sebagai bahan pertimbangan Bapak, saya lampirkan:
        </p>

        <ol>
            <li>Proposal Penelitian</li>
            <li>Foto Copy Slip pembayaran UKT Terakhir</li>
            <li>KHS Semester terakhir</li>
        </ol>

        <p>Atas perhatian Bapak, saya ucapkan terima kasih</p>
        <p>Wassalamu’alaikum Wr. Wb.</p>

        <br>

        <p style="text-align:right;">Bandar Lampung, <?= date('d-m-Y'); ?></p>

        <div class="ttd">
            <div>
                <p>Mengetahui,</p>
                <p>Pembimbing I</p>

                <?php if (!empty($qr_dospem1)) { ?>
                    <img src="<?= $qr_dospem1; ?>" class="qr-img" alt="QR Dospem 1">
                <?php } else { ?>
                    <br><br><br>
                <?php } ?>

                <p><b><?= htmlspecialchars($data['nama_dospem1']); ?></b></p>
                <p>NIP. <?= htmlspecialchars($data['nip_dospem1']); ?></p>
            </div>

            <div>
                <p>&nbsp;</p>
                <p>Pembimbing II</p>

                <?php if (!empty($qr_dospem2)) { ?>
                    <img src="<?= $qr_dospem2; ?>" class="qr-img" alt="QR Dospem 2">
                <?php } else { ?>
                    <br><br><br>
                <?php } ?>

                <p><b><?= htmlspecialchars($data['nama_dospem2']); ?></b></p>
                <p>NIP. <?= htmlspecialchars($data['nip_dospem2']); ?></p>
            </div>

            <div>
                <p>&nbsp;</p>
                <p>Pemohon</p>

                <?php if (!empty($qr_mhs)) { ?>
                    <img src="<?= $qr_mhs; ?>" class="qr-img" alt="QR Mahasiswa">
                <?php } else { ?>
                    <br><br><br>
                <?php } ?>

                <p><b><?= htmlspecialchars($data['nama_mhs']); ?></b></p>
                <p><?= htmlspecialchars($data['npm']); ?></p>
            </div>
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