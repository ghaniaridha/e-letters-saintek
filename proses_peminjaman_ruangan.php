<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_ormawa'])) {
    echo "<script>
            alert('Silakan login terlebih dahulu');
            window.location='index.php';
          </script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ormawa_form_peminjaman_ruangan.php");
    exit;
}

$id_ormawa        = $_POST['id_ormawa'];
$id_jenis         = $_POST['id_jenis'];
$id_pembina       = isset($_POST['id_pembina']) ? mysqli_real_escape_string($koneksi, $_POST['id_pembina']) : '';
$nomor_surat      = mysqli_real_escape_string($koneksi, $_POST['nomor_surat'] ?? '');
$nama_kegiatan    = mysqli_real_escape_string($koneksi, $_POST['nama_kegiatan']);
$ruangan          = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
$tanggal_kegiatan = $_POST['tanggal_kegiatan'];
$jam_mulai        = $_POST['jam_mulai'];
$jam_selesai      = $_POST['jam_selesai'];

$status_awal = "Menunggu Persetujuan Pembina";
$posisi      = "Pembina";
$tanggal_pengajuan = date("Y-m-d H:i:s");

$proposal         = isset($_POST['file_proposal_terupload']) ? mysqli_real_escape_string($koneksi, $_POST['file_proposal_terupload']) : '';
$surat_permohonan = isset($_POST['file_surat_terupload']) ? mysqli_real_escape_string($koneksi, $_POST['file_surat_terupload']) : '';

$query = mysqli_query($koneksi, "
INSERT INTO surat_pengajuan
(
    id_jenis,
    id_ormawa,
    id_pembina,
    nomor_surat,
    file_surat_final,
    dokumen_hash,
    tanggal_pengajuan,
    status_akhir,
    posisi_sekarang,
    urutan_sekarang
)
VALUES
(
    '$id_jenis',
    '$id_ormawa',
    '$id_pembina',
    '',
    '',
    '',
    '$tanggal_pengajuan',
    '$status_awal',
    '$posisi',
    '3'
)
");

if (!$query) {
    die("Gagal menyimpan surat pengajuan : " . mysqli_error($koneksi));
}

$id_surat = mysqli_insert_id($koneksi);

$dokumen_hash = hash('sha256', $id_surat . $id_ormawa . time());
mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

$detail = mysqli_query($koneksi, "
INSERT INTO detail_peminjaman_ruangan
(
    id_surat,
    nomor_surat,
    nama_kegiatan,
    tanggal_mulai,
    tanggal_selesai,
    jam_mulai,
    jam_selesai,
    ruangan_yang_diajukan,
    proposal
)
VALUES
(
    '$id_surat',
    '$nomor_surat',
    '$nama_kegiatan',
    '$tanggal_kegiatan',
    '$tanggal_kegiatan',
    '$jam_mulai',
    '$jam_selesai',
    '$ruangan',
    '$proposal'
)
");

if (!$detail) {
    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = '$id_surat'");
    die("Gagal menyimpan detail peminjaman : " . mysqli_error($koneksi));
}

mysqli_query($koneksi, "
INSERT INTO riwayat_disposisi
(
    id_surat,
    pengirim,
    penerima,
    waktu_disposisi,
    intruksi_catatan,
    status_tindakan
)
VALUES
(
    '$id_surat',
    'ORMAWA',
    'PEMBINA',
    NOW(),
    'Pengajuan peminjaman ruangan menunggu persetujuan pembina.',
    'MENUNGGU'
)
");

$_SESSION['status'] = 'success';
$_SESSION['pesan']  = 'Surat permohonan peminjaman ruangan berhasil dikirim.';
header("Location: ormawa_lacak.php?id=$id_surat");
exit;
