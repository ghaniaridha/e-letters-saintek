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
        dsm.semester,              
        dsm.lokasi_magang,
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        dsm.surat_ditujukan
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi 
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat 
    WHERE sp.id_surat = '$id_surat'
"));

if (!$data) {
    echo "<script>alert('Data surat tidak ditemukan'); window.location='pimpinan_verif.php';</script>";
    exit;
}

$query_dekan = mysqli_query($koneksi, "
    SELECT nama_dosen, nip 
    FROM dosen 
    WHERE jabatan = 'Dekan' OR jabatan LIKE 'Dekan Fakultas%' 
    LIMIT 1
");

$data_dekan = mysqli_fetch_assoc($query_dekan);

$nama_dekan = $data_dekan['nama_dosen'] ?? 'Nama Dekan Belum Diatur';
$nip_dekan  = $data_dekan['nip'] ?? '-';

if (isset($_POST['kirim_balasan'])) {
    $nama_file = "surat_resmi_izin_magang_" . $id_surat . "_" . time() . ".pdf";

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

    header("Location: adm_riwayat_review.php");
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

$tglMulai = !empty($data['tanggal_mulai_magang'])
    ? date('d', strtotime($data['tanggal_mulai_magang'])) . ' ' .
    $bulanIndo[date('m', strtotime($data['tanggal_mulai_magang']))] . ' ' .
    date('Y', strtotime($data['tanggal_mulai_magang']))
    : '____________';

$tglSelesai = !empty($data['tanggal_selesai_magang'])
    ? date('d', strtotime($data['tanggal_selesai_magang'])) . ' ' .
    $bulanIndo[date('m', strtotime($data['tanggal_selesai_magang']))] . ' ' .
    date('Y', strtotime($data['tanggal_selesai_magang']))
    : '____________';

$base_url = "http://localhost/e-letters-saintek";

$link_verifikasi = $base_url . "/verifikasi_surat.php?hash=" . $data['dokumen_hash'];

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_verifikasi);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Balasan Magang</title>
    <link rel="stylesheet" href="preview.css?v=<?= time(); ?>">
    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />

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
            <div class="kop-surat">
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
            </div>

            <table class="info-surat-magang">
                <tr>
                    <td>Nomor</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($nomorSurat); ?></td>
                    <td class="spacer-info-surat"></td>
                    <td>Bandar Lampung, <?= $tanggalSurat; ?></td>
                </tr>
                <tr>
                    <td>Sifat</td>
                    <td>:</td>
                    <td>Penting</td>
                </tr>
                <tr>
                    <td>Lampiran</td>
                    <td>:</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Perihal</td>
                    <td>:</td>
                    <td><strong>Permohonan Magang</strong></td>
                </tr>
            </table>

            <br>

            <p>
                Kepada Yth,<br><b>
                    <?= htmlspecialchars($data['surat_ditujukan']); ?></b><br>
                di<br>
                <span class="indent-tempat">Tempat</span>
            </p>

            <p>Assalamu’alaikum Wr. Wb.</p>

            <p class="isi">
                Dalam rangka peningkatan standar kompetensi dan kualitas mahasiswa jurusan
                <?= htmlspecialchars($data['nama_prodi']); ?> Fakultas Sains dan Teknologi
                UIN Raden Intan Lampung dengan keterampilan manajerial yang relevan dan sesuai
                dengan perkembangan sosial dunia usaha,
            </p>

            <p class="isi">
                Maka kami mohon kepada Bapak/Ibu kiranya berkenan menerima mahasiswa kami
                melaksanakan magang pada lembaga yang Bapak/Ibu pimpin terhitung mulai tanggal
                <?= $tglMulai; ?> s/d <?= $tglSelesai; ?>.
            </p>

            <p>Adapun nama mahasiswa sebagaimana tercantum di bawah ini:</p>

            <table class="tabel-mhs">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>Nama</th>
                        <th>NPM</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
                        <td><?= htmlspecialchars($data['npm']); ?></td>
                    </tr>
                </tbody>
            </table>

            <p>
                Demikian permohonan ini, atas perhatian dan kerjasamanya diucapkan terimakasih.
            </p>

            <p>Wassalamu’alaikum Wr. Wb.</p>

            <div class="ttd">
                <p>Dekan,</p>

                <?php if (!empty($data['dokumen_hash'])) { ?>
                    <img src="<?= $qr_url; ?>" class="qr-ttd" alt="QR Verifikasi">
                <?php } else { ?>
                    <br><br><br>
                <?php } ?>

                <p>
                    <strong><?= htmlspecialchars($nama_dekan); ?></strong><br>
                    NIP. <?= htmlspecialchars($nip_dekan); ?>
                </p>
            </div>

            <br>

            <p class="tembusan-surat">
                <u>Tembusan:</u><br>
                1. Wakil Dekan Bidang Akademik;<br>
                2. Kajur/Kaprodi <?= htmlspecialchars($data['nama_prodi']); ?><br>
                3. Kasubag Akademik;<br>
                4. Mahasiswa yang bersangkutan
            </p>

        </div>

        <div class="action">
            <?php
            $asal_halaman = $_GET['asal'] ?? '';

            if ($asal_halaman == 'laporan') {
                $link_kembali = 'adm_laporan_surat.php';
            } elseif ($asal_halaman == 'pimpinan') {
                $link_kembali = 'pimpinan_riwayat.php';
            } elseif ($asal_halaman == 'review') {
                $link_kembali = 'adm_riwayat_review.php';
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