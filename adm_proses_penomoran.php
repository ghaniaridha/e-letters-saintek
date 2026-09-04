<?php
session_start();
include "koneksi.php";

date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

if (isset($_GET['id']) && isset($_GET['metode'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);
    $metode   = $_GET['metode'];

    $query_cek = mysqli_query($koneksi, "
        SELECT sp.id_surat, js.nama_surat 
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    ");

    $data = mysqli_fetch_assoc($query_cek);

    if (!$data) {
        echo "<script>alert('Data tidak ditemukan!'); window.location='adm_riwayat_review.php';</script>";
        exit;
    }

    $namaSurat = strtolower($data['nama_surat']);
    $nomor_final = "";

    // PROSES PENENTUAN NOMOR SURAT
    if ($metode == 'manual') {
        // Manual --> tangkap nomor dari inputan URL yang dikirim SweetAlert
        $nomor_input = isset($_GET['nomor']) ? mysqli_real_escape_string($koneksi, $_GET['nomor']) : '';
        $nomor_final = $nomor_input;
    } else if ($metode == 'auto') {
        // Otomatis --> Hitung urutan berdasarkan surat yang valid di tahun ini
        $bulan = date('m'); // otomatis menghasilkan 01, 02, dst.
        $tahun = date('Y');

        // Query untuk menghitung jumlah surat yang sudah memiliki nomor
        // (Status Selesai atau Menunggu Penomoran) pada tahun berjalan
        $query_urut = mysqli_query($koneksi, "
            SELECT COUNT(id_surat) AS total_surat 
            FROM surat_pengajuan 
            WHERE nomor_surat IS NOT NULL 
              AND nomor_surat != '' 
              AND (status_akhir = 'Selesai' OR status_akhir = 'Menunggu Penomoran')
              AND YEAR(tanggal_pengajuan) = '$tahun'
        ");

        $data_urut = mysqli_fetch_assoc($query_urut);

        $urutan_baru = (int)$data_urut['total_surat'] + 1;

        // Format angka menjadi 3 digit (Contoh: 1 menjadi 001, 12 menjadi 012)
        $id_format = str_pad($urutan_baru, 3, "0", STR_PAD_LEFT);

        $nomor_final = "B-" . $id_format . "/Un.16/FST/PP.009/" . $bulan . "/" . $tahun;
    }

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan 
        SET nomor_surat = '$nomor_final',
            waktu_selesai = '$waktu_sekarang' 
        WHERE id_surat = '$id_surat'
    ");

    if (strpos($namaSurat, 'aktif') !== false) {
        header("Location: generate_sk_aktif_resmi.php?id=$id_surat");
        exit;
    } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
        header("Location: generate_surat_magang_resmi.php?id=$id_surat");
        exit;
    } elseif (strpos($namaSurat, 'lulus') !== false) {
        header("Location: generate_sk_lulus_resmi.php?id=$id_surat");
        exit;
    } elseif (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
        header("Location: generate_skmk_resmi.php?id=$id_surat");
        exit;
    } else {
        header("Location: generate_surat_riset_resmi.php?id=$id_surat");
        exit;
    }
} else {
    header("Location: adm_laporan_surat.php");
    exit;
}
