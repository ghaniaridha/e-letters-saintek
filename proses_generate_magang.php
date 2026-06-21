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
$tanggal_mulai_magang = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai_magang']);
$tanggal_selesai_magang = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai_magang']);
$lokasi_magang = mysqli_real_escape_string($koneksi, $_POST['lokasi_magang']);
$surat_ditujukan = mysqli_real_escape_string($koneksi, $_POST['surat_ditujukan']);

$tanggal_pengajuan = date('Y-m-d H:i:s');
$dokumen_hash = hash('sha256', $id_mhs . $id_jenis . time());

mysqli_query($koneksi, "
    INSERT INTO surat_pengajuan
    (
        id_mhs,
        id_jenis,
        tanggal_pengajuan,
        status_akhir,
        semester,
        tanggal_mulai_magang,
        tanggal_selesai_magang,
        lokasi_magang,
        surat_ditujukan,
        dokumen_hash
    )
    VALUES
    (
        '$id_mhs',
        '$id_jenis',
        '$tanggal_pengajuan',
        'Menunggu Admin',
        '$semester',
        '$tanggal_mulai_magang',
        '$tanggal_selesai_magang',
        '$lokasi_magang',
        '$surat_ditujukan',
        '$dokumen_hash'
    )
");

$id_surat = mysqli_insert_id($koneksi);

$_SESSION['semester_magang_' . $id_surat] = $semester;

echo "<script>
    alert('Surat magang berhasil dibuat dan dikirim ke admin.');
    window.location='preview_magang.php?id=$id_surat';
</script>";
exit;
?>