<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SESSION['role_admin'] !== 'admin2') {
    header("Location: index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

$prodi           = $_GET['prodi'] ?? '';
$id_jenis_filter = $_GET['id_jenis'] ?? '';
$keyword         = $_GET['keyword'] ?? '';
$detail_id       = $_GET['detail'] ?? '';

// ==========================================
// PROSES AKSI ADMIN (VALIDASI, REVISI, LANJUT)
// ==========================================
if (isset($_POST['aksi_admin'])) {
    $id_surat = (int) $_POST['id_surat'];
    $aksi     = $_POST['aksi_admin'];

    $validasi_dikirim = $_POST['validasi'] ?? [];

    $dataSurat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT sp.*, js.nama_surat
        FROM surat_pengajuan sp
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        WHERE sp.id_surat = '$id_surat'
    "));

    if ($dataSurat) {
        $q_semua_lampiran = mysqli_query($koneksi, "SELECT id_syarat FROM lampiran_pengajuan WHERE id_surat = '$id_surat'");

        while ($row_syarat = mysqli_fetch_assoc($q_semua_lampiran)) {
            $id_syarat = $row_syarat['id_syarat'];

            if (isset($validasi_dikirim[$id_syarat]) && $validasi_dikirim[$id_syarat] == 'Valid') {
                $status_aman = 'Valid';
            } else {
                $status_aman = 'Tidak Valid';
            }

            mysqli_query($koneksi, "
                UPDATE lampiran_pengajuan 
                SET status_validasi = '$status_aman' 
                WHERE id_surat = '$id_surat' AND id_syarat = '$id_syarat'
            ");
        }

        // ==========================================
        // EKSEKUSI AKSI (REVISI / LANJUT)
        // ==========================================
        if ($aksi == 'revisi') {
            $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan_penolakan']);
            mysqli_query($koneksi, "
                UPDATE surat_pengajuan
                SET status_akhir='Perbaikan', 
                    posisi_sekarang='Mahasiswa', 
                    alasan_penolakan='$alasan',
                    waktu_verif_admin=NOW()
                WHERE id_surat='$id_surat'
            ");
            $_SESSION['status'] = 'success';
            $_SESSION['pesan'] = 'Permohonan berhasil dikembalikan ke mahasiswa untuk diperbaiki';
            header("Location: adm_permohonan_akademik.php");
            exit;
        }

        if ($aksi == 'lanjut') {
            $namaSurat = strtolower($dataSurat['nama_surat']);

            if (strpos($namaSurat, 'riset') !== false || strpos($namaSurat, 'aktif') !== false || strpos($namaSurat, 'lulus') !== false || strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
                $queryUpdate = "UPDATE surat_pengajuan SET status_akhir='Menunggu Wadek 1', posisi_sekarang='Wadek 1', status_pimpinan='Menunggu', waktu_verif_admin=NOW() WHERE id_surat='$id_surat'";
            } elseif (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                $queryUpdate = "UPDATE surat_pengajuan SET status_akhir='Menunggu Wadek 1', posisi_sekarang='Wadek 1', tujuan_admin='admin2', status_pimpinan='Menunggu', waktu_verif_admin=NOW() WHERE id_surat='$id_surat'";
            } else {
                $queryUpdate = "UPDATE surat_pengajuan SET status_akhir='Menunggu Wadek 1', posisi_sekarang='Wadek 1', status_pimpinan='Menunggu', waktu_verif_admin=NOW() WHERE id_surat='$id_surat'";
            }

            mysqli_query($koneksi, $queryUpdate);
            $_SESSION['status'] = 'success';
            $_SESSION['pesan']  = 'Surat berhasil diteruskan ke pimpinan';
            header("Location: adm_riwayat_review.php");
            exit;
        }
    }
}

// ==========================================
// PROSES HAPUS PERMOHONAN
// ==========================================
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM surat_pengajuan WHERE id_surat = $id");
    $_SESSION['status'] = 'success';
    $_SESSION['pesan']  = 'Permohonan berhasil dihapus';
    header("Location: adm_permohonan_akademik.php");
    exit;
}

// ==========================================
// QUERY UNTUK TABEL UTAMA & PAGINATION
// ==========================================
$where = "WHERE sp.status_akhir = 'Menunggu Admin' AND sp.tujuan_admin = 'admin2' AND sp.id_mhs IS NOT NULL";

if ($prodi != "") {
    $prodiAman = mysqli_real_escape_string($koneksi, $prodi);
    $where .= " AND m.id_prodi = '$prodiAman'";
}
if ($id_jenis_filter != "") {
    $idJenisAman = (int) $id_jenis_filter;
    $where .= " AND sp.id_jenis = '$idJenisAman'";
}
if ($keyword != "") {
    $keywordAman = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (m.npm LIKE '%$keywordAman%' OR m.nama_mhs LIKE '%$keywordAman%')";
}

$limit = 10;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($halaman - 1) * $limit;

$query_count = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total 
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    $where
");
$total_data = mysqli_fetch_assoc($query_count)['total'];
$total_halaman = ceil($total_data / $limit);

$query = mysqli_query($koneksi, "
    SELECT sp.id_surat, sp.id_jenis, m.npm, m.nama_mhs, p.nama_prodi, js.nama_surat, sp.tanggal_pengajuan, sp.status_akhir
    FROM surat_pengajuan sp
    JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    $where 
    ORDER BY sp.tanggal_pengajuan DESC
    LIMIT $limit OFFSET $offset
");

$params = $_GET;
unset($params['page']);
$query_string = http_build_query($params);
if (!empty($query_string)) {
    $query_string = '&' . $query_string;
}

// ==========================================
// QUERY UNTUK MENAMPILKAN DETAIL
// ==========================================
$detail = null;
$lampiran = [];

if ($detail_id != "") {
    $detail_id = (int) $detail_id;
    $query_detail = mysqli_query($koneksi, "
        SELECT 
            sp.*, m.nama_mhs, m.npm, p.nama_prodi, js.nama_surat,
            dsr.judul_skripsi, dsr.lokasi_penelitian, 
            dsm.lokasi_magang, dsm.tanggal_mulai_magang, dsm.tanggal_selesai_magang,
            dak.lama_cuti, dak.ta_mulai_cuti, dak.ta_selesai_cuti, dak.tahun_akademik,
            dsl.tempat_lahir,
            dsl.tanggal_lahir,
            dsl.tahun_akademik AS tahun_akademik_lulus,
            dsl.tanggal_lulus,
            dsl.ipk,
            dsl.nilai_skripsi,
            dsl.predikat_kelulusan,
            dsl.keperluan AS keperluan_lulus,

            -- Detail SK Masih Kuliah (SKMK)
            dsk.tahun_akademik AS tahun_akademik_skmk,
            dsk.keperluan AS keperluan_skmk,
            dsk.nama_ortu,
            dsk.nip_ortu,
            dsk.instansi_ortu,
            dsk.alamat_ortu,

            COALESCE(dsm.surat_ditujukan, dsr.surat_ditujukan) AS surat_ditujukan,
            COALESCE(dsr.semester, dsm.semester, dak.semester, dsl.semester, dsk.semester) AS semester,
            COALESCE(dsl.keperluan, dsk.keperluan) AS keperluan
        FROM surat_pengajuan sp
        JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
        JOIN prodi p ON m.id_prodi = p.id_prodi
        JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
        LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
        LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
        LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
        LEFT JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat
        LEFT JOIN detail_skmk dsk ON sp.id_surat = dsk.id_surat
        WHERE sp.id_surat = '$detail_id'
    ");

    $detail = mysqli_fetch_assoc($query_detail);

    if ($detail) {
        $q_lampiran = mysqli_query($koneksi, "
            SELECT lp.id_syarat, ms.nama_syarat, lp.file_upload, lp.status_validasi 
            FROM lampiran_pengajuan lp
            JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
            WHERE lp.id_surat = '$detail_id'
        ");
        while ($row_lamp = mysqli_fetch_assoc($q_lampiran)) {
            $lampiran[] = $row_lamp;
        }
    }
}

function tgl_indo($tanggal)
{
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }

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

    $timestamp = strtotime($tanggal);
    if (!$timestamp) {
        return $tanggal;
    }

    $hari = date('d', $timestamp);
    $bulan = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . $array_bulan[$bulan] . ' ' . $tahun;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="admin-wrapper">
        <?php include "adm_sidebar.php"; ?>
        <main class="main-content">

            <?php if ($detail) {
                $namaSurat = strtolower($detail['nama_surat']);
            ?>
                <!-- HALAMAN DETAIL & VERIFIKASI -->
                <div class="table-card-table">
                    <h3 class="section-title-verif">Detail Permohonan Surat Mahasiswa</h3>

                    <table class="table-detail">
                        <tr>
                            <th>Jenis Surat</th>
                            <td><?= htmlspecialchars($detail['nama_surat']); ?></td>
                        </tr>
                        <tr>
                            <th>Nama Mahasiswa</th>
                            <td><?= htmlspecialchars($detail['nama_mhs']); ?></td>
                        </tr>
                        <tr>
                            <th>NPM</th>
                            <td><?= htmlspecialchars($detail['npm']); ?></td>
                        </tr>
                        <tr>
                            <th>Program Studi</th>
                            <td><?= htmlspecialchars($detail['nama_prodi']); ?></td>
                        </tr>
                        <tr>
                            <th>Semester</th>
                            <td><?= htmlspecialchars($detail['semester'] ?? '-'); ?></td>
                        </tr>

                        <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                            <tr>
                                <th>Judul Skripsi</th>
                                <td><?= htmlspecialchars($detail['judul_skripsi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi Penelitian</th>
                                <td><?= htmlspecialchars($detail['lokasi_penelitian'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Surat Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                            <tr>
                                <th>Lokasi Magang</th>
                                <td><?= htmlspecialchars($detail['lokasi_magang'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pelaksanaan</th>
                                <td>
                                    <?= tgl_indo($detail['tanggal_mulai_magang'] ?? ''); ?> s/d
                                    <?= tgl_indo($detail['tanggal_selesai_magang'] ?? ''); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Surat Ditujukan Kepada</th>
                                <td><?= htmlspecialchars($detail['surat_ditujukan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                            <tr>
                                <th>Lama Cuti</th>
                                <td><?= htmlspecialchars($detail['lama_cuti'] ?? '-'); ?> Semester</td>
                            </tr>
                            <tr>
                                <th>Periode Masa Cuti</th>
                                <td>Gasal: <?= htmlspecialchars($detail['ta_mulai_cuti'] ?? '-'); ?> <br>Genap: <?= htmlspecialchars($detail['ta_selesai_cuti'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tahun Akademik Aktif</th>
                                <td><?= htmlspecialchars($detail['tahun_akademik'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'lulus') !== false) { ?>
                            <tr>
                                <th>Tempat, Tanggal Lahir</th>
                                <td>
                                    <?= htmlspecialchars($detail['tempat_lahir'] ?? '-'); ?>,
                                    <?= (!empty($detail['tanggal_lahir']) && function_exists('tgl_indo')) ? tgl_indo($detail['tanggal_lahir']) : ($detail['tanggal_lahir'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Tahun Akademik Kelulusan</th>
                                <td><?= htmlspecialchars($detail['tahun_akademik_lulus'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Lulus (Munaqasah)</th>
                                <td><?= (!empty($detail['tanggal_lulus']) && function_exists('tgl_indo')) ? tgl_indo($detail['tanggal_lulus']) : ($detail['tanggal_lulus'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>IPK Terakhir</th>
                                <td><?= htmlspecialchars($detail['ipk'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Nilai Skripsi</th>
                                <td><?= htmlspecialchars($detail['nilai_skripsi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Predikat Kelulusan</th>
                                <td><?= htmlspecialchars($detail['predikat_kelulusan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Keperluan</th>
                                <td><?= htmlspecialchars($detail['keperluan'] ?? '-'); ?></td>
                            </tr>

                        <?php } else if (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) { ?>
                            <tr>
                                <th>Tahun Akademik</th>
                                <td><?= htmlspecialchars($detail['tahun_akademik_skmk'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Keperluan</th>
                                <td><?= htmlspecialchars($detail['keperluan'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Nama Orang Tua / Wali</th>
                                <td><?= htmlspecialchars($detail['nama_ortu'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>NIP Orang Tua</th>
                                <td><?= htmlspecialchars($detail['nip_ortu'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Instansi Orang Tua</th>
                                <td><?= htmlspecialchars($detail['instansi_ortu'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Alamat Orang Tua</th>
                                <td><?= nl2br(htmlspecialchars($detail['alamat_ortu'] ?? '-')); ?></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <th>Status Saat Ini</th>
                            <td class="status-text-amber"><?= htmlspecialchars($detail['status_akhir']); ?></td>
                        </tr>
                    </table>


                    <form method="POST" class="document-box" id="formVerifikasi">
                        <input type="hidden" name="id_surat" value="<?= $detail['id_surat']; ?>">
                        <input type="hidden" name="aksi_admin" id="aksiInput" value="">
                        <input type="hidden" name="alasan_penolakan" id="catatanInput" value="">

                        <h3 class="section-title mt-4">Dokumen Pendukung</h3>
                        <div class="document-box">
                            <p><i class="fa-solid fa-file-circle-check icon-spacing"></i> Periksa kelengkapan berkas di bawah ini dan ubah status validasinya jika diperlukan.</p>

                            <div class="document-buttons-custom">

                                <!-- Preview Surat Permohonan-->
                                <?php
                                $filePreview = "preview_surat_riset_mhs.php?id=" . $detail['id_surat'] . "&mode=view";

                                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                                    $filePreview = "preview_surat_magang_mhs.php?id=" . $detail['id_surat'] . "&mode=view";
                                } elseif (strpos($namaSurat, 'aktif') !== false) {
                                    $filePreview = "preview_sk_aktif_mhs.php?id=" . $detail['id_surat'] . "&mode=view";
                                } elseif (strpos($namaSurat, 'lulus') !== false) {
                                    $filePreview = "preview_sk_lulus_mhs.php?id=" . $detail['id_surat'] . "&mode=view";
                                } elseif (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
                                    $filePreview = "preview_skmk_mhs.php?id=" . $detail['id_surat'] . "&mode=view";
                                }
                                ?>
                                <div class="document-item-row document-item-draft">
                                    <a href="#" class="document-link-item" onclick="bukaPreview('<?= $filePreview; ?>')">
                                        <i class="fa-solid fa-file-lines document-icon-blue"></i> Surat Permohonan
                                    </a>
                                    <span class="document-system-note">Dihasilkan oleh sistem</span>
                                </div>

                                <!-- Looping Dinamis dari Tabel lampiran_pengajuan -->
                                <?php if (!empty($lampiran)) {
                                    foreach ($lampiran as $lamp) {
                                        $isChecked = ($lamp['status_validasi'] == 'Valid') ? 'checked' : '';
                                ?>
                                        <div class="document-item-row">
                                            <a href="#" class="document-link-item" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lamp['file_upload']); ?>')">
                                                <i class="fa-solid fa-paperclip document-icon-amber"></i> <?= htmlspecialchars($lamp['nama_syarat']); ?>
                                            </a>
                                            <label class="checkbox-label-valid">
                                                <input type="checkbox" name="validasi[<?= $lamp['id_syarat']; ?>]" value="Valid" class="checkbox-input-custom" <?= $isChecked; ?>>Sesuai
                                            </label>
                                        </div>
                                <?php
                                    }
                                } else {
                                    echo "<p class='text-error-doc'>Tidak ada dokumen lampiran tambahan.</p>";
                                }
                                ?>
                            </div>
                        </div>

                        <div class="action-panel-sec action-panel-custom-sec">
                            <?php
                            $asal = isset($_GET['asal']) ? $_GET['asal'] : '';

                            if ($asal == 'review') {
                                $link_kembali = 'adm_riwayat_review.php';
                            } elseif ($asal == 'laporan') {
                                $link_kembali = 'adm_laporan_surat.php';
                            } else {
                                $link_kembali = 'adm_permohonan_akademik.php';
                            }

                            $is_menunggu_admin = ($detail['status_akhir'] == 'Menunggu Admin');
                            ?>

                            <a href="<?= $link_kembali; ?>" class="btn-styled btn-back">Kembali</a>
                            <?php
                            if ($is_menunggu_admin):
                            ?>
                                <div class="action-buttons-group">
                                    <button type="button" class="btn-styled btn-reject-amber" onclick="konfirmasiRevisi()" title="Kembalikan untuk direvisi mahasiswa">
                                        Kembalikan Permohonan
                                    </button>
                                    <button type="button" class="btn-styled btn-approve" onclick="konfirmasiAksi('lanjut', 'Yakin ingin meneruskan permohonan surat ini ke pimpinan?', 'success')">
                                        Lanjutkan ke Pimpinan
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

            <?php } else { ?>

                <!-- HALAMAN TABEL DAFTAR PERMOHONAN -->
                <div class="page-title">
                    <h1>Kelola Surat Permohonan Mahasiswa</h1>
                </div>

                <div class="table-card-table">
                    <form method="GET" action="" class="filter-section">
                        <input type="text" name="keyword" placeholder="Cari NPM atau Nama..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                        <select name="prodi">
                            <option value="">Semua Prodi</option>
                            <?php
                            $queryProdi = mysqli_query($koneksi, "SELECT * FROM prodi ORDER BY nama_prodi ASC");
                            while ($prd = mysqli_fetch_assoc($queryProdi)) {
                                $selected = ($prodi == $prd['id_prodi']) ? 'selected' : '';
                                echo "<option value='" . $prd['id_prodi'] . "' $selected>" . htmlspecialchars($prd['nama_prodi']) . "</option>";
                            }
                            ?>
                        </select>
                        <select name="id_jenis">
                            <option value="">Semua Jenis Surat</option>
                            <?php
                            $jenisSurat = mysqli_query($koneksi, "SELECT * FROM jenis_surat WHERE nama_surat NOT LIKE '%Dana%' AND nama_surat NOT LIKE '%Ruangan%' ORDER BY nama_surat ASC");
                            while ($js = mysqli_fetch_assoc($jenisSurat)) {
                                $selected = ($id_jenis_filter == $js['id_jenis']) ? 'selected' : '';
                                echo "<option value='" . $js['id_jenis'] . "' $selected>" . htmlspecialchars($js['nama_surat']) . "</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn-filter"><i class="fa-solid fa-search"></i> Cari</button>
                        <a href="adm_permohonan_akademik.php" class="btn-reset-filter"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    </form>

                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal & Waktu Pengajuan</th>
                                <th>NPM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Program Studi</th>
                                <th>Jenis Surat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query && mysqli_num_rows($query) > 0) {
                                $no = ($offset ?? 0) + 1;
                                while ($row = mysqli_fetch_assoc($query)) {
                            ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengajuan'])); ?> WIB</td>
                                        <td><?= htmlspecialchars($row['npm']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_surat']); ?></td>
                                        <td><span class="badge-warning"><?= htmlspecialchars($row['status_akhir']); ?></span></td>
                                        <td>
                                            <div class="action-group-table">
                                                <a href="adm_permohonan_akademik.php?detail=<?= $row['id_surat']; ?>" class="btn btn-detail">Tindak Lanjut</a>
                                                <a href="#" class="btn btn-delete" onclick="hapusData(<?= $row['id_surat']; ?>)">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada permohonan yang perlu ditindaklanjuti.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <?php if (isset($total_halaman) && $total_halaman > 0): ?>
                        <div class="pagination-container">
                            <ul class="pagination">
                                <?php if ($halaman > 1): ?>
                                    <li><a href="?page=<?= $halaman - 1 ?><?= $query_string ?>">Sebelumnya</a></li>
                                <?php else: ?>
                                    <li class="disabled"><span>Sebelumnya</span></li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                    <?php if ($i == $halaman): ?>
                                        <li class="active"><span><?= $i ?></span></li>
                                    <?php else: ?>
                                        <li><a href="?page=<?= $i ?><?= $query_string ?>"><?= $i ?></a></li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($halaman < $total_halaman): ?>
                                    <li><a href="?page=<?= $halaman + 1 ?><?= $query_string ?>">Selanjutnya</a></li>
                                <?php else: ?>
                                    <li class="disabled"><span>Selanjutnya</span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php } ?>
        </main>
    </div>

    <!-- MODAL PREVIEW -->
    <div id="modalPreview" class="modal-preview-sec">
        <div class="modal-content-preview-sec">
            <span class="close-btn" onclick="tutupPreview()">&times;</span>
            <iframe id="previewFrame" class="iframe-preview"></iframe>
        </div>
    </div>

    <script>
        function bukaPreview(file) {
            document.getElementById('previewFrame').src = file;
            document.getElementById('modalPreview').style.display = 'flex';
        }

        function tutupPreview() {
            document.getElementById('modalPreview').style.display = 'none';
            document.getElementById('previewFrame').src = '';
        }

        function hapusData(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: 'Data permohonan ini akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_permohonan_akademik.php?hapus=' + id;
                }
            });
        }

        function konfirmasiRevisi() {
            Swal.fire({
                title: 'Catatan Revisi Kesalahan',
                input: 'textarea',
                inputPlaceholder: 'Sebutkan bagian data atau berkas yang salah agar diperbaiki oleh mahasiswa...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus menuliskan catatan atau alasan revisi terlebih dahulu!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('catatanInput').value = result.value;
                    document.getElementById('aksiInput').value = 'revisi';
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }

        function konfirmasiAksi(aksi, pesan, icon) {
            Swal.fire({
                title: 'Konfirmasi Tindakan',
                text: pesan,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('aksiInput').value = aksi;
                    document.getElementById('formVerifikasi').submit();
                }
            });
        }
    </script>
</body>

</html>