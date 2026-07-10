<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

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
    $nama_file = "surat_resmi_izin_riset" . $id_surat . "_" . time() . ".pdf";

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET
            status_akhir = 'Selesai',
            status_balasan = 'Disetujui',
            file_surat_final = '$nama_file',
            file_surat_final = '$nama_file'
        WHERE id_surat = '$id_surat'
    ");

    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Surat balasan berhasil dibuat dan dikirim ke mahasiswa.';
    header("Location: pimpinan_riwayat.php");
    exit;
}

if (empty($data['nomor_surat'])) {
    $tahun = date('Y');

    $cekNomor = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM surat_pengajuan
        WHERE status_akhir IN ('Menunggu Surat Balasan', 'Selesai')
        AND YEAR(tanggal_pengajuan) = '$tahun'
    "));

    $nomorUrut = str_pad($cekNomor['total'], 3, '0', STR_PAD_LEFT);
    $nomorSurat = "B-" . $nomorUrut . "/Un.16/DST/PP.009/" . $tahun;

    mysqli_query($koneksi, "
        UPDATE surat_pengajuan
        SET nomor_surat = '$nomorSurat'
        WHERE id_surat = '$id_surat'
    ");
} else {
    $nomorSurat = $data['nomor_surat'];
}

$tanggalSurat = date('d-m-Y');

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
                    <td style="width: 70px;">Nomor</td>
                    <td style="width: 15px;">:</td>
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
                Kepada Yth,<br>
                <?= htmlspecialchars($data['surat_ditujukan'] ?? 'Pimpinan Instansi'); ?><br>
                di<br>
                <span class="indent-tempat">Tempat</span>
            </p>

            <p>Assalamu’alaikum Wr. Wb.</p>

            <p class="paragraf-isi">
                Bersama ini disampaikan permohonan izin untuk mengadakan Riset guna penulisan skripsi mahasiswa kami sebagai berikut:
            </p>

            <table class="tabel-data-mhs">
                <tr?>
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

    <div class="preview-actions">
        <?php
        $isViewMode = isset($_GET['view']);
        $roleUser = $_SESSION['role'] ?? '';

        if ($roleUser == 'pimpinan' && !$isViewMode) {
        ?>
            <form method="POST" style="margin: 0; padding: 0;">
                <button type="submit" name="kirim_balasan" class="btn-action btn-acc">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Surat
                </button>
            </form>
        <?php
        }

        if ($isViewMode) {
            $linkKembali = ($roleUser == 'pimpinan') ? 'pimpinan_riwayat.php' : 'mhs_riwayat.php';
        ?>
            <a href="<?= $linkKembali; ?>" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        <?php } ?>

        <button onclick="window.print()" class="btn-action btn-fill">
            <i class="fa-solid fa-print"></i> Unduh Surat
        </button>
    </div>
</body>

</html>