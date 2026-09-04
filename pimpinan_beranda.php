<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    echo "<script>alert('Silakan login sebagai dosen'); window.location='index.php';</script>";
    exit;
}

$id_dosen = $_SESSION['id_dosen'] ?? 0;

$namaLengkap = $_SESSION['nama_lengkap'] ?? 'pimpinan';
$idLogin = $_SESSION['nama'] ?? '';
$role = isset($_SESSION['role']) ? ucwords($_SESSION['role']) : 'pimpinan';

$inisial = '';
$namaParts = explode(' ', $namaLengkap);
if (!empty($namaParts)) {
    $inisial = strtoupper(substr($namaParts[0], 0, 1));
}

$query_dosen = mysqli_query($koneksi, "SELECT jabatan FROM dosen WHERE id_dosen = '$id_dosen' LIMIT 1");
$data_dosen = mysqli_fetch_assoc($query_dosen);
$jabatan_pimpinan = strtolower($data_dosen['jabatan'] ?? '');
$jabatan_lower = strtolower($jabatan_pimpinan);

$status_target = '';
$filter_jabatan = "";
$is_ormawa_role = false;

if (strpos($jabatan_lower, 'wadek 1') !== false || strpos($jabatan_lower, 'wakil dekan 1') !== false) {
    $status_target = 'Menunggu Wadek 1';
    $filter_jabatan = " AND (LOWER(js.nama_surat) LIKE '%magang%' OR LOWER(js.nama_surat) LIKE '%pkl%' OR LOWER(js.nama_surat) LIKE '%riset%' OR LOWER(js.nama_surat) LIKE '%aktif%')";
} elseif (strpos($jabatan_lower, 'wadek 2') !== false || strpos($jabatan_lower, 'wakil dekan 2') !== false) {
    $status_target = 'Menunggu Disposisi Wadek 2';
    $is_ormawa_role = true;
    $filter_jabatan = " AND (LOWER(js.nama_surat) LIKE '%dana%' OR LOWER(js.nama_surat) LIKE '%ruangan%')";
} elseif (strpos($jabatan_lower, 'dekan') !== false && strpos($jabatan_lower, 'wakil') === false && strpos($jabatan_lower, 'wadek') === false) {
    $status_target = 'Menunggu Dekan';
    $filter_jabatan = " AND (LOWER(js.nama_surat) NOT LIKE '%magang%' AND LOWER(js.nama_surat) NOT LIKE '%pkl%' AND LOWER(js.nama_surat) NOT LIKE '%riset%' AND LOWER(js.nama_surat) NOT LIKE '%aktif%' AND LOWER(js.nama_surat) NOT LIKE '%dana%' AND LOWER(js.nama_surat) NOT LIKE '%ruangan%')";
} elseif (strpos($jabatan_lower, 'kasubag') !== false || strpos($jabatan_lower, 'kasubbag') !== false) {
    $status_target = 'Menunggu Disposisi Kasubbag TU';
    $is_ormawa_role = true;
    $filter_jabatan = " AND (LOWER(js.nama_surat) LIKE '%dana%' OR LOWER(js.nama_surat) LIKE '%ruangan%')";
}

$q_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.status_akhir = '$status_target' $filter_jabatan");
$menunggu = mysqli_fetch_assoc($q_menunggu)['total'] ?? 0;

$q_disetujui = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.status_pimpinan = 'Disetujui' $filter_jabatan");
$disetujui = mysqli_fetch_assoc($q_disetujui)['total'] ?? 0;

$q_ditolak = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_pengajuan sp JOIN jenis_surat js ON sp.id_jenis = js.id_jenis WHERE sp.status_pimpinan = 'Ditolak' $filter_jabatan");
$ditolak = mysqli_fetch_assoc($q_ditolak)['total'] ?? 0;

