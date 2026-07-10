<?php
session_start();
include "koneksi.php";

function formatTanggalIndo($tanggal_db)
{
    $nama_bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal_db);
    $hari = date('d', $timestamp);
    $bulan = $nama_bulan[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    return "$hari $bulan $tahun, $jam";
}

$hash = mysqli_real_escape_string($koneksi, $_GET['hash']);

$query_surat = "
    SELECT 
        sp.*, 
        m.nama_mhs, m.npm, m.id_pb1, m.id_pb2, m.id_pa,
        js.nama_surat,
        p.nama_prodi,
        dsr.ttd_pb1, dsr.ttd_pb2,
        dak.ttd_pa
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    WHERE dsr.ttd_pb1 = '$hash' 
       OR dsr.ttd_pb2 = '$hash' 
       OR dak.ttd_pa = '$hash'
";

$result_surat = mysqli_query($koneksi, $query_surat);
$data_dosen = null;

if (mysqli_num_rows($result_surat) > 0) {
    $row = mysqli_fetch_assoc($result_surat);

    $id_dosen_penandatangan = null;
    $peran_dosen = "";

    if ($row['ttd_pb1'] === $hash) {
        $id_dosen_penandatangan = $row['id_pb1'];
        $peran_dosen = "Pembimbing 1";
    } elseif ($row['ttd_pb2'] === $hash) {
        $id_dosen_penandatangan = $row['id_pb2'];
        $peran_dosen = "Pembimbing 2";
    } elseif ($row['ttd_pa'] === $hash) {
        $id_dosen_penandatangan = $row['id_pa'];
        $peran_dosen = "Pembimbing Akademik";
    }

    if ($id_dosen_penandatangan) {
        $query_dosen = mysqli_query($koneksi, "SELECT nama_dosen, nip FROM dosen WHERE id_dosen = '$id_dosen_penandatangan'");

        if (mysqli_num_rows($query_dosen) > 0) {
            $dosen = mysqli_fetch_assoc($query_dosen);

            $data_dosen = [
                'nama_dosen'        => $dosen['nama_dosen'],
                'nip'               => $dosen['nip'],
                'peran_dosen'       => $peran_dosen,
                'nama_surat'        => $row['nama_surat'],
                'nama_mhs'          => $row['nama_mhs'],
                'npm'               => $row['npm'],
                'ttd_hash'          => $hash,
                'waktu_disetujui'   => date('Y-m-d H:i:s'),
                'tanggal_pengajuan' => $row['tanggal_pengajuan']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi QR Code</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="verif.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <div class="verifikasi-card">
        <?php if ($data_dosen) { ?>
            <div class="status-box valid">
                <i class="fa-solid fa-circle-check"></i>
                <h2>TERVERIFIKASI</h2>
                <p class="status-message">Dokumen ini telah diverifikasi dan disetujui secara elektronik melalui SIPATU FST UIN RIL</p>
            </div>

            <div class="info-group">
                <h4>Informasi Penandatangan</h4>
                <p><strong>Nama :</strong> <?= htmlspecialchars($data_dosen['nama_dosen']); ?></p>
                <p><strong>NIP :</strong> <?= htmlspecialchars($data_dosen['nip']); ?></p>
                <p><strong>Kapasitas :</strong> <?= htmlspecialchars($data_dosen['peran_dosen']); ?> </p>
                <p><strong>Waktu Persetujuan :</strong> <?= formatTanggalIndo($data_dosen['tanggal_pengajuan']); ?> WIB</p>
            </div>

            <div class="info-group">
                <h4>Dokumen yang Disetujui</h4>
                <p><strong>Jenis Surat :</strong> <?= htmlspecialchars($data_dosen['nama_surat']); ?></p>
                <p><strong>Atas Nama :</strong> <?= htmlspecialchars($data_dosen['nama_mhs']); ?></p>
                <p><strong>NPM :</strong> <?= htmlspecialchars($data_dosen['npm']); ?></p>
                <p><strong>Kode Dokumen :</strong> <span class="hash-code"><?= substr($data_dosen['ttd_hash'], 0, 16); ?>...</span></p>
            </div>

            <div class="footer-meta">
                <strong>Waktu Pindai:</strong>
                <?php
                $bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo date('d') . ' ' . $bulan_indo[date('n') - 1] . ' ' . date('Y, H:i:s');
                ?> WIB
            </div>

        <?php } else { ?>
            <div class="status-box invalid">
                <i class="fa-solid fa-circle-xmark"></i>
                <h2>TANDA TANGAN TIDAK VALID</h2>
                <p>Data persetujuan tidak ditemukan. QR Code mungkin tidak sah atau telah kedaluwarsa.</p>
            </div>
        <?php } ?>
    </div>
</body>

</html>