<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>
            alert('Silakan login sebagai dosen');
            window.location='login.php';
          </script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];
$id_surat = (int) $_POST['id_surat'];
$aksi     = $_POST['aksi'];
$catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
        sp.*,

        dsr.id_pb1,
        dsr.id_pb2,
        dsr.status_pb1,
        dsr.status_pb2,

        dak.id_pa,
        dak.status_pa,

        o.id_pembina

    FROM surat_pengajuan sp

    LEFT JOIN detail_surat_riset dsr
        ON sp.id_surat = dsr.id_surat

    LEFT JOIN detail_aktif_kuliah dak
        ON sp.id_surat = dak.id_surat

    LEFT JOIN ormawa o
        ON sp.id_ormawa = o.id_ormawa

    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>
            alert('Data surat tidak ditemukan');
            window.location='dosen_permohonan.php';
          </script>";
    exit;
}

$hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

/* =====================================================
   SETUJUI
===================================================== */
if ($aksi == 'setujui') {

    /* Dospem 2 */
    if (
        !empty($data['id_pb2']) &&
        $data['id_pb2'] == $id_dosen &&
        $data['status_pb2'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Dospem 1'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_surat_riset
            SET status_pb2='Disetujui',
                ttd_pb2='$hash_ttd'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Dospem 1';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Dospem 1 */
    if (
        !empty($data['id_pb1']) &&
        $data['id_pb1'] == $id_dosen &&
        $data['status_pb2'] == 'Disetujui' &&
        $data['status_pb1'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                tujuan_admin='admin2'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_surat_riset
            SET status_pb1='Disetujui',
                ttd_pb1='$hash_ttd'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Admin';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Pembimbing Akademik */
    if (
        !empty($data['id_pa']) &&
        $data['id_pa'] == $id_dosen &&
        $data['status_pa'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                tujuan_admin='admin2'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_aktif_kuliah
            SET status_pa='Disetujui',
                ttd_pa='$hash_ttd'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Admin';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Pembina Ormawa */
    if (
        !empty($data['id_pembina']) &&
        $data['id_pembina'] == $id_dosen &&
        $data['posisi_sekarang'] == 'Pembina'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                posisi_sekarang='Admin',
                urutan_sekarang='2',
                tujuan_admin='admin1'
            WHERE id_surat='$id_surat'
        ");

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
            'PEMBINA',
            'ADMIN1',
            NOW(),
            'Pengajuan dana telah disetujui pembina dan diteruskan ke Admin 1.'
        )
    ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Pengajuan Ormawa berhasil disetujui dan diteruskan ke Admin 1';

        header("Location:dosen_riwayat.php");
        exit;
    }
}


/* =====================================================
   TOLAK
===================================================== */
if ($aksi == 'tolak') {

    /* Dospem 2 */
    if (
        !empty($data['id_pb2']) &&
        $data['id_pb2'] == $id_dosen &&
        $data['status_pb2'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
           UPDATE surat_pengajuan
            SET status_akhir='Ditolak Dospem 2',
                posisi_sekarang='Selesai',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_surat_riset
            SET status_pb2='Ditolak'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil ditolak.';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Dospem 1 */
    if (
        !empty($data['id_pb1']) &&
        $data['id_pb1'] == $id_dosen &&
        $data['status_pb2'] == 'Disetujui' &&
        $data['status_pb1'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Ditolak Dospem 1',
                posisi_sekarang='Selesai',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_surat_riset
            SET status_pb1='Ditolak'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil ditolak.';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Pembimbing Akademik */
    if (
        !empty($data['id_pa']) &&
        $data['id_pa'] == $id_dosen &&
        $data['status_pa'] == 'Menunggu'
    ) {

        mysqli_query($koneksi,"
            UPDATE surat_pengajuan
            SET status_akhir='Ditolak Pembimbing Akademik',
                posisi_sekarang='Selesai',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi,"
            UPDATE detail_aktif_kuliah
            SET status_pa='Ditolak'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil ditolak.';

        header("Location:dosen_riwayat.php");
        exit;
    }

    /* Pembina Ormawa */
    if (
        !empty($data['id_pembina']) &&
        $data['id_pembina'] == $id_dosen &&
        $data['posisi_sekarang'] == 'Pembina'
    ) {

        mysqli_query($koneksi,"
           UPDATE surat_pengajuan
            SET status_akhir='Ditolak Pembina',
                posisi_sekarang='Selesai',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Pengajuan Ormawa ditolak';

        header("Location:dosen_riwayat.php");
        exit;
    }
}

$_SESSION['status'] = 'error';
$_SESSION['pesan']  = 'Aksi tidak valid atau Anda tidak memiliki hak verifikasi.';
header("Location:dosen_permohonan.php");
exit;
?>