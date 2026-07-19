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

$id_ormawa = $_POST['id_ormawa'];
$id_jenis  = $_POST['id_jenis'];
// TANGKAP ID PEMBINA DARI INPUT HIDDEN PREVIEW
$id_pembina = isset($_POST['id_pembina']) ? mysqli_real_escape_string($koneksi, $_POST['id_pembina']) : '';

$nama_kegiatan   = mysqli_real_escape_string($koneksi,$_POST['nama_kegiatan']);
$jenis_kegiatan  = mysqli_real_escape_string($koneksi,$_POST['jenis_kegiatan']);
$tema_kegiatan   = mysqli_real_escape_string($koneksi,$_POST['tema_kegiatan']);
$tujuan_kegiatan = mysqli_real_escape_string($koneksi,$_POST['tujuan_kegiatan']);

$ruangan          = mysqli_real_escape_string($koneksi,$_POST['ruangan']);
$tanggal_kegiatan = $_POST['tanggal_kegiatan'];
$jam_mulai        = $_POST['jam_mulai'];
$jam_selesai      = $_POST['jam_selesai'];

$jumlah_peserta = $_POST['jumlah_peserta'];

$status_awal = "Menunggu Persetujuan Pembina";
$posisi      = "Pembina";

$tanggal_pengajuan = date("Y-m-d H:i:s");

/* =====================================================
   MENANGKAP NAMA FILE YANG SUDAH TERUPLOAD DI PREVIEW
===================================================== */
$proposal         = isset($_POST['file_proposal_terupload']) ? mysqli_real_escape_string($koneksi, $_POST['file_proposal_terupload']) : '';
$surat_permohonan = isset($_POST['file_surat_terupload']) ? mysqli_real_escape_string($koneksi, $_POST['file_surat_terupload']) : '';

/* =====================================================
   SIMPAN SURAT PENGAJUAN
===================================================== */
$query = mysqli_query($koneksi,"
INSERT INTO surat_pengajuan
(
    id_jenis,
    id_ormawa,
    id_pembina,
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
    '$tanggal_pengajuan',
    '$status_awal',
    '$posisi',
    '3'
)
");

if(!$query){
    die("Gagal menyimpan surat pengajuan : " . mysqli_error($koneksi));
}

$id_surat = mysqli_insert_id($koneksi);

/* =====================================================
   SIMPAN DETAIL PEMINJAMAN
===================================================== */
$detail = mysqli_query($koneksi,"
INSERT INTO detail_peminjaman_ruangan
(
    id_surat,
    nama_kegiatan,
    jenis_kegiatan,
    tema_kegiatan,
    tujuan_kegiatan,
    tanggal_mulai,
    tanggal_selesai,
    jam_mulai,
    jam_selesai,
    ruangan_yang_diajukan,
    jumlah_peserta,
    proposal,             
    surat_permohonan      
)
VALUES
(
    '$id_surat',
    '$nama_kegiatan',
    '$jenis_kegiatan',
    '$tema_kegiatan',
    '$tujuan_kegiatan',
    '$tanggal_kegiatan',
    '$tanggal_kegiatan',
    '$jam_mulai',
    '$jam_selesai',
    '$ruangan',
    '$jumlah_peserta',
    '$proposal',           
    '$surat_permohonan'    
)
");

if(!$detail){
    die("Gagal menyimpan detail peminjaman : " . mysqli_error($koneksi));
}

/* =====================================================
   RIWAYAT DISPOSISI
===================================================== */
mysqli_query($koneksi,"
INSERT INTO riwayat_disposisi
(
    id_surat,
    pengirim,
    penerima,
    waktu_disposisi,
    intruksi_catatan
)
VALUES
(
    '$id_surat',
    'ORMAWA',
    'PEMBINA',
    NOW(),
    'Pengajuan peminjaman ruangan menunggu persetujuan pembina.'
)
");

/* =====================================================
   REDIRECT
===================================================== */
echo "
<script>
alert('Pengajuan peminjaman ruangan berhasil dikirim.');
window.location='ormawa_lacak.php';
</script>
";
?>