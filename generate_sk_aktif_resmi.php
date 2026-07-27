<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$username_admin = $_SESSION['nama'] ?? '';

$id_surat = $_GET['id'] ?? '';

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT 
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        da.semester,
        da.lama_cuti,
        da.ta_mulai_cuti,
        da.ta_selesai_cuti,
        da.tahun_akademik
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    JOIN detail_aktif_kuliah da ON sp.id_surat = da.id_surat
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

$query_pimpinan = mysqli_query($koneksi, "
    SELECT nama_dosen, nip 
    FROM dosen 
    WHERE jabatan LIKE '%wadek 1%' OR jabatan LIKE '%wakil dekan 1%' 
    LIMIT 1
");

$data_pimpinan = mysqli_fetch_assoc($query_pimpinan);

$nama_wadek1 = $data_pimpinan['nama_dosen'] ?? 'Nama Pimpinan Belum Diatur';
$nip_wadek1  = $data_pimpinan['nip'] ?? '-';

$nomorSurat = !empty($data['nomor_surat']) ? $data['nomor_surat'] : "BELUM DIBERI NOMOR";

if (isset($_POST['kirim_balasan'])) {
    $nama_file = "surat_resmi_sk_aktif_" . $id_surat . "_" . time() . ".pdf";

    $update_query = mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET 
            status_akhir = 'Selesai',
            status_pimpinan = 'Disetujui',
            file_surat_final = '$nama_file' 
        WHERE id_surat = '$id_surat'
    ");

    if ($update_query) {
        $_SESSION['status'] = 'success';
        $_SESSION['pesan'] = 'Surat balasan berhasil diselesaikan dan diterbitkan.';
        header("Location: adm_laporan_surat.php");
        exit;
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['pesan'] = 'Gagal memperbarui database.';
        header("Location: adm_laporan_surat.php");
        exit;
    }
}

$bulanIndo = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

$tanggalSurat = date('d') . ' ' . $bulanIndo[date('m')] . ' ' . date('Y');

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode("http://192.168.18.174/localhost/e-letters-saintek/verifikasi_surat.php?hash=" . $data['dokumen_hash']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>SK Resmi Aktif Kuliah Kembali</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="preview.css?v=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_SESSION['pesan'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status']; ?>',
                title: '<?= ($_SESSION['status'] == "success") ? "Berhasil!" : "Gagal!"; ?>',
                text: <?= json_encode($_SESSION['pesan']); ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        </script>
        <?php
        unset($_SESSION['pesan']);
        unset($_SESSION['status']);
        ?>
    <?php endif; ?>

    <div class="preview-container">
        <div class="surat-wrapper-resmi">
            <table class="kop-surat">
                <tr>
                    <td class="kop-logo">
                        <img src="images/Logo UINRIL(2).png" alt="Logo UIN RIL">
                    </td>
                    <td class="kop-teks">
                        <h3>KEMENTERIAN AGAMA</h3>
                        <h3>UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
                        <h2>FAKULTAS SAINS DAN TEKNOLOGI</h2>
                        <small>
                            Alamat: Jl. Endro Suratmin Sukarame I, Telp (0721) 703289 Bandar Lampung
                        </small>
                    </td>
                </tr>
            </table>
            <hr class="garis-kop">


            <div style="text-align: center;">
                <h3 style="margin:0; text-decoration:underline;">SURAT AKTIF KULIAH KEMBALI</h3>
                <p>Nomor: <?= $nomorSurat; ?></p>
            </div>

            <p>Yang bertandatangan di bawah ini :</p>
            <table style="width:100%; margin-left:20px;">
                <tr>
                    <td class="col-label">Nama</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($nama_wadek1); ?></td>
                </tr>
                <tr>
                    <td>NIP</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($nip_wadek1); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Jabatan</td>
                    <td class="col-separator">:</td>
                    <td>Wakil Dekan 1 Fakultas Sains dan Teknologi UIN Raden Intan Lampung</td>
                </tr>
            </table>

            <p>Dengan ini menerangkan dengan sesungguhnya bahwa :</p>
            <table style="width:100%; margin-left:20px;">
                <tr>
                    <td class="col-label">Nama</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">NPM</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Jurusan</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Semester</td>
                    <td class="col-separator">:</td>
                    <td><?= htmlspecialchars($data['semester']); ?></td>
                </tr>
            </table>

            <p>Adalah benar mahasiswa Fakultas Sains dan Teknologi UIN Raden Intan Lampung. Surat keterangan ini diberikan untuk <b>Kuliah Kembali</b> pada semester <?= $data['semester']; ?> Tahun Akademik <?= $data['tahun_akademik']; ?>, berdasarkan surat Cuti Kuliah.</p>

            <p>Demikian surat keterangan ini dibuat untuk diperhatikan dan dilaksanakan sebagaimana mestinya.</p>

            <div class="surat-footer-section">
                <div class="tembusan-area">
                    <u>Tembusan Yth.:</u><br>
                    1. Dekan Fakultas Sains dan Teknologi UIN Raden Intan Lampung;<br>
                    2. Kabag Keuangan UIN Raden Intan Lampung;<br>
                    3. Kabag Akademik & Kemahasiswaan UIN Raden Intan Lampung;<br>
                    4. Ketua Jurusan Sistem Informasi;<br>
                    5. Pembimbing Akademik
                </div>

                <div class="ttd-container-sk">
                    <p class="tgl-surat-sk">Bandar Lampung, <?= $tanggalSurat; ?></p>
                    <p class="ttd-no-margin">An. Dekan</p>
                    <p class="ttd-margin-bottom"><b>Wakil Dekan 1,</b></p>

                    <?php if (!empty($data['dokumen_hash'])) { ?>
                        <img src="<?= $qr_url; ?>" class="qr-ttd" alt="QR Verifikasi">
                    <?php } else { ?>
                        <br><br><br>
                    <?php } ?>

                    <p class="ttd-margin-top">
                        <span class="nama-penandatangan"><?= htmlspecialchars($nama_wadek1); ?></span><br>
                        NIP. <?= htmlspecialchars($nip_wadek1); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="action">
            <?php
            $asal_halaman = $_GET['asal'] ?? '';
            if ($asal_halaman == 'laporan') {
                $link_kembali = 'adm_laporan_surat.php';
            } elseif ($asal_halaman == 'pimpinan') {
                $link_kembali = 'pimpinan_riwayat.php';
            } else {
                $link_kembali = 'mhs_riwayat.php';
            }
            ?>
            <a href="<?= $link_kembali; ?>" class="btn-secondary">Kembali</a>

            <button onclick="window.print()" class="btn-print">
                Unduh Surat
            </button>

            <?php
            $is_view_only = (isset($_GET['view']) && $_GET['view'] == 'true');

            if (isset($_SESSION['role']) && strtolower($_SESSION['role']) == 'admin' && $username_admin !== 'ADM001' && !$is_view_only) {
            ?>
                <form action="?id=<?= $id_surat; ?>" method="POST">
                    <button type="submit" name="kirim_balasan" class="btn-approve">
                        Terbitkan Surat
                    </button>
                </form>
            <?php } ?>
        </div>
    </div>
</body>

</html>