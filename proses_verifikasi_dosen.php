<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>
            alert('Silakan login sebagai dosen');
            window.location='index.php';
          </script>";
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

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

        o.id_pembina,
        o.jenis_organisasi,
        p.id_kaprodi

    FROM surat_pengajuan sp

    LEFT JOIN detail_surat_riset dsr
        ON sp.id_surat = dsr.id_surat

    LEFT JOIN detail_aktif_kuliah dak
        ON sp.id_surat = dak.id_surat

    LEFT JOIN ormawa o
        ON sp.id_ormawa = o.id_ormawa
        
    LEFT JOIN prodi p 
        ON o.id_prodi = p.id_prodi

    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>
            alert('Data surat tidak ditemukan');
            window.location='dosen_permohonan_akademik.php';
          </script>";
    exit;
}

$hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

/* =====================================================
   SETUJUI
===================================================== */
if ($aksi == 'setujui') {

    /* Dospem 2 (Akademik) */
    if (
        !empty($data['id_pb2']) &&
        $data['id_pb2'] == $id_dosen &&
        $data['status_pb2'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Dospem 1'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_surat_riset
            SET status_pb2='Disetujui',
                ttd_pb2='$hash_ttd',
                waktu_pb2='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Dospem 1';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Dospem 1 (Akademik) */
    if (
        !empty($data['id_pb1']) &&
        $data['id_pb1'] == $id_dosen &&
        $data['status_pb2'] == 'Disetujui' &&
        $data['status_pb1'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                tujuan_admin='admin2'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_surat_riset
            SET status_pb1='Disetujui',
                ttd_pb1='$hash_ttd',
                waktu_pb1='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Admin';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Pembimbing Akademik (Akademik) */
    if (
        !empty($data['id_pa']) &&
        $data['id_pa'] == $id_dosen &&
        $data['status_pa'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                tujuan_admin='admin2'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_aktif_kuliah
            SET status_pa='Disetujui',
                ttd_pa='$hash_ttd',
                waktu_pa='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Surat diteruskan ke Admin';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Penanggung Jawab Organisasi (Pembina UKM ATAU Kaprodi Ormawa) */
    $is_pembina_surat = (isset($data['id_pembina']) && $data['id_pembina'] == $id_dosen);
    $is_kaprodi_surat = (isset($data['id_kaprodi']) && $data['id_kaprodi'] == $id_dosen);

    if (($is_pembina_surat || $is_kaprodi_surat) && $data['posisi_sekarang'] == 'Pembina') {

        $nama_pengirim = (isset($data['jenis_organisasi']) && $data['jenis_organisasi'] == 'Ormawa') ? 'KAPRODI' : 'PEMBINA';
        $teks_pengirim = (isset($data['jenis_organisasi']) && $data['jenis_organisasi'] == 'Ormawa') ? 'Kaprodi' : 'Pembina';

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Menunggu Admin',
                posisi_sekarang='Admin',
                urutan_sekarang='2',
                tujuan_admin='admin1'
            WHERE id_surat='$id_surat'
        ");

        $cek_jenis = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT js.nama_surat FROM surat_pengajuan sp JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'"));

        if (stripos($cek_jenis['nama_surat'], 'dana') !== false) {
            mysqli_query($koneksi, "UPDATE detail_pengajuan_dana SET waktu_pembina = NOW() WHERE id_surat='$id_surat'");
        } else {
            mysqli_query($koneksi, "UPDATE detail_peminjaman_ruangan SET waktu_pembina = NOW() WHERE id_surat='$id_surat'");
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
                '$nama_pengirim', 
                'ADMIN1', 
                NOW(), 
                'Pengajuan telah disetujui $teks_pengirim dan diteruskan ke Admin.', 
                'MENUNGGU'
            )
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = "Pengajuan Organisasi berhasil disetujui $teks_pengirim dan diteruskan ke Admin";

        header("Location: dosen_riwayat_ormawa.php");
        exit;
    }
}

/* =====================================================
   KEMBALIKAN (REVISI)
===================================================== */
if ($aksi == 'kembalikan' || $aksi == 'tolak') {

    /* Dospem 2 (Akademik) */
    if (
        !empty($data['id_pb2']) &&
        $data['id_pb2'] == $id_dosen &&
        $data['status_pb2'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
           UPDATE surat_pengajuan
            SET status_akhir='Perbaikan',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_surat_riset
            SET status_pb2='Perbaikan',
                catatan_pb2='$catatan',
                waktu_pb2='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil dikembalikan ke mahasiswa untuk direvisi.';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Dospem 1 (Akademik) */
    if (
        !empty($data['id_pb1']) &&
        $data['id_pb1'] == $id_dosen &&
        $data['status_pb2'] == 'Disetujui' &&
        $data['status_pb1'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Perbaikan',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_surat_riset
            SET status_pb1='Perbaikan',
                catatan_pb1='$catatan',
                waktu_pb1='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil dikembalikan ke mahasiswa untuk direvisi.';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Pembimbing Akademik */
    if (
        !empty($data['id_pa']) &&
        $data['id_pa'] == $id_dosen &&
        $data['status_pa'] == 'Menunggu'
    ) {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Perbaikan',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        mysqli_query($koneksi, "
            UPDATE detail_aktif_kuliah
            SET status_pa='Perbaikan',
                catatan_pa='$catatan',
                waktu_pa='$waktu_sekarang'
            WHERE id_surat='$id_surat'
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = 'Permohonan berhasil dikembalikan ke mahasiswa untuk direvisi.';

        header("Location: dosen_riwayat_akademik.php");
        exit;
    }

    /* Penanggung Jawab Organisasi (Pembina UKM ATAU Kaprodi Ormawa) */
    $is_pembina_surat = (isset($data['id_pembina']) && $data['id_pembina'] == $id_dosen);
    $is_kaprodi_surat = (isset($data['id_kaprodi']) && $data['id_kaprodi'] == $id_dosen);

    if (($is_pembina_surat || $is_kaprodi_surat) && $data['posisi_sekarang'] == 'Pembina') {

        $nama_pengirim = (isset($data['jenis_organisasi']) && $data['jenis_organisasi'] == 'Ormawa') ? 'KAPRODI' : 'PEMBINA';
        $teks_pengirim = (isset($data['jenis_organisasi']) && $data['jenis_organisasi'] == 'Ormawa') ? 'Kaprodi' : 'Pembina';

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET status_akhir='Perbaikan',
                catatan='$catatan'
            WHERE id_surat='$id_surat'
        ");

        $cek_jenis = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT js.nama_surat FROM surat_pengajuan sp JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.id_surat='$id_surat'"));

        if (stripos($cek_jenis['nama_surat'], 'dana') !== false) {
            mysqli_query($koneksi, "UPDATE detail_pengajuan_dana SET waktu_pembina = NOW() WHERE id_surat='$id_surat'");
        } else {
            mysqli_query($koneksi, "UPDATE detail_peminjaman_ruangan SET waktu_pembina = NOW() WHERE id_surat='$id_surat'");
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
                '$nama_pengirim', 
                'MAHASISWA', 
                NOW(), 
                '$catatan', 
                'PERBAIKAN'
            )
        ");

        $_SESSION['status'] = 'success';
        $_SESSION['pesan']  = "Pengajuan Organisasi dikembalikan oleh $teks_pengirim untuk direvisi.";

        header("Location: dosen_riwayat_ormawa.php");
        exit;
    }
}

// Jika gagal / tidak masuk skenario mana pun
$_SESSION['status'] = 'error';
$_SESSION['pesan']  = 'Aksi tidak valid atau Anda tidak memiliki hak verifikasi.';
header("Location: dosen_permohonan_akademik.php");
exit;
