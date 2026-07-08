<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'dosen') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='login.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'];
$id_surat = $_POST['id_surat'];
$aksi = $_POST['aksi'];

// PERBAIKAN: Gunakan JOIN jamak agar bisa membaca data Surat Riset DAN SK Aktif Kuliah
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        sp.*, 
        dsr.id_pb1, dsr.id_pb2, dsr.status_pb1, dsr.status_pb2,
        dak.id_pa, dak.status_pa -- TAMBAHAN: Tarik kolom detail aktif kuliah
    FROM surat_pengajuan sp
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat -- TAMBAHAN: JOIN tabel aktif kuliah
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='dosen_permohonan.php';</script>";
    exit;
}

$hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

// --- LOGIKA SETUJUI ---
if ($aksi == 'setujui') {

    // 1. Logika untuk Dospem 2 (Verifikator Pertama Surat Riset)
    if (isset($data['id_pb2']) && $data['id_pb2'] == $id_dosen && $data['status_pb2'] == 'Menunggu') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Dospem 1' WHERE id_surat = '$id_surat'");
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET status_pb2 = 'Disetujui', ttd_pb2 = '$hash_ttd' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan disetujui. Surat diteruskan ke Dospem 1.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }

    // 2. Logika untuk Dospem 1 (Verifikator Kedua Surat Riset)
    if (isset($data['id_pb1']) && $data['id_pb1'] == $id_dosen && $data['status_pb2'] == 'Disetujui' && $data['status_pb1'] == 'Menunggu') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Admin' WHERE id_surat = '$id_surat'");
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET status_pb1 = 'Disetujui', ttd_pb1 = '$hash_ttd' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan disetujui. Surat diteruskan ke Admin.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }

    // PERBAIKAN: 3. Logika untuk Pembimbing Akademik (SK Aktif Kuliah Kembali)
    if (isset($data['id_pa']) && $data['id_pa'] == $id_dosen && $data['status_pa'] == 'Menunggu') {
        // Update status master menjadi Menunggu Admin agar sinkron dengan timeline pelacakan
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Menunggu Admin' WHERE id_surat = '$id_surat'");
        // Update status detail PA dan simpan tanda tangan digital (hash)
        mysqli_query($koneksi, "UPDATE detail_aktif_kuliah SET status_pa = 'Disetujui', ttd_pa = '$hash_ttd' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan SK Aktif Kuliah disetujui. Surat diteruskan ke Admin.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }
}

// --- LOGIKA TOLAK ---
if ($aksi == 'tolak') {

    // 1. Logika Tolak oleh Dospem 2
    if (isset($data['id_pb2']) && $data['id_pb2'] == $id_dosen && $data['status_pb2'] == 'Menunggu') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Ditolak Dospem 2' WHERE id_surat = '$id_surat'");
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET status_pb2 = 'Ditolak' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan ditolak oleh Pembimbing 2.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }

    // 2. Logika Tolak oleh Dospem 1
    if (isset($data['id_pb1']) && $data['id_pb1'] == $id_dosen && $data['status_pb2'] == 'Disetujui' && $data['status_pb1'] == 'Menunggu') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Ditolak Dospem 1' WHERE id_surat = '$id_surat'");
        mysqli_query($koneksi, "UPDATE detail_surat_riset SET status_pb1 = 'Ditolak' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan ditolak oleh Pembimbing 1.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }

    // PERBAIKAN: 3. Logika Tolak oleh Pembimbing Akademik (SK Aktif Kuliah Kembali)
    if (isset($data['id_pa']) && $data['id_pa'] == $id_dosen && $data['status_pa'] == 'Menunggu') {
        mysqli_query($koneksi, "UPDATE surat_pengajuan SET status_akhir = 'Ditolak Pembimbing Akademik' WHERE id_surat = '$id_surat'");
        mysqli_query($koneksi, "UPDATE detail_aktif_kuliah SET status_pa = 'Ditolak' WHERE id_surat = '$id_surat'");

        echo "<script>alert('Permohonan SK Aktif Kuliah ditolak oleh Pembimbing Akademik.'); window.location='dosen_permohonan.php';</script>";
        exit;
    }
}

// Jika gagal melewati semua pengecekan
echo "<script>alert('Aksi tidak valid atau Anda tidak berhak memverifikasi surat ini.'); window.location='dosen_permohonan.php';</script>";