$q_agenda = mysqli_query($koneksi, "
    SELECT sp.id_surat, o.nama_ormawa, js.nama_surat, sp.jadwal_pertemuan
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    WHERE sp.jadwal_pertemuan IS NOT NULL 
      AND DATE(sp.jadwal_pertemuan) >= CURDATE()
    ORDER BY sp.jadwal_pertemuan ASC
    LIMIT 4
");

$tahun_ini = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');
$bulan_ini = isset($_GET['m']) ? (int)$_GET['m'] : date('n');

$nama_bulan = [
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

$array_bulan_indo = [
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

$daftar_jadwal = [];
$q_SemuaJadwal = mysqli_query($koneksi, "
    SELECT sp.id_surat, sp.jadwal_pertemuan, o.nama_ormawa, js.nama_surat,
           dpr.catatan as catatan_ruangan, dpd.catatan as catatan_dana
    FROM surat_pengajuan sp
    JOIN ormawa o ON sp.id_ormawa = o.id_ormawa
    JOIN jenis_surat js ON sp.id_jenis = js.id_jenis
    LEFT JOIN detail_peminjaman_ruangan dpr ON sp.id_surat = dpr.id_surat
    LEFT JOIN detail_pengajuan_dana dpd ON sp.id_surat = dpd.id_surat
    WHERE sp.jadwal_pertemuan IS NOT NULL 
      AND MONTH(sp.jadwal_pertemuan) = '$bulan_ini' 
      AND YEAR(sp.jadwal_pertemuan) = '$tahun_ini'
      $filter_jabatan
");

while ($j = mysqli_fetch_assoc($q_SemuaJadwal)) {
    $tgl_saja = date('Y-m-d', strtotime($j['jadwal_pertemuan']));
    $daftar_jadwal[$tgl_saja][] = [
        'nama_ormawa' => $j['nama_ormawa'],
        'nama_surat'  => $j['nama_surat'],
        'jam'         => date('H:i', strtotime($j['jadwal_pertemuan'])),
        'catatan'     => $j['catatan_ruangan'] ?? $j['catatan_dana'] ?? '-'
    ];
}

$jml_hari_bulan_ini = cal_days_in_month(CAL_GREGORIAN, $bulan_ini, $tahun_ini);
$hari_pertama = date('w', strtotime("$tahun_ini-$bulan_ini-01"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar">
        <a href="#" id="hamburger-menu"><i class="fa-solid fa-bars"></i></a>
        <a href="#" class="navbar-logo">
            <img src="images/LOGO2.png" alt="navbar-logo">
        </a>

        <div class="navbar-nav">
            <a href="pimpinan_beranda.php#home">Beranda</a>
            <a href="pimpinan_verif.php">Disposisi & Verifikasi</a>
            <a href="pimpinan_beranda.php#riwayat">Informasi</a>
            <a href="pimpinan_riwayat.php">Riwayat Verifikasi</a>

            <div class="nav-dropdown">
                <a href="#" class="<?= basename($_SERVER['PHP_SELF']) == 'pimpinan_tracking.php' ? 'active' : ''; ?>">
                    Tracking <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </a>
                <div class="dropdown-content">
                    <a href="pimpinan_tracking.php?kategori=akademik">Surat Akademik</a>
                    <a href="pimpinan_tracking.php?kategori=ormawa">Surat Organisasi</a>
                </div>
            </div>
        </div>

        <div class="navbar-extra">
            <div class="user-menu-container">
                <button id="user-btn" class="user-btn">
                    <span class="avatar-inisial"><?= htmlspecialchars($inisial); ?></span>
                </button>

                <div id="user-dropdown" class="dropdown-menu">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($namaLengkap); ?></span>
                        <span class="user-role"><?= htmlspecialchars($idLogin); ?> - <?= htmlspecialchars($role); ?></span>
                    </div>

                    <div class="divider"></div>

                    <a href="logout.php" class="logout-btn" onclick="confirmLogout(event, this.href)">
                        <span>Keluar</span>
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero" id="home">
        <main class="content">
            <h2>Halo, <?= htmlspecialchars($namaLengkap); ?></h2>
            <h1>Selamat datang di Dashboard Pimpinan Akademik FST UIN RIL</h1>
        </main>
    </section>

    <section id="riwayat" class="riwayat-section">
        <div class="wave-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"
                    class="shape-fill"></path>
            </svg>
        </div>

        <section id="status-info" class="status-info-section">
            <div class="wave-divider">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                </svg>
            </div>

            <div class="status-info-header">
                <h2>Ringkasan <span class="text-orange">Kinerja & Kalender</span></h2>
            </div>

            <div class="pimpinan-dashboard-wrapper">
                <div class="stats-column">
                    <div class="status-box-pimpinan status-warning-pimpinan">
                        <h4 class="stats-title">Menunggu</h4>
                        <span class="status-count"><?= $menunggu; ?></span>
                        <p class="stats-desc">Menunggu verifikasi Anda</p>
                    </div>

                    <div class="status-box-pimpinan status-success">
                        <h4 class="stats-title">Disetujui</h4>
                        <span class="status-count"><?= $disetujui; ?></span>
                        <p class="stats-desc">Riwayat persetujuan surat</p>
                    </div>

                    <div class="status-box-pimpinan status-danger">
                        <h4 class="stats-title">Ditolak</h4>
                        <span class="status-count"><?= $ditolak; ?></span>
                        <p class="stats-desc">Riwayat penolakan surat</p>
                    </div>
                </div>

                <div class="calendar-column">
                    <div class="cal-header">
                        <h3>
                            <i class="fa-solid fa-calendar-days text-orange cal-title-icon"></i>
                            <?= $array_bulan_indo[$bulan_ini]; ?> <?= $tahun_ini; ?>
                        </h3>
                        <div class="cal-nav-btns">
                            <?php
                            $prev_m = $bulan_ini - 1;
                            $prev_y = $tahun_ini;
                            if ($prev_m < 1) {
                                $prev_m = 12;
                                $prev_y--;
                            }

                            $next_m = $bulan_ini + 1;
                            $next_y = $tahun_ini;
                            if ($next_m > 12) {
                                $next_m = 1;
                                $next_y++;
                            }
                            ?>
                            <a href="?m=<?= $prev_m; ?>&y=<?= $prev_y; ?>" class="cal-nav-btn"><i class="fa-solid fa-chevron-left"></i></a>
                            <a href="?m=<?= $next_m; ?>&y=<?= $next_y; ?>" class="cal-nav-btn"><i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>

                    <div class="cal-grid cal-grid-head">
                        <div class="cal-day-name sunday">Min</div>
                        <div class="cal-day-name">Sen</div>
                        <div class="cal-day-name">Sel</div>
                        <div class="cal-day-name">Rab</div>
                        <div class="cal-day-name">Kam</div>
                        <div class="cal-day-name">Jum</div>
                        <div class="cal-day-name">Sab</div>
                    </div>

                    <div class="cal-grid">
                        <?php
                        for ($i = 0; $i < $hari_pertama; $i++) {
                            echo '<div class="cal-cell other-month"></div>';
                        }

                        for ($d = 1; $d <= $jml_hari_bulan_ini; $d++) {
                            $format_tgl = sprintf('%04d-%02d-%02d', $tahun_ini, $bulan_ini, $d);
                            $is_today = ($format_tgl == date('Y-m-d'));

                            $has_event = isset($daftar_jadwal[$format_tgl]);

                            $class_extra = '';
                            if ($is_today) $class_extra .= ' today';
                            if ($has_event) $class_extra .= ' has-event clickable-event';

                            $onclick_attr = '';
                            if ($has_event) {
                                $json_data = htmlspecialchars(json_encode($daftar_jadwal[$format_tgl]), ENT_QUOTES, 'UTF-8');
                                $onclick_attr = " onclick='tampilkanDetailAgenda(\"$format_tgl\", $json_data)'";
                            }

                            echo '<div class="cal-cell' . $class_extra . '"' . $onclick_attr . '>';
                            echo '<span>' . $d . '</span>';
                            if ($has_event) {
                                echo '<div class="event-dot" title="Klik untuk lihat detail agenda"></div>';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <div class="cal-footer">
                        <span class="cal-legend-item"><span class="legend-dot dot-today"></span> Hari Ini</span>
                        <span class="cal-legend-item"><span class="legend-dot dot-event"></span> Jadwal Audiensi Ormawa</span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="footer-section">
            <div class="footer-wave">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M1200,0H0V60.4C138.85,108.62,298.54,125,441.77,105.81,595.6,85.19,705.51,20.89,864,24.7c124.62,3,212.87,41.4,336,65.7V0Z" class="shape-fill"></path>
                </svg>
            </div>
            <div class="footer-container">
                <div class="footer-col info-col">
                    <h3>SIPATU FST</h3>
                    <p>Sistem Informasi Manajemen Persuratan Fakultas Sains dan Teknologi UIN Raden Intan Lampung.</p>
                    <div class="contact-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Jl. Endro Suratmin No.38, Sukarame, Kec. Sukarame, Kota Bandar Lampung, Lampung 35131</span>
                    </div>
                </div>

                <div class="footer-col map-col">
                    <h4>Lokasi Kami</h4>
                    <div class="map-wrapper">
                        <iframe
                            src="https://maps.google.com/maps?q=Gedung%20Fakultas%20Sains%20dan%20Teknologi%20Tower%201%20UIN%20Raden%20Intan%20Lampung&t=&z=17&ie=UTF8&iwloc=&output=embed"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

                <div class="footer-col contact-col">
                    <h4>INFORMASI & KONTAK</h4>
                    <div class="contact-item">
                        <i class="fa-brands fa-instagram"></i>
                        <a href="https://www.instagram.com/saintek.radenintan" target="_blank" class="footer-clickable-link">
                            <span>saintek.radenintan</span>
                        </a>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-globe"></i>
                        <a href="https://saintek.radenintan.ac.id" target="_blank" class="footer-clickable-link">
                            <span>saintek.radenintan.ac.id</span>
                        </a>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-clock"></i>
                        <span>Senin - Jumat: 08.00 - 16.00 WIB</span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Fakultas Sains dan Teknologi UIN RIL. Dibuat oleh Ghania Ridha Khairiah.</p>
            </div>
        </footer>

        <script>
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

            function confirmLogout(event, url) {
                event.preventDefault();

                Swal.fire({
                    title: 'Yakin ingin keluar?',
                    text: 'Anda harus login kembali untuk mengakses layanan akademik.',
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

            function tampilkanDetailAgenda(tanggalStr, listAgenda) {
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const tanggalFormatted = new Date(tanggalStr).toLocaleDateString('id-ID', options);

                let htmlContent = `<div class="agenda-container">`;

                listAgenda.forEach((item, index) => {
                    htmlContent += `
                <div class="agenda-card">
                    <div class="agenda-title">
                        ${item.nama_ormawa}
                    </div>
                    <div class="agenda-subtitle">
                        ${item.nama_surat}
                    </div>
                    <div class="agenda-footer">
                        <div>
                            <span class="agenda-time-badge">
                                <i class="fa-regular fa-clock"></i> ${item.jam} WIB
                            </span>
                        </div>
                        <div class="agenda-location" title="${item.catatan}">
                            <i class="fa-solid fa-location-dot agenda-loc-icon"></i> ${item.catatan}
                        </div>
                    </div>
                </div>`;
                });

                htmlContent += `</div>`;

                Swal.fire({
                    title: `<div class="swal2-title-sec">Agenda: ${tanggalFormatted}</div>`,
                    html: htmlContent,
                    showConfirmButton: false,
                    showCloseButton: true,
                    closeButtonHtml: '<i class="fa-solid fa-xmark"></i>',
                    width: '500px',
                    customClass: {
                        closeButton: 'custom-swal-close-btn'
                    }
                });
            }

            document.getElementById('hamburger-menu')?.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.navbar-nav')?.classList.toggle('active');
            });
            document.getElementById('my-hamburger-menu')?.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.my-navbar-nav')?.classList.toggle('active');
            });
        </script>
</body>

</html>