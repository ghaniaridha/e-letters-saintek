<?php
session_start();
include "koneksi.php";

$view_mode = false;
$status_pembina = "";

if (isset($_GET['id'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);

    $query_tampil = mysqli_query($koneksi, "
        SELECT sp.*, dpr.*, o.id_ormawa
        FROM surat_pengajuan sp
        LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
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
    $ruangan          = $data_kombinasi['ruangan_yang_diajukan'];
    $tanggal_kegiatan = $data_kombinasi['tanggal_mulai'];
    $jam_mulai        = $data_kombinasi['jam_mulai'];
    $jam_selesai      = $data_kombinasi['jam_selesai'];
    $status_pembina   = $data_kombinasi['status_akhir'];

    $view_mode = true;
} else {
    if (!isset($_SESSION['id_ormawa'])) {
        header("Location:index.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        header("Location: ormawa_form_peminjaman_ruangan.php");
        exit;
    }

    $id_ormawa = $_SESSION['id_ormawa'];
    $folder_proposal = __DIR__ . "/uploads/proposal/";
    $folder_surat = __DIR__ . "/uploads/surat_permohonan/";

    if (!is_dir($folder_proposal)) {
        @mkdir($folder_proposal, 0755, true);
    }
    if (!is_dir($folder_surat)) {
        @mkdir($folder_surat, 0755, true);
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
            echo "<script>alert('Format file " . $field . " tidak valid!'); history.back();</script>";
            exit;
        }

        $nama_baru = $field . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;

        if (!move_uploaded_file($tmp_file, $folder_upload . $nama_baru)) {
            echo "<script>alert('Gagal upload file " . $field . ". Silakan coba lagi.'); history.back();</script>";
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

    $nama_surat_permohonan = "";
    if (isset($_FILES['surat_permohonan']) && $_FILES['surat_permohonan']['error'] == 0) {
        $nama_surat_permohonan = uploadFileSecure('surat_permohonan', $folder_surat, $ext_pdf, $mime_pdf);
    }

    $nomor_surat        = htmlspecialchars($_POST['nomor_surat']);
    $nama_kegiatan    = htmlspecialchars($_POST['nama_kegiatan']);
    $ruangan          = htmlspecialchars($_POST['ruangan']);
    $tanggal_kegiatan = htmlspecialchars($_POST['tanggal_kegiatan']);
    $jam_mulai        = htmlspecialchars($_POST['jam_mulai']);
    $jam_selesai      = htmlspecialchars($_POST['jam_selesai']);
}

$ormawa = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT o.*, d.nama_dosen AS nama_pembina,
           d.nip AS nip_pembina,
           d.kode_ttd_qr AS qr_pembina
    FROM ormawa o
    LEFT JOIN dosen d ON o.id_pembina=d.id_dosen
    WHERE o.id_ormawa='$id_ormawa'
"));

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
                    No. Telp: <?= htmlspecialchars($ormawa['kontak_ormawa'] ?? '-'); ?> | Email: <?= htmlspecialchars($ormawa['email_ormawa'] ?? '-'); ?></p>
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
                <td><b><u>Permohonan Peminjaman <?= htmlspecialchars($ruangan ?? 'Ruangan') ?></u></b></td>
            </tr>
            <tr>
                <td>Lampiran</td>
                <td>:</td>
                <td>1 (satu) Berkas Proposal</td>
            </tr>
        </table>

        <div class="ormawa-tujuan-surat">
            Kepada Yth,<br>
            <b>Bapak/Ibu Kasubbag Umum Fakultas Sains dan Teknologi</b><br>
            <b>Universitas Islam Negeri Raden Intan Lampung</b><br>
            di -<br>
            <span class="ormawa-tempat">Tempat</span>
        </div>

        <div class="ormawa-salutation">Assalamu'alaikum Warahmatullahi Wabarakatuh</div>

        <div class="ormawa-paragraf">
            Teriring salam dan do'a semoga Allah SWT. senantiasa melimpahkan rahmat dan hidayah-Nya kepada kita semua dalam menjalankan aktivitas sehari-hari.
        </div>

        <div class="ormawa-paragraf">
            Sehubungan akan dilaksanakannya kegiatan <b><?= htmlspecialchars($nama_kegiatan ?? '') ?></b> oleh <?= htmlspecialchars($ormawa['nama_ormawa'] ?? '') ?> Fakultas Sains dan Teknologi UIN Raden Intan Lampung, yang akan dilaksanakan pada:
        </div>

        <table class="ormawa-table-info">
            <tr>
                <td class="col-label">Hari, Tanggal</td>
                <td class="col-separator">:</td>
                <td><?= $hari_indo ?>, <?= $tanggal_indo; ?></td>
            </tr>
            <tr>
                <td class="col-label">Waktu</td>
                <td class="col-separator">:</td>
                <td><?= htmlspecialchars($jam_mulai ?? '') ?> WIB s.d. <?= htmlspecialchars($jam_selesai ?? '') ?> WIB</td>
            </tr>
            <tr>
                <td class="col-label">Tempat</td>
                <td class="col-separator">:</td>
                <td><?= htmlspecialchars($ruangan ?? '') ?></td>
            </tr>
        </table>

        <div class="ormawa-paragraf">
            Maka dengan ini kami bermaksud memohon izin untuk meminjam <?= htmlspecialchars($ruangan ?? '') ?> Fakultas Sains dan Teknologi guna menunjang kelancaran pelaksanaan kegiatan tersebut. Sebagai kelengkapan dan bahan pertimbangan, bersama surat ini turut kami lampirkan 1 (satu) berkas proposal kegiatan.
        </div>

        <div class="ormawa-paragraf">
            Demikian surat permohonan ini kami sampaikan. Atas perhatian dan izin yang Bapak/Ibu berikan, kami ucapkan terima kasih.
        </div>

        <div class="ormawa-salutation">Wassalamu'alaikum Warahmatullahi Wabarakatuh</div>

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
                <div class="ormawa-jabatan-pembina">DOSEN PEMBINA <?= strtoupper(htmlspecialchars($ormawa['singkatan_ormawa'] ?? 'ORMAWA')) ?></div>

                <?php
                $status_lower = strtolower($status_pembina);
                $is_ditolak = (strpos($status_lower, 'ditolak') !== false);

                $is_disetujui = ($status_pembina !== "Menunggu Persetujuan Pembina" && !empty($status_pembina) && !$is_ditolak);

                if ($view_mode && $is_disetujui && !empty($ormawa['qr_pembina'])) {
                    $link_pembina = $base_url . "/verifikasi.php?hash=" . $ormawa['qr_pembina'];
                    $qr_pembina_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($link_pembina);
                ?>
                    <img src="<?= $qr_pembina_url ?>" alt="QR Pembina">
                <?php } else { ?>
                    <div class="ormawa-qr-placeholder" style="<?= $is_ditolak ? 'color: #dc2626; border-color: #dc2626;' : '' ?>">
                        <?= $is_ditolak ? 'Ditolak' : 'QR Belum Tersedia' ?>
                    </div>
                <?php } ?>

                <span class="ormawa-nama-ttd"><?= htmlspecialchars($ormawa['nama_pembina'] ?? '-') ?></span>
                <span>NIP. <?= htmlspecialchars($ormawa['nip_pembina'] ?? '-') ?></span>
            </div>
        </div>
    </div>

    <?php if (!$view_mode) { ?>
        <div class="preview-actions">
            <div class="action-buttons">
                <button class="btn-action btn-outline" type="button" onclick="history.back()">Edit Kembali</button>
                <form action="proses_peminjaman_ruangan.php" method="POST" onsubmit="confirmAjukanSurat(event)">

                    <input type="hidden" name="id_pembina" value="<?= htmlspecialchars($ormawa['id_pembina'] ?? '') ?>">
                    <input type="hidden" name="file_proposal_terupload" value="<?= $nama_proposal; ?>">

                    <?php
                    foreach ($_POST as $key => $value) {
                        if ($key == 'id_pembina' || $key == 'file_proposal_terupload' || $key == 'file_surat_terupload') continue;
                        echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
                    }
                    ?>
                    <button class="btn-action btn-fill" type="submit">Ajukan Surat</button>
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