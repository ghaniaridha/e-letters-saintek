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

$nomor_surat      = mysqli_real_escape_string(
    $koneksi,
    $_POST['nomor_surat']
        ?? ''
);

$nama_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['nama_kegiatan']
);

$tema_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tema_kegiatan']
);


$tempat_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tempat_kegiatan']
);

$tanggal_kegiatan = mysqli_real_escape_string(
    $koneksi,
    $_POST['tanggal_kegiatan']
);


$proposal = isset($_POST['file_proposal_terupload'])
    ? mysqli_real_escape_string(
        $koneksi,
        $_POST['file_proposal_terupload']
    )
    : '';

$status_awal = "Menunggu Persetujuan Pembina";
$posisi      = "Pembina";
$urutan      = 3;

$tanggal_pengajuan = date(
    "Y-m-d H:i:s"
);

$query = mysqli_query(
    $koneksi,
    "
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
        '$nomor_surat',
        '',
        '',
        '$tanggal_pengajuan',
        '$status_awal',
        '$posisi',
        '$urutan'
    )
"
);

if (!$query) {
    die("Gagal menyimpan surat pengajuan : "
        . mysqli_error($koneksi));
}

$id_surat = mysqli_insert_id($koneksi);

$dokumen_hash = hash('sha256', $id_surat . $id_ormawa . time());
mysqli_query($koneksi, "UPDATE surat_pengajuan SET dokumen_hash = '$dokumen_hash' WHERE id_surat = '$id_surat'");

$detail = mysqli_query(
    $koneksi,
    "
    INSERT INTO detail_pengajuan_dana
    (
        id_surat,
        nomor_surat,
        nama_kegiatan,
        tema_kegiatan,
        tanggal_kegiatan,
        tempat_kegiatan,
        proposal
    )
    VALUES
    (
        '$id_surat',
        '$nomor_surat',
        '$nama_kegiatan',
        '$tema_kegiatan',
        '$tanggal_kegiatan',
        '$tempat_kegiatan',
        '$proposal'
    )
"
);

if (!$detail) {
    die("Gagal menyimpan detail pengajuan dana : "
        . mysqli_error($koneksi));
}

mysqli_query(
    $koneksi,
    "
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
        'Pengajuan dana menunggu persetujuan pembina.',
        'MENUNGGU'
    )
"
);

$_SESSION['status'] = 'success';
$_SESSION['pesan']  = 'Surat permohonan pengajuan dana kegiatan berhasil dikirim.';
header("Location: ormawa_lacak.php?id=$id_surat");
exit;
