<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$id_jenis = $_POST['id_jenis'];
$semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
$judul_skripsi = mysqli_real_escape_string($koneksi, $_POST['judul_skripsi']);
$lokasi_penelitian = mysqli_real_escape_string($koneksi, $_POST['lokasi_penelitian']);
$surat_ditujukan = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);
$pembimbing_1 = $_POST['pembimbing_1'];
$pembimbing_2 = !empty($_POST['pembimbing_2']) ? $_POST['pembimbing_2'] : 'NULL';

$folder_upload = __DIR__ . "/uploads/dokumen_hss/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
    chmod($folder_upload, 0777);
}

function uploadFile($field, $folder_upload, $allowed_ext, $allowed_mime)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] != 0) {
        echo "<script>alert('File " . $field . " wajib diupload'); history.back();</script>";
        exit;
    }

    $nama_asli = $_FILES[$field]['name'];
    $tmp_file = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_asli = finfo_file($finfo, $tmp_file);
    finfo_close($finfo);

    if (!in_array($ext, $allowed_ext) || !in_array($mime_asli, $allowed_mime)) {
        echo "<script>alert('Format file " . $field . " tidak valid atau file telah dimanipulasi!'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal upload file " . $field . "'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$ext_proposal = ['pdf'];
$mime_proposal = ['application/pdf'];
$proposal_penelitian = uploadFile('proposal_penelitian', $folder_upload, $ext_proposal, $mime_proposal);

$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];

$khs = uploadFile('khs', $folder_upload, $ext_umum, $mime_umum);
$bukti_ukt = uploadFile('bukti_ukt', $folder_upload, $ext_umum, $mime_umum);

$tanggal = date('Y-m-d H:i:s');

$query_utama = "
    INSERT INTO surat_pengajuan 
    (id_mhs, id_jenis, nomor_surat, file_surat_final, dokumen_hash, tanggal_pengajuan, status_akhir, status_pimpinan)
    VALUES 
    ('$id_mhs', '$id_jenis', '', '', '', '$tanggal', 'Menunggu Dospem 2', 'Menunggu')
";

if (mysqli_query($koneksi, $query_utama)) {
    $id_surat = mysqli_insert_id($koneksi);

    $dokumen_hash = hash('sha256', $id_surat . $id_mhs . time());
    mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

    $pembimbing_1_val = ($pembimbing_1 === 'NULL') ? "NULL" : "'$pembimbing_1'";

    $query_detail = "
    INSERT INTO detail_surat_riset 
    (id_surat, semester, judul_skripsi, lokasi_penelitian, surat_ditujukan, id_pb2, id_pb1, status_pb1, status_pb2)
    VALUES 
    ('$id_surat', '$semester', '$judul_skripsi', '$lokasi_penelitian', '$surat_ditujukan', '$pembimbing_2', $pembimbing_1_val, 'Menunggu', 'Menunggu')
    ";
    mysqli_query($koneksi, $query_detail);

    function getIdSyarat($koneksi, $nama_syarat)
    {
        $nama_syarat_clean = mysqli_real_escape_string($koneksi, $nama_syarat);
        $q = mysqli_query($koneksi, "SELECT id_syarat FROM master_syarat WHERE nama_syarat LIKE '%$nama_syarat_clean%' LIMIT 1");
        $row = mysqli_fetch_assoc($q);
        return $row['id_syarat'] ?? null;
    }

    $id_syarat_proposal = getIdSyarat($koneksi, 'Proposal Penelitian');
    $id_syarat_khs      = getIdSyarat($koneksi, 'KHS Semester Lalu');
    $id_syarat_ukt      = getIdSyarat($koneksi, 'Bukti Pembayaran UKT Terakhir');

    $lampiran_values = [];
    if ($id_syarat_proposal) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_proposal', '$proposal_penelitian')";
    }
    if ($id_syarat_khs) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_khs', '$khs')";
    }
    if ($id_syarat_ukt) {
        $lampiran_values[] = "('$id_surat', '$id_syarat_ukt', '$bukti_ukt')";
    }

    if (count($lampiran_values) > 0) {
        $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);
        mysqli_query($koneksi, $query_lampiran);
    }

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat permohonan izin riset berhasil dibuat dan diajukan.';
    header("Location: preview_surat_riset_mhs.php?id=$id_surat");
    exit;
} else {
    $_SESSION['status'] = 'error';
    $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat.';
    $halaman_sebelumnya = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'mhs_beranda.php';
    header("Location: " . $halaman_sebelumnya);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body>

</body>

</html>