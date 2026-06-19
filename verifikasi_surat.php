<?php
include "koneksi.php";

$hash = $_GET['hash'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm
    FROM surat_pengajuan sp
    JOIN mahasiswa m
        ON sp.id_mhs = m.id_mhs
    WHERE sp.dokumen_hash = '$hash'
"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Verifikasi Tanda Tangan</title>

<style>
body{
    background:#f5f7fb;
    font-family:Arial,sans-serif;
    padding:40px;
}

.card{
    max-width:600px;
    margin:auto;
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
    text-align:center;
}

.valid{
    color:#16a34a;
    font-size:22px;
    font-weight:bold;
}

.icon{
    font-size:70px;
    color:#16a34a;
}

.data{
    text-align:left;
    margin-top:25px;
    line-height:2;
}
</style>

</head>
<body>

<div class="card">

<?php if($data){ ?>

    <div class="icon">✓</div>

    <h2 class="valid">
        TERVERIFIKASI
    </h2>

    <hr>

    <div class="data">

        <p>
            <strong>Nama :</strong>
            <?= htmlspecialchars($data['nama_mhs']); ?>
        </p>

        <p>
            <strong>NPM :</strong>
            <?= htmlspecialchars($data['npm']); ?>
        </p>

        <p>
            <strong>Perihal :</strong>
            Verifikasi TTD Digital Mahasiswa
        </p>

        <p>
            <strong>Status :</strong>
            Valid
        </p>

    </div>

<?php } else { ?>

    <h2 style="color:red">
        QR Tidak Valid
    </h2>

<?php } ?>

</div>

</body>
</html>