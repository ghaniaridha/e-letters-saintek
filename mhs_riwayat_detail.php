<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_mhs'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='index.php';</script>";
    exit;
}

$id_mhs = $_SESSION['id_mhs'];
$namaLengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Pengguna';
$idLogin = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'ROLE';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query_detail = mysqli_query($koneksi, "
    SELECT
        sp.*,
        m.nama_mhs,
        m.npm,
        p.nama_prodi,
        js.nama_surat,
        
        -- Detail Surat Riset
        dsr.judul_skripsi,
        dsr.lokasi_penelitian,
        dsr.catatan_pb1,
        dsr.catatan_pb2,
        
        -- Detail Surat Magang
        dsm.lokasi_magang,
        dsm.tanggal_mulai_magang,
        dsm.tanggal_selesai_magang,
        
        -- Detail SK Aktif Kuliah Kembali
        dak.lama_cuti,
        dak.ta_mulai_cuti,
        dak.ta_selesai_cuti,
        dak.tahun_akademik,
        dak.catatan_pa,

        -- Detail SK Lulus
        dsl.tempat_lahir,
        dsl.tanggal_lahir,
        dsl.tahun_akademik AS tahun_akademik_lulus,
        dsl.tanggal_lulus,
        dsl.ipk,
        dsl.nilai_skripsi,
        dsl.predikat_kelulusan,
        dsl.keperluan,
        
        dsk.tahun_akademik AS tahun_akademik_skmk,
        dsk.keperluan AS keperluan_skmk,
        dsk.nama_ortu,
        dsk.nip_ortu,
        dsk.instansi_ortu,
        dsk.alamat_ortu,
        
        COALESCE(dsr.semester, dsm.semester, dak.semester, dsl.semester, dsk.semester) AS semester,
        COALESCE(dsr.surat_ditujukan, dsm.surat_ditujukan) AS surat_ditujukan,
        COALESCE(dsl.keperluan, dsk.keperluan) AS keperluan
    FROM surat_pengajuan sp
    LEFT JOIN mahasiswa m ON sp.id_mhs = m.id_mhs
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    LEFT JOIN detail_surat_riset dsr ON sp.id_surat = dsr.id_surat
    LEFT JOIN detail_surat_magang dsm ON sp.id_surat = dsm.id_surat
    LEFT JOIN detail_aktif_kuliah dak ON sp.id_surat = dak.id_surat
    LEFT JOIN detail_sk_lulus dsl ON sp.id_surat = dsl.id_surat
    LEFT JOIN detail_skmk dsk ON sp.id_surat = dsk.id_surat
    WHERE sp.id_surat = '$id_surat' AND sp.id_mhs = '$id_mhs'
");

$data = mysqli_fetch_assoc($query_detail);

if (!$data) {
    echo "<script>alert('Data permohonan tidak ditemukan atau Anda tidak memiliki hak akses.'); window.location='mhs_riwayat.php';</script>";
    exit;
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

$namaSurat = strtolower($data['nama_surat']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Riwayat Permohonan</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="adm.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/logo2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="mhs_beranda.php#home">Beranda</a>
            <a href="mhs_beranda.php#services">Pengajuan Surat</a>
            <a href="mhs_beranda.php#status-info">Status & Informasi</a>
            <a href="mhs_riwayat.php">Riwayat Pengajuan</a>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial) ?></span>
                </button>
                <div id="user-dropdown" class="dropdown-menu">
                    <a href="mhs_profile.php" class="user-info-link-mhs">
                        <div class="user-info-mhs">
                            <span class="user-name-mhs"><?= htmlspecialchars($namaLengkap) ?></span>
                            <span class="user-role-mhs"><?= htmlspecialchars($idLogin) ?> - <?= htmlspecialchars($role) ?></span>
                        </div>
                    </a>
                    <div class="divider"></div>
                    <a href="logout.php" class="logout-btn" onclick="confirmLogout(event, this.href)">
                        <span>Keluar</span>
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="table-card">
        <h3 class="section-title-verif">Detail Permohonan Surat Akademik</h3>

        <table class="table-detail">
            <tr>
                <th>Kode Lacak Surat</th>
                <td>
                    <span class="badge-kode-lacak-detail">
                        <?= htmlspecialchars($data['kode_pelacakan'] ?? 'Belum Tersedia'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Jenis Surat</th>
                <td><?= htmlspecialchars($data['nama_surat']); ?></td>
            </tr>
            <tr>
                <th>Nama Mahasiswa</th>
                <td><?= htmlspecialchars($data['nama_mhs']); ?></td>
            </tr>
            <tr>
                <th>NPM</th>
                <td><?= htmlspecialchars($data['npm']); ?></td>
            </tr>
            <tr>
                <th>Program Studi</th>
                <td><?= htmlspecialchars($data['nama_prodi']); ?></td>
            </tr>
            <tr>
                <th>Semester</th>
                <td><?= htmlspecialchars($data['semester'] ?? '-'); ?></td>
            </tr>

            <!-- DETAIL SPESIFIK JENIS SURAT AKADEMIK -->
            <?php if (strpos($namaSurat, 'riset') !== false) { ?>
                <tr>
                    <th>Judul Skripsi</th>
                    <td><?= htmlspecialchars($data['judul_skripsi'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Lokasi Penelitian</th>
                    <td><?= htmlspecialchars($data['lokasi_penelitian'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Surat Ditujukan Kepada</th>
                    <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                </tr>
            <?php } else if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) { ?>
                <tr>
                    <th>Lokasi Magang</th>
                    <td><?= htmlspecialchars($data['lokasi_magang'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal Pelaksanaan</th>
                    <td>
                        <?= tgl_indo($data['tanggal_mulai_magang'] ?? ''); ?> s/d
                        <?= tgl_indo($data['tanggal_selesai_magang'] ?? ''); ?>
                    </td>
                </tr>
                <tr>
                    <th>Surat Ditujukan Kepada</th>
                    <td><?= htmlspecialchars($data['surat_ditujukan'] ?? '-'); ?></td>
                </tr>
            <?php } else if (strpos($namaSurat, 'aktif') !== false) { ?>
                <tr>
                    <th>Lama Cuti</th>
                    <td><?= htmlspecialchars($data['lama_cuti'] ?? '-'); ?> </td>
                </tr>
                <tr>
                    <th>Periode Masa Cuti</th>
                    <td>
                        Gasal: <?= htmlspecialchars($data['ta_mulai_cuti'] ?? '-'); ?> <br>
                        Genap: <?= htmlspecialchars($data['ta_selesai_cuti'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <th>Tahun Akademik Aktif</th>
                    <td><?= htmlspecialchars($data['tahun_akademik'] ?? '-'); ?></td>
                </tr>
            <?php } else if (strpos($namaSurat, 'lulus') !== false) { ?>
                <tr>
                    <th>Tempat, Tanggal Lahir</th>
                    <td>
                        <?= htmlspecialchars($data['tempat_lahir'] ?? '-'); ?>,
                        <?= (!empty($data['tanggal_lahir']) && function_exists('tgl_indo')) ? tgl_indo($data['tanggal_lahir']) : ($data['tanggal_lahir'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <th>Tahun Akademik Kelulusan</th>
                    <td><?= htmlspecialchars($data['tahun_akademik_lulus'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal Lulus (Munaqasah)</th>
                    <td><?= (!empty($data['tanggal_lulus']) && function_exists('tgl_indo')) ? tgl_indo($data['tanggal_lulus']) : ($data['tanggal_lulus'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>IPK Terakhir</th>
                    <td><?= htmlspecialchars($data['ipk'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Nilai Skripsi</th>
                    <td><?= htmlspecialchars($data['nilai_skripsi'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Predikat Kelulusan</th>
                    <td><?= htmlspecialchars($data['predikat_kelulusan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Keperluan</th>
                    <td><?= htmlspecialchars($data['keperluan'] ?? '-'); ?></td>
                </tr>

            <?php } else if (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) { ?>
                <tr>
                    <th>Tahun Akademik</th>
                    <td><?= htmlspecialchars($data['tahun_akademik_skmk'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Keperluan</th>
                    <td><?= htmlspecialchars($data['keperluan'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Nama Orang Tua / Wali</th>
                    <td><?= htmlspecialchars($data['nama_ortu'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>NIP Orang Tua</th>
                    <td><?= htmlspecialchars($data['nip_ortu'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Instansi Orang Tua</th>
                    <td><?= htmlspecialchars($data['instansi_ortu'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Alamat Orang Tua</th>
                    <td><?= nl2br(htmlspecialchars($data['alamat_ortu'] ?? '-')); ?></td>
                </tr>
            <?php } ?>

            <?php
            $status_lower = strtolower($data['status_akhir']);

            $status_perbaikan = (strpos($status_lower, 'perbaikan') !== false || strpos($status_lower, 'dikembalikan') !== false);
            $status_ditolak = (strpos($status_lower, 'tolak') !== false);
            $status_menunggu = (strpos($status_lower, 'menunggu') !== false);

            if ($status_perbaikan) {
                $warna_status = 'color: #d97706; font-weight: 700;';
            } elseif ($status_ditolak) {
                $warna_status = 'color: #dc2626; font-weight: 700;';
            } elseif ($status_menunggu) {
                $warna_status = 'color: #d97706; font-weight: 700;';
            } else {
                $warna_status = 'color: #10b981; font-weight: 700;';
            }
            ?>

            <tr>
                <th>Status Akhir Permohonan</th>
                <td style="<?= $warna_status; ?>">
                    <?= htmlspecialchars($data['status_akhir']); ?>
                </td>
            </tr>

            <?php
            $catatan_admin = $data['alasan_penolakan'] ?? '';
            $catatan_umum = $data['catatan'] ?? '';
            $catatan_pb1 = $data['catatan_pb1'] ?? '';
            $catatan_pb2 = $data['catatan_pb2'] ?? '';
            $catatan_pa = $data['catatan_pa'] ?? '';

            $ada_catatan_akademik = (!empty($catatan_admin) || !empty($catatan_pb1) || !empty($catatan_pb2) || !empty($catatan_pa));

            if ($status_perbaikan && $ada_catatan_akademik) { ?>
                <tr>
                    <th>Catatan Revisi</th>
                    <td class="catatan-perbaikan-td">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php
                        if (!empty($catatan_admin)) {
                            echo "<strong>Dari Bagian Akademik:</strong><br>" . nl2br(htmlspecialchars($catatan_admin)) . "<br><br>";
                        }
                        if (!empty($catatan_pb2)) {
                            echo "<strong>Dari Dosen Pembimbing 2:</strong><br>" . nl2br(htmlspecialchars($catatan_pb2)) . "<br><br>";
                        }
                        if (!empty($catatan_pb1)) {
                            echo "<strong>Dari Dosen Pembimbing 1:</strong><br>" . nl2br(htmlspecialchars($catatan_pb1)) . "<br><br>";
                        }
                        if (!empty($catatan_pa)) {
                            echo "<strong>Dari Pembimbing Akademik:</strong><br>" . nl2br(htmlspecialchars($catatan_pa)) . "<br><br>";
                        }
                        ?>
                    </td>
                </tr>
            <?php } elseif ($status_ditolak && !empty($catatan_umum)) { ?>
                <tr>
                    <th>Catatan Penolakan</th>
                    <td class="status-tolak">
                        <?= nl2br(htmlspecialchars($catatan_umum)); ?>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <h3 class="section-title mt-4">Status Verifikasi Dokumen Pendukung</h3>
        <div class="document-box">
            <p><i class="fa-solid fa-file-circle-check icon-spacing"></i> Berikut adalah hasil pengecekan berkas oleh bagian akademik:</p>

            <div class="document-buttons-custom">

                <!-- Preview Surat Permohonan -->
                <?php
                $filePreview = "preview_surat_riset_mhs.php?id=" . $data['id_surat'] . "&mode=view";

                if (strpos($namaSurat, 'magang') !== false || strpos($namaSurat, 'pkl') !== false) {
                    $filePreview = "preview_surat_magang_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'aktif') !== false) {
                    $filePreview = "preview_sk_aktif_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'lulus') !== false) {
                    $filePreview = "preview_sk_lulus_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                } elseif (strpos($namaSurat, 'masih kuliah') !== false || strpos($namaSurat, 'skmk') !== false) {
                    $filePreview = "preview_skmk_mhs.php?id=" . $data['id_surat'] . "&mode=view";
                }
                ?>
                <div class="document-item-row document-item-draft">
                    <a href="#" class="document-link-item" onclick="bukaPreview('<?= $filePreview; ?>')">
                        <i class="fa-solid fa-file-lines document-icon-blue"></i> Surat Permohonan
                    </a>
                    <span class="document-system-note">Dihasilkan oleh sistem</span>
                </div>

                <!-- Looping Dokumen Lampiran Pendukung -->
                <?php
                $q_lampiran_mhs = mysqli_query($koneksi, "
                    SELECT ms.nama_syarat, lp.file_upload, lp.status_validasi 
                    FROM lampiran_pengajuan lp
                    JOIN master_syarat ms ON lp.id_syarat = ms.id_syarat
                    WHERE lp.id_surat = '$id_surat'
                ");

                if (mysqli_num_rows($q_lampiran_mhs) > 0) {
                    while ($lamp = mysqli_fetch_assoc($q_lampiran_mhs)) {
                        $statusVal = $lamp['status_validasi'];
                ?>
                        <div class="document-item-row">
                            <a href="#" class="document-link-item" onclick="bukaPreview('uploads/dokumen_hss/<?= htmlspecialchars($lamp['file_upload']); ?>')">
                                <i class="fa-solid fa-paperclip document-icon-amber"></i> <?= htmlspecialchars($lamp['nama_syarat']); ?>
                            </a>

                            <?php
                            if ($statusVal == 'Valid') {
                            ?>
                                <span class="badge-status-doc badge-valid">
                                    <i class="fa-solid fa-circle-check"></i> Sesuai
                                </span>
                            <?php
                            } elseif ($statusVal == 'Tidak Valid') {
                            ?>
                                <span class="badge-status-doc badge-invalid">
                                    <i class="fa-solid fa-circle-xmark"></i> Perlu Perbaikan
                                </span>
                            <?php
                            } else {
                            ?>
                                <span class="badge-status-doc badge-waiting">
                                    <i class="fa-solid fa-clock"></i> Menunggu Pengecekan Akademik
                                </span>
                            <?php
                            }
                            ?>
                        </div>
                <?php
                    }
                } else {
                    echo "<p class='empty-document-text'>Tidak ada lampiran dokumen.</p>";
                }
                ?>
            </div>
        </div>

        <div class="action-panel-custom">
            <a href="mhs_riwayat.php" class="btn-styled btn-back">
                Kembali
            </a>

            <?php if ($status_perbaikan) {
                $namaSuratLower = strtolower($data['nama_surat']);
                if (strpos($namaSuratLower, 'magang') !== false || strpos($namaSuratLower, 'pkl') !== false) {
                    $linkEdit = "mhs_form_surat_magang.php?id=" . $data['id_surat'];
                } elseif (strpos($namaSuratLower, 'aktif') !== false) {
                    $linkEdit = "mhs_form_sk_aktif_kuliah.php?id=" . $data['id_surat'];
                } else {
                    $linkEdit = "mhs_form_surat_riset.php?id=" . $data['id_surat'];
                }
            ?>
                <a href="<?= $linkEdit; ?>" class="btn-styled btn-perbaiki-custom">
                    Perbaiki Pengajuan Ini
                </a>
            <?php } ?>
        </div>

        <!-- MODAL PREVIEW IFRAME -->
        <div id="modalPreview" class="modal-preview">
            <div class="modal-content-preview">
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

            window.onclick = function(event) {
                const modal = document.getElementById('modalPreview');
                if (event.target == modal) {
                    tutupPreview();
                }
            }

            //fungsi dropdown menu user
            document.addEventListener('DOMContentLoaded', function() {
                const userBtn = document.getElementById('user-btn');
                const dropdown = document.getElementById('user-dropdown');

                userBtn.addEventListener('click', function(event) {
                    dropdown.classList.toggle('show');
                    event.stopPropagation();
                });

                window.addEventListener('click', function(event) {
                    if (!event.target.matches('#user-btn') && !event.target.closest('#user-btn')) {
                        if (dropdown.classList.contains('show')) {
                            dropdown.classList.remove('show');
                        }
                    }
                });
            });

            // Fungsi untuk menampilkan konfirmasi sebelum logout
            function confirmLogout(event, url) {
                event.preventDefault();
                Swal.fire({
                    title: 'Yakin ingin keluar?',
                    text: "Anda harus masuk kembali untuk mengakses halaman ini.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Ya, Keluar',
                    cancelButtonText: 'Batal',
                    heightAuto: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            }

            document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.navbar-nav')?.classList.toggle('active');
            });
        </script>
</body>

</html>