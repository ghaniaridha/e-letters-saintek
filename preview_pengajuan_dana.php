<?php
session_start();
include "koneksi.php";

$view_mode = false;
$status_pembina = "";

if (isset($_GET['id'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);

    $query_tampil = mysqli_query($koneksi, "
        SELECT sp.*, dpd.*, o.id_ormawa
        FROM surat_pengajuan sp
        JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
        JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
        WHERE sp.id_surat = '$id_surat'
    ");
    $data_kombinasi = mysqli_fetch_assoc($query_tampil);

    if (!$data_kombinasi) {
        die("Data pengajuan tidak ditemukan.");
    }

    $id_ormawa        = $data_kombinasi['id_ormawa'];
    $nomor_surat       = $data_kombinasi['nomor_surat'] ?? '-';
    $nama_kegiatan    = $data_kombinasi['nama_kegiatan'];
    $tempat_kegiatan    = $data_kombinasi['tempat_kegiatan'];
    $tanggal_kegiatan   = $data_kombinasi['tanggal_kegiatan'];
    $status_pembina   = $data_kombinasi['status_akhir'];

    $view_mode = true;
} else {
    if (!isset($_SESSION['id_ormawa'])) {
        header("Location:index.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        header("Location: ormawa_form_pengajuan_dana.php");
        exit;
    }

    $id_ormawa = $_SESSION['id_ormawa'];

    $folder_proposal = __DIR__ . "/uploads/proposal/";

    if (!is_dir($folder_proposal)) {
        @mkdir($folder_proposal, 0755, true);
    }

    function uploadFileSecure($field, $folder_upload, $allowed_ext, $allowed_mime)
    {
        $nama_asli = $_FILES[$field]['name'];
        $tmp_file = $_FILES[$field]['tmp_name'];
        $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_asli = finfo_file($finfo, $tmp_file);
        finfo_close($finfo);

        if (!in_array($ext, $allowed_ext) || !in_array($mime_asli, $allowed_mime)) {
            echo "<script>alert('Format file proposal tidak valid atau telah dimanipulasi!'); history.back();</script>";
            exit;
        }

        $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

        if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
            echo "<script>alert('Gagal upload file. Silakan coba lagi.'); history.back();</script>";
            exit;
        }

        return $nama_baru;
    }

    $ext_pdf = ['pdf'];
    $mime_pdf = ['application/pdf'];

    $nama_proposal = "";
    if (isset($_FILES['proposal']) && $_FILES['proposal']['error'] == 0) {
        $nama_proposal = uploadFileSecure('proposal', $folder_proposal, $ext_pdf, $mime_pdf);
    }

    $nomor_surat        = htmlspecialchars($_POST['nomor_surat']);
    $nama_kegiatan      = htmlspecialchars($_POST['nama_kegiatan']);
    $tempat_kegiatan    = htmlspecialchars($_POST['tempat_kegiatan']);
    $tanggal_kegiatan   = htmlspecialchars($_POST['tanggal_kegiatan']);
}

$ormawa = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT o.*, 
           p.nama_prodi,
           p.id_kaprodi,
           d_ukm.nama_dosen AS nama_pembina_ukm,
           d_ukm.nip AS nip_pembina_ukm,
           d_ukm.kode_ttd_qr AS qr_pembina_ukm,
           d_kaprodi.nama_dosen AS nama_kaprodi,
           d_kaprodi.nip AS nip_kaprodi,
           d_kaprodi.kode_ttd_qr AS qr_kaprodi
    FROM ormawa o
    LEFT JOIN prodi p ON o.id_prodi = p.id_prodi
    LEFT JOIN dosen d_ukm ON o.id_pembina = d_ukm.id_dosen
    LEFT JOIN dosen d_kaprodi ON p.id_kaprodi = d_kaprodi.id_dosen
    WHERE o.id_ormawa='$id_ormawa'
"));

if ($ormawa['jenis_organisasi'] == 'Ormawa') {
    $nama_penanggung_jawab = $ormawa['nama_kaprodi'];
    $nip_penanggung_jawab = $ormawa['nip_kaprodi'];
    $qr_penanggung_jawab = $ormawa['qr_kaprodi'];
    $jabatan_surat = "KETUA PROGRAM STUDI " . strtoupper(htmlspecialchars($ormawa['nama_prodi'] ?? ''));
} else {
    $nama_penanggung_jawab = $ormawa['nama_pembina_ukm'];
    $nip_penanggung_jawab = $ormawa['nip_pembina_ukm'];
    $qr_penanggung_jawab = $ormawa['qr_pembina_ukm'];
    $jabatan_surat = "DOSEN PEMBINA " . strtoupper(htmlspecialchars($ormawa['singkatan_ormawa'] ?? 'ORMAWA'));
}

$hari_array = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$array_bulan = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];
$tanggal_surat = date('d') . ' ' . $array_bulan[(int)date('m')] . ' ' . date('Y');
$timestamp = strtotime($tanggal_kegiatan ?? 'now');
$tanggal_indo = date('d', $timestamp) . ' ' . $array_bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
$hari_index = date('w', strtotime($tanggal_kegiatan ?? 'now'));
$hari_indo = $hari_array[$hari_index];

$host = $_SERVER['HTTP_HOST'];
$local_ip = gethostbyname(gethostname());

if ($host == 'localhost' || $host == '127.0.0.1') {
    $host = $local_ip;
}
if (strpos($host, '.') !== false && strpos($host, 'localhost') === false && !filter_var($host, FILTER_VALIDATE_IP)) {
    $base_url = "https://" . $host;
} else {
    $base_url = "http://" . $host . "/e-letters-saintek";
}

$link_ketua = $base_url . "/verifikasi.php?hash=" . ($ormawa['kode_ttd_qr_ketua'] ?? '');
$qr_ketua = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_ketua);

$link_sekretaris = $base_url . "/verifikasi.php?hash=" . ($ormawa['kode_ttd_qr_sekretaris'] ?? '');
$qr_sekretaris = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_sekretaris);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Surat</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="preview.css?v=<?= time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="ormawa-surat-wrapper">
        <div class="ormawa-kop">
            <div class="ormawa-logo-kiri">
                <img src="images/Logo UINRIL(2).png" alt="Logo UIN">
            </div>
            <div class="ormawa-judul-kop">
                <h3><?= strtoupper(htmlspecialchars($ormawa['nama_ormawa'] ?? 'HIMPUNAN MAHASISWA')); ?></h3>
                <h3>FAKULTAS SAINS DAN TEKNOLOGI</h3>
                <h3>UNIVERSITAS ISLAM NEGERI RADEN INTAN LAMPUNG</h3>
                <p>Sekretariat: <?= htmlspecialchars($ormawa['alamat_sekretariat'] ?? 'Jl. Letkol H. Endro Suratmin Sukarame Bandar Lampung'); ?><br>
                    No. Telp: <?= htmlspecialchars($ormawa['kontak_ormawa'] ?? '-'); ?> Email: <?= htmlspecialchars($ormawa['email_ormawa'] ?? '-'); ?></p>
            </div>
            <div class="ormawa-logo-kanan">
                <?php if (!empty($ormawa['logo_ormawa']) && file_exists("images/" . $ormawa['logo_ormawa'])): ?>
                    <img src="images/<?= htmlspecialchars($ormawa['logo_ormawa']); ?>" alt="Logo Ormawa">
                <?php else: ?>
                    <img src="images/Logo UINRIL(2).png" alt="Logo Default">
                <?php endif; ?>
            </div>
        </div>

        <div class="ormawa-garis-kop"></div>

        <table class="ormawa-detail-surat">
            <tr>
                <td>Nomor</td>
                <td>:</td>
                <td><?= htmlspecialchars($nomor_surat ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td><b><u>Permohonan Bantuan Dana </u></b></td>
            </tr>
            <tr>
                <td>Lampiran</td>
                <td>:</td>
                <td>1 (satu) Berkas Proposal</td>
            </tr>
        </table>

        <div class="ormawa-tujuan-surat">
            Kepada Yth,<br>
            <b>Wakil Dekan II Fakultas Sains dan Teknologi</b><br>
            <b>Universitas Islam Negeri Raden Intan Lampung</b><br>
            Di -<br>
            <span class="ormawa-tempat">Tempat</span>
        </div>

        <div class="ormawa-salutation">Assalammu'alaikum Warahmatullahi Wabarakatuh</div>

        <div class="ormawa-paragraf">
            Teriring salam dan do'a semoga Allah SWT. senantiasa melimpahkan rahmat dan hidayah-Nya kepada kita semua dalam menjalankan aktivitas sehari-hari.
        </div>

        <div class="ormawa-paragraf">
            Sehubungan akan dilaksanakannya kegiatan <b><?= htmlspecialchars($nama_kegiatan ?? '') ?></b> oleh <?= htmlspecialchars($ormawa['nama_ormawa'] ?? '') ?> Fakultas Sains Dan Teknologi UIN Raden Intan Lampung, yang akan dilaksanakan pada:
        </div>

        <table class="ormawa-table-info">
            <tr>
                <td class="col-label">Hari, Tanggal</td>
                <td class="col-separator">:</td>
                <td><?= $hari_indo ?>, <?= $tanggal_indo; ?></td>
            </tr>
            <tr>
                <td class="col-label">Tempat Kegiatan</td>
                <td class="col-separator">:</td>
                <td><?= htmlspecialchars($tempat_kegiatan) ?></td>
            </tr>
            <tr>
                <td class="col-label">Nama Kegiatan</td>
                <td class="col-separator">:</td>
                <td><?= htmlspecialchars($nama_kegiatan) ?></td>
            </tr>
        </table>

        <div class="ormawa-paragraf">
            Maka dengan ini kami bermaksud mengajukan permohonan bantuan dana kepada Bapak/Ibu Wakil Dekan II Fakultas Sains dan Teknologi Universitas Islam Negeri Raden Intan Lampung. Sebagai bahan pertimbangan, bersama surat ini turut kami lampirkan 1 (satu) berkas proposal yang memuat deskripsi rincian kegiatan beserta Rencana Anggaran Biaya (RAB).
        </div>

        <div class="ormawa-paragraf">
            Demikian surat permohonan ini kami sampaikan. Atas perhatian, dukungan, dan perkenan Bapak/Ibu, kami ucapkan terima kasih.
        </div>

        <div class="ormawa-salutation">Wassalammu'alaikum Warahmatullahi Wabarakatuh</div>

        <div class="ormawa-ttd-section">
            <div class="ormawa-tanggal-surat">
                Bandar Lampung, <?= $tanggal_surat; ?>
            </div>

            <div class="ormawa-ttd-container">
                <div class="ormawa-ttd-box">
                    <span>Ketua Umum</span>
                    <img src="<?= $qr_ketua ?>" alt="QR Ketua">
                    <span class="ormawa-nama-ttd"><?= htmlspecialchars($ormawa['nama_ketua'] ?? '') ?></span>
                    <span>NPM. <?= htmlspecialchars($ormawa['npm_ketua'] ?? '') ?></span>
                </div>

                <div class="ormawa-ttd-box">
                    <span>Sekretaris Umum</span>
                    <img src="<?= $qr_sekretaris ?>" alt="QR Sekretaris">
                    <span class="ormawa-nama-ttd"><?= htmlspecialchars($ormawa['nama_sekretaris'] ?? '') ?></span>
                    <span>NPM. <?= htmlspecialchars($ormawa['npm_sekretaris'] ?? '') ?></span>
                </div>
            </div>

            <div class="ormawa-ttd-pembina">
                <div>Mengetahui,</div>
                <div class="ormawa-jabatan-pembina"><?= $jabatan_surat ?></div>

                <?php
                $status_lower = strtolower($status_pembina);
                $is_ditolak = (strpos($status_lower, 'ditolak') !== false);
                $is_disetujui = ($status_pembina !== "Menunggu Persetujuan Pembina" && !empty($status_pembina) && !$is_ditolak);

                if ($view_mode && $is_disetujui && !empty($qr_penanggung_jawab)) {
                    $link_pembina = $base_url . "/verifikasi.php?hash=" . $qr_penanggung_jawab;
                    $qr_pembina_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_pembina);
                ?>
                    <img src="<?= $qr_pembina_url ?>" alt="QR Penanggung Jawab">
                <?php } else { ?>
                    <div class="ormawa-qr-placeholder" style="<?= $is_ditolak ? 'color: #dc2626; border-color: #dc2626;' : '' ?>">
                        <?= $is_ditolak ? 'Ditolak' : 'QR Belum Tersedia' ?>
                    </div>
                <?php } ?>

                <span class="ormawa-nama-ttd"><?= htmlspecialchars($nama_penanggung_jawab ?? '-') ?></span>
                <span>NIP. <?= htmlspecialchars($nip_penanggung_jawab ?? '-') ?></span>
            </div>
        </div>
    </div>

    <?php if (!$view_mode) { ?>
        <div class="preview-actions">
            <div class="action-buttons">
                <button class="btn-action btn-outline" type="button" onclick="history.back()">Edit Kembali</button>
                <form action="proses_pengajuan_dana.php" method="POST" onsubmit="confirmAjukanSurat(event)">

                    <input type="hidden"
                        name="id_ormawa"
                        value="<?= $id_ormawa ?>">

                    <input type="hidden"
                        name="id_jenis"
                        value="<?= $_POST['id_jenis'] ?>">

                    <input type="hidden"
                        name="id_pembina"
                        value="<?= $ormawa['id_pembina'] ?>">

                    <input type="hidden"
                        name="nomor_surat"
                        value="<?= htmlspecialchars($nomor_surat); ?>">

                    <input type="hidden"
                        name="file_proposal_terupload"
                        value="<?= $nama_proposal ?>">

                    <input type="hidden"
                        name="nama_kegiatan"
                        value="<?= htmlspecialchars($nama_kegiatan) ?>">

                    <input type="hidden"
                        name="tanggal_kegiatan"
                        value="<?= htmlspecialchars($tanggal_kegiatan) ?>">

                    <input type="hidden"
                        name="tempat_kegiatan"
                        value="<?= htmlspecialchars($tempat_kegiatan) ?>">

                    <button class="btn-action btn-fill" type="submit">
                        Ajukan Surat
                    </button>
                </form>
            </div>
        </div>
    <?php } ?>

    <script>
        function confirmAjukanSurat(event) {
            event.preventDefault();
            const form = event.target;

            Swal.fire({
                title: 'Konfirmasi Pengajuan Surat',
                text: "Pastikan data sudah benar. Data tidak dapat diubah setelah dikirim.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Ajukan Surat',
                cancelButtonText: 'Periksa Kembali',
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>