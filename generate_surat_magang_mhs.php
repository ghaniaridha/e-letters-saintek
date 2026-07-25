<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];

$id_jenis               = $_POST['id_jenis'];
$semester               = mysqli_real_escape_string($koneksi, $_POST['semester']);
$tanggal_mulai_magang   = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_magang']);
$tanggal_selesai_magang = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_magang']);
$lokasi_magang          = mysqli_real_escape_string($koneksi, $_POST['lokasi_magang']);
$surat_ditujukan        = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);

$folder_upload = "uploads/dokumen_hss/";
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
        echo "<script>alert('Format file " . $field . " tidak valid atau dimanipulasi!'); history.back();</script>";
        exit;
    }

    $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
        echo "<script>alert('Gagal upload file " . $field . "'); history.back();</script>";
        exit;
    }

    return $nama_baru;
}

$ext_umum = ['pdf', 'jpg', 'jpeg', 'png'];
$mime_umum = ['application/pdf', 'image/jpeg', 'image/png'];

$file_ktm      = uploadFile('ktm', $folder_upload, $ext_umum, $mime_umum);
$file_bukti_ukt = uploadFile('bukti_ukt', $folder_upload, $ext_umum, $mime_umum);
$file_khs      = uploadFile('khs', $folder_upload, $ext_umum, $mime_umum);

$tanggal_pengajuan = date('Y-m-d H:i:s');
$dokumen_hash      = hash('sha256', $id_mhs . $id_jenis . time());

$query_utama = "
    INSERT INTO surat_pengajuan 
    (id_mhs, id_jenis, nomor_surat, file_surat_final, tanggal_pengajuan, status_akhir, status_pimpinan, tujuan_admin, dokumen_hash)
    VALUES 
    ('$id_mhs', '$id_jenis', '', '', '$tanggal_pengajuan', 'Menunggu Admin', 'Menunggu', 'admin2', '$dokumen_hash')
";

if (mysqli_query($koneksi, $query_utama)) {

    $id_surat = mysqli_insert_id($koneksi);

    $query_detail = "
        INSERT INTO detail_surat_magang 
        (id_surat, semester, tanggal_mulai_magang, tanggal_selesai_magang, lokasi_magang, surat_ditujukan)
        VALUES 
        ('$id_surat', '$semester', '$tanggal_mulai_magang', '$tanggal_selesai_magang', '$lokasi_magang', '$surat_ditujukan')
    ";

    if (mysqli_query($koneksi, $query_detail)) {

        $lampiran_values = [];

        $lampiran_values[] = "('$id_surat', '4', '$file_ktm')";
        $lampiran_values[] = "('$id_surat', '2', '$file_bukti_ukt')";
        $lampiran_values[] = "('$id_surat', '3', '$file_khs')";

        if (count($lampiran_values) > 0) {
            $query_lampiran = "INSERT INTO lampiran_pengajuan (id_surat, id_syarat, file_upload) VALUES " . implode(", ", $lampiran_values);

            if (!mysqli_query($koneksi, $query_lampiran)) {
            }
        }

        $_SESSION['semester_magang_' . $id_surat] = $semester;

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat permohonan izin magang berhasil dibuat dan diajukan';
        header("Location: preview_surat_magang_mhs.php?id=$id_surat");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan']  = 'Sistem gagal memproses pengajuan surat.';
        $halaman_sebelumnya = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'mhs_beranda.php';
        header("Location: " . $halaman_sebelumnya);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genarate Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>

<body>

</body>

</html>