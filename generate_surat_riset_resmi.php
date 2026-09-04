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
        dsr.semester,          
        dsr.surat_ditujukan,   
        dsr.judul_skripsi,       
        dsr.lokasi_penelitian    
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
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

if (isset($_POST['kirim_balasan'])) {
    $nama_file = "surat_resmi_izin_riset_" . $id_surat . "_" . time() . ".pdf";

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET
            status_akhir = 'Selesai',
            status_balasan = 'Disetujui',
            file_surat_final = '$nama_file'
        WHERE id_surat = '$id_surat'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat balasan berhasil diselesaikan dan diterbitkan.';
    header("Location: adm_laporan_surat.php");
    exit;
}

$nomorSurat = !empty($data['nomor_surat']) ? $data['nomor_surat'] : "BELUM DIBERI NOMOR";

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

$host = $_SERVER['HTTP_HOST'];
$base_url = "http://" . $host . "/e letters saintek";
$link_pimpinan = $base_url . "/pimpinan_qr_verif.php?hash=" . $data['dokumen_hash'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_pimpinan);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin Reset</title>

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

            <table class="info-surat">
                <tr>
                    <td>Nomor</td>
                    <td>:</td>
                    <td colspan="3"><?= htmlspecialchars($nomorSurat); ?></td>
                </tr>
                <tr>
                    <td>Sifat</td>
                    <td>:</td>
                    <td colspan="3">Biasa</td>
                </tr>
                <tr>
                    <td>Lampiran</td>
                    <td>:</td>
                    <td colspan="3">1 Eks</td>
                </tr>
                <tr>
                    <td>Perihal</td>
                    <td>:</td>
                    <td colspan="3"><?= htmlspecialchars($data['nama_surat']); ?></td>
                </tr>
            </table>

            <br>

            <p>
                Kepada Yth,<br><b>
                    <?= htmlspecialchars($data['surat_ditujukan'] ?? 'Pimpinan Instansi'); ?></b><br>
                di<br>
                <span class="indent-tempat">Tempat</span>
            </p>

            <p>Assalamu’alaikum Wr. Wb.</p>

            <p class="paragraf-isi">
                Bersama ini disampaikan permohonan izin untuk mengadakan Riset guna penulisan skripsi mahasiswa kami sebagai berikut:
            </p>

            <table class="tabel-data-mhs">
                <tr>
                    <td class="col-label">Nama / NPM:</td>
                    <td class="col-titik">:</td>
                    <td><?= htmlspecialchars($data['nama_mhs']); ?> / <?= htmlspecialchars($data['npm']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Semester / Program Studi</td>
                    <td class="col-titik">:</td>
                    <td><?= htmlspecialchars($data['semester']); ?> / <?= htmlspecialchars($data['nama_prodi']); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Judul Skripsi</td>
                    <td class="col-titik">:</td>
                    <td class="paragraf-isi"><?= htmlspecialchars($data['judul_skripsi'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Lokasi Penelitian</td>
                    <td class="col-titik">:</td>
                    <td><?= htmlspecialchars($data['lokasi_penelitian'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="col-label">Penanggung Jawab</td>
                    <td class="col-titik">:</td>
                    <td>Dosen Pembimbing</td>
                </tr>
            </table>

            <p class="paragraf-isi">
                Penelitian ini semata-mata untuk kepentingan ilmiah sebagai data dalam penulisan skripsi yang bersangkutan,
                sebagai bahan pertimbangan Saudara bersama ini dilampirkan 1 (satu) Eks. Proposal penelitian dimaksud.
            </p>

            <p>Demikian, atas perhatian dan kerjasamanya diucapkan terima kasih.</p>

            <p>Wassalamu’alaikum Wr. Wb.</p>

            <div class="ttd-container">
                <p class="tgl-surat">Bandar Lampung, <?= $tanggalSurat; ?></p>

                <p>Wakil Dekan 1,</p>

                <?php if (!empty($data['dokumen_hash'])) { ?>
                    <img src="<?= $qr_url; ?>" class="qr-ttd" alt="QR Verifikasi">
                <?php } else { ?>
                    <br><br><br><br>
                <?php } ?>

                <p>
                    <strong><?= htmlspecialchars($nama_wadek1); ?></strong><br>
                    NIP. <?= htmlspecialchars($nip_wadek1); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="action">
        <?php
        $is_preview = (isset($_GET['view']) && $_GET['view'] == 'true');
        $asal_halaman = $_GET['asal'] ?? '';

        if (!$is_preview || $asal_halaman == 'riwayat_pimpinan' || $asal_halaman == 'pimpinan' || $asal_halaman == 'laporan') :

            if ($asal_halaman == 'laporan') {
                $link_kembali = 'adm_laporan_surat.php';
            } elseif ($asal_halaman == 'pimpinan' || $asal_halaman == 'riwayat_pimpinan') {
                $link_kembali = 'pimpinan_riwayat.php';
            } elseif ($asal_halaman == 'tracking') {
                $link_kembali = 'pimpinan_tracking.php';
            } else {
                $link_kembali = 'mhs_riwayat.php';
            }
        ?>
            <div class="action-button-container">
                <a href="<?= $link_kembali; ?>" class="btn-secondary">Kembali</a>

                <button onclick="window.print()" class="btn-print">
                    Unduh Surat
                </button>
            </div>
        <?php endif; ?>

        <?php
        $is_view_only = (isset($_GET['view']) && $_GET['view'] == 'true');

        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) == 'admin' && $username_admin !== 'ADM001' && !$is_view_only) {
        ?>
            <form action="?id=<?= $id_surat; ?>" method="POST" class="action-button-container">
                <button type="submit" name="kirim_balasan" class="btn-approve">
                    Terbitkan Surat
                </button>
            </form>
        <?php } ?>
    </div>
</body>

</html>