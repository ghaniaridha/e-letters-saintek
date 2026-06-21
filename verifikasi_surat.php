<?php
include "koneksi.php";

$hash = $_GET['hash'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    WHERE sp.dokumen_hash = '$hash'
"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi TTD Mahasiswa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            padding: 40px;
        }

        .card {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .valid {
            color: green;
            font-size: 24px;
            font-weight: bold;
        }

        .invalid {
            color: red;
            font-size: 22px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="card">
        <?php if ($data) { ?>
            <p class="valid">TERVERIFIKASI</p>
            <p><strong>Nama :</strong> <?= htmlspecialchars($data['nama_mhs']); ?></p>
            <p><strong>NPM :</strong> <?= htmlspecialchars($data['npm']); ?></p>
            <p><strong>Perihal :</strong> Verifikasi TTD Digital Mahasiswa</p>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
            <p style="font-size: 0.9rem; color: #555;">
                <strong>Tanggal & Waktu Verifikasi:</strong><br>
                <?php
                $tanggal = date('d F Y, H:i:s', strtotime($data['tanggal_pengajuan']));
                echo $tanggal . " WIB";
                ?>
            </p>

        <?php } else { ?>
            <p class="invalid">Data verifikasi tidak ditemukan.</p>
        <?php } ?>
    </div>

</body>

</html>