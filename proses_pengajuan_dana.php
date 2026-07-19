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
    header("Location: ormawa_form_pengajuan_dana.php");
    exit;
}

$id_ormawa = mysqli_real_escape_string(
    $koneksi,
    $_POST['id_ormawa']
);

$id_jenis = mysqli_real_escape_string(
    $koneksi,
    $_POST['id_jenis']
);

$id_pembina = mysqli_real_escape_string(
    $koneksi,
    $_POST['id_pembina']
);

/* ==========================
   DATA PENGAJUAN DANA
========================== */

$nama_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['nama_kegiatan']
);

$tema_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tema_kegiatan']
);

$tanggal_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tanggal_kegiatan']
);

$tempat_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tempat_kegiatan']
);

$nominal_pengajuan = mysqli_real_escape_string(
    $koneksi,
    $_POST['nominal_pengajuan']
);

$deskripsi_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['deskripsi_kegiatan']
);

/* ==========================
   FILE PROPOSAL
========================== */

$proposal = isset($_POST['file_proposal_terupload'])
    ? mysqli_real_escape_string(
        $koneksi,
        $_POST['file_proposal_terupload']
      )
    : '';

/* ==========================
   STATUS AWAL
========================== */

$status_awal = "Menunggu Persetujuan Pembina";
$posisi      = "Pembina";
$urutan      = 3;

$tanggal_pengajuan = date(
    "Y-m-d H:i:s"
);

/* ==========================
   SIMPAN SURAT PENGAJUAN
========================== */

$query = mysqli_query(
    $koneksi,
    "
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
        '$urutan'
    )
"
);

if (!$query) {
    die(
        "Gagal menyimpan surat pengajuan : "
        . mysqli_error($koneksi)
    );
}

$id_surat = mysqli_insert_id($koneksi);

/* ==========================
   SIMPAN DETAIL PENGAJUAN DANA
========================== */

$detail = mysqli_query(
    $koneksi,
    "
    INSERT INTO detail_pengajuan_dana
    (
        id_surat,
        nama_kegiatan,
        tema_kegiatan,
        tanggal_kegiatan,
        tempat_kegiatan,
        nominal_pengajuan,
        deskripsi_kegiatan,
        proposal
    )
    VALUES
    (
        '$id_surat',
        '$nama_kegiatan',
        '$tema_kegiatan',
        '$tanggal_kegiatan',
        '$tempat_kegiatan',
        '$nominal_pengajuan',
        '$deskripsi_kegiatan',
        '$proposal'
    )
"
);

if (!$detail) {
    die(
        "Gagal menyimpan detail pengajuan dana : "
        . mysqli_error($koneksi)
    );
}

/* ==========================
   RIWAYAT DISPOSISI
========================== */

mysqli_query(
    $koneksi,
    "
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
        'Pengajuan dana menunggu persetujuan pembina.'
    )
"
);

/* ==========================
   REDIRECT
========================== */

echo "
<script>
alert('Pengajuan dana berhasil dikirim ke Pembina.');
window.location='ormawa_lacak.php';
</script>
";
?>