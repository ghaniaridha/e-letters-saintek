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
$id_surat = $_POST['id_surat'];
$aksi = $_POST['aksi'];

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT *
    FROM surat_pengajuan
    WHERE id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>
        alert('Data surat tidak ditemukan');
        window.location='dosen_permohonan.php';
    </script>";
    exit;
}

if ($aksi == 'setujui') {

    $hash_ttd = hash('sha256', $id_surat . $id_dosen . time());

    if ($data['pembimbing_1'] == $id_dosen && $data['status_dospem1'] == 'Menunggu') {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET
                status_dospem1 = 'Disetujui',
                status_akhir = 'Menunggu Dospem 2',
                ttd_dospem1 = '$hash_ttd'
            WHERE id_surat = '$id_surat'
        ");

        echo "<script>
            alert('Permohonan berhasil disetujui. Surat diteruskan ke Dospem 2.');
            window.location='dosen_permohonan.php';
        </script>";
        exit;
    }

    if ($data['pembimbing_2'] == $id_dosen && $data['status_dospem1'] == 'Disetujui' && $data['status_dospem2'] == 'Menunggu') {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET
                status_dospem2 = 'Disetujui',
                status_akhir = 'Menunggu Admin',
                ttd_dospem2 = '$hash_ttd'
            WHERE id_surat = '$id_surat'
        ");

        echo "<script>
            alert('Permohonan berhasil disetujui. Surat diteruskan ke Admin.');
            window.location='dosen_permohonan.php';
        </script>";
        exit;
    }
}

if ($aksi == 'tolak') {

    if ($data['pembimbing_1'] == $id_dosen && $data['status_dospem1'] == 'Menunggu') {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET
                status_dospem1 = 'Ditolak',
                status_akhir = 'Ditolak Dospem 1'
            WHERE id_surat = '$id_surat'
        ");

        echo "<script>
            alert('Permohonan ditolak.');
            window.location='dosen_permohonan.php';
        </script>";
        exit;
    }

    if ($data['pembimbing_2'] == $id_dosen && $data['status_dospem1'] == 'Disetujui' && $data['status_dospem2'] == 'Menunggu') {

        mysqli_query($koneksi, "
            UPDATE surat_pengajuan
            SET
                status_dospem2 = 'Ditolak',
                status_akhir = 'Ditolak Dospem 2'
            WHERE id_surat = '$id_surat'
        ");

        echo "<script>
            alert('Permohonan ditolak.');
            window.location='dosen_permohonan.php';
        </script>";
        exit;
    }
}

echo "<script>
    alert('Aksi tidak valid atau Anda tidak berhak memverifikasi surat ini.');
    window.location='dosen_permohonan.php';
</script>";
?>