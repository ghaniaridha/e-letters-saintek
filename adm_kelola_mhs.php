<?php
session_start();
include "koneksi.php";

$sql = "SELECT mahasiswa.*, prodi.nama_prodi,
               d1.nama_dosen AS nama_pa,
               d2.nama_dosen AS nama_dosbing1,
               d3.nama_dosen AS nama_dosbing2
        FROM mahasiswa
        LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
        LEFT JOIN dosen d1 ON mahasiswa.id_pa = d1.id_dosen
        LEFT JOIN dosen d2 ON mahasiswa.id_pb1 = d2.id_dosen
        LEFT JOIN dosen d3 ON mahasiswa.id_pb2 = d3.id_dosen";
$where = [];

// logika search & filter
if (!empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['keyword']);
    $where[] = "(mahasiswa.npm LIKE '%$keyword%' OR mahasiswa.nama_mhs LIKE '%$keyword%')";
}
if (!empty($_GET['prodi'])) {
    $prodi = mysqli_real_escape_string($koneksi, $_GET['prodi']);
    $where[] = "mahasiswa.id_prodi = '$prodi'";
}
if (isset($_GET['status']) && $_GET['status'] !== "") {
    $status = mysqli_real_escape_string($koneksi, $_GET['status']);
    $where[] = "status = '$status'";
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// pengurutan data berdasarkan status, prodi, dan nama mahasiswa
$sql .= " ORDER BY mahasiswa.status ASC, prodi.nama_prodi ASC, mahasiswa.nama_mhs ASC";

// logika pagination
$batas = 5;
$halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$query_total = mysqli_query($koneksi, $sql);
$jumlah_data = mysqli_num_rows($query_total);
$total_halaman = ceil($jumlah_data / $batas);

$sql_limit = $sql . " LIMIT $halaman_awal, $batas";
$query = mysqli_query($koneksi, $sql_limit);

$url_query = $_GET;
unset($url_query['page']);
$query_string = http_build_query($url_query);
$query_string = $query_string ? '&' . $query_string : '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mahasiswa</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include "admin_sidebar.php"; ?>

        <main class="main-content">
            <div class="page-title">
                <h1>Verifikasi & Kelola Mahasiswa</h1>
                <p>Kelola data dan lakukan verifikasi pendaftaran akun baru mahasiswa.</p>
            </div>

            <div class="table-card">
                <form method="GET" action="" class="filter-section">
                    <input type="text" name="keyword" placeholder="Cari NPM atau Nama..." value="<?= $_GET['keyword'] ?? '' ?>">

                    <select name="prodi">
                        <option value="">Semua Prodi</option>
                        <?php
                        $res = mysqli_query($koneksi, "SELECT * FROM prodi");
                        while ($p = mysqli_fetch_assoc($res)) {
                            $selected = (isset($_GET['prodi']) && $_GET['prodi'] == $p['id_prodi']) ? 'selected' : '';
                            echo "<option value='" . $p['id_prodi'] . "' $selected>" . $p['nama_prodi'] . "</option>";
                        }
                        ?>
                    </select>

                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="1" <?= (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="2" <?= (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : '' ?>>Nonaktif</option>
                        <option value="0" <?= (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : '' ?>>Menunggu</option>
                    </select>

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>

                    <a href="adm_kelola_mhs.php" class="btn btn-secondary">
                        <i class="fa-solid fa-rotate-left"></i> Atur Ulang
                    </a>
                </form>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                            <?php $no = $halaman_awal + 1;
                            while ($row = mysqli_fetch_assoc($query)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['npm']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_prodi']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>

                                    <td>
                                        <?php if ($row['status'] == 1) { ?>
                                            <span class="status-aktif">Aktif</span>
                                        <?php } elseif ($row['status'] == 2) { ?>
                                            <span class="status-nonaktif">Dinonaktifkan</span>
                                        <?php } else { ?>
                                            <span class="status-menunggu">Menunggu</span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <div class="action-group">
                                            <button type="button" class="btn btn-detail"
                                                onclick="lihatDetail(
                                                    '<?= htmlspecialchars($row['npm'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_mhs'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_prodi'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= $row['status'] ?? ''; ?>',
                                                    '<?= htmlspecialchars($row['nama_pa'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_dosbing1'] ?? '', ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_dosbing2'] ?? '', ENT_QUOTES); ?>'   
                                                )">
                                                Rincian
                                            </button>

                                            <button type="button" class="btn btn-edit" onclick="window.location='adm_edit_mhs.php?npm=<?= $row['npm'] ?>'">Ubah</button>
                                            <button type="button" class="btn btn-reset" onclick="konfirmasiReset('<?= $row['npm'] ?>')">Reset</button>
                                            <button type="button" class="btn btn-nonaktif" onclick="konfirmasiNonaktif('<?= $row['npm'] ?>')">Nonaktif</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="7" class="empty-table">Belum ada pengguna.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
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

            </div>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        //fungsi button rincian data - start
        function lihatDetail(npm, nama, prodi, email, status, nama_pa, nama_dosbing1, nama_dosbing2) {
            const dosbing1 = nama_dosbing1 ? nama_dosbing1 : '<i class="text-muted">Belum ditentukan</i>';
            const dosbing2 = nama_dosbing2 ? nama_dosbing2 : '<i class="text-muted">Belum ditentukan</i>';

            let statusBadge = '';
            if (status == 1) {
                statusBadge = '<span class="badge-status badge-aktif">Akun Aktif</span>';
            } else if (status == 0) {
                statusBadge = '<span class="badge-status badge-menunggu">Menunggu Verifikasi</span>';
            } else {
                statusBadge = '<span class="badge-status badge-nonaktif">Nonaktif</span>';
            }

            let htmlContent = `
                <div class="swal-scroll-container">
                    <div class="section-title">Informasi Pribadi</div>
                    <div class="detail-row"><div class="detail-label">NPM</div><div class="detail-value">${npm}</div></div>
                    <div class="detail-row"><div class="detail-label">Nama</div><div class="detail-value">${nama}</div></div>
                    <div class="detail-row"><div class="detail-label">Prodi</div><div class="detail-value">${prodi}</div></div>
                    <div class="detail-row"><div class="detail-label">Email</div><div class="detail-value">${email}</div></div>

                    <div class="section-title">Informasi Akademik</div>
                    <div class="detail-row"><div class="detail-label">Dosen PA</div><div class="detail-value">${nama_pa}</div></div>
                    <div class="detail-row"><div class="detail-label">Pembimbing 1</div><div class="detail-value">${dosbing1}</div></div>
                    <div class="detail-row"><div class="detail-label">Pembimbing 2</div><div class="detail-value">${dosbing2}</div></div>
                </div>
            `;

            if (status == 0) {
                htmlContent += `
            <div class="swal-action-box">
                <button onclick="konfirmasiAksi('${npm}', 'setuju')" class="swal-btn swal-btn-setuju">Setuju</button>
                <button onclick="konfirmasiAksi('${npm}', 'tolak')" class="swal-btn swal-btn-tolak">Tolak</button>
                <button onclick="Swal.close()" class="swal-btn swal-btn-batal">Batal</button>
            </div>
        `;
            } else if (status == 2) {
                htmlContent += `
            <div class="swal-action-box">
                <button onclick="konfirmasiAktifkan('${npm}')" class="swal-btn swal-btn-setuju">Aktifkan Kembali</button>
                <button onclick="Swal.close()" class="swal-btn swal-btn-batal">Batal</button>
            </div>
        `;
            } else {
                htmlContent += `<div class="swal-status-aktif"><b>Akun sudah aktif</b></div>`;
            }

            Swal.fire({
                title: 'Detail Mahasiswa',
                html: htmlContent,
                width: '500px',
                showCloseButton: true,
                focusConfirm: false,
                showConfirmButton: false,
                padding: '20px'
            });
        }
        //fungsi button rincian data - end

        //fungsi button terima dan tolak permintaan akun baru - start
        function konfirmasiAksi(npm, action) {
            const isSetuju = action === 'setuju';

            Swal.fire({
                title: isSetuju ? 'Setujui Pendaftaran?' : 'Tolak Pendaftaran?',
                text: isSetuju ?
                    "Mahasiswa akan mendapatkan akses login." : "Data mahasiswa akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isSetuju ? '#28a745' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isSetuju ? 'Ya, Setujui' : 'Ya, Tolak',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_approval_mhs.php?npm=' + npm + '&action=' + action;
                }
            });
        }
        //fungsi button terima dan tolak permintaan akun baru - end

        //fungsi button reset password -start
        function konfirmasiReset(npm) {
            Swal.fire({
                title: 'Reset Sandi?',
                text: "Sandi akan diubah menjadi default (npm/123456).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_reset_password.php?npm=' + npm;
                }
            });
        }
        //fungsi button reset password -start

        //fungsi button nonaktifkan akun - start
        function konfirmasiNonaktif(npm) {
            Swal.fire({
                title: 'Nonaktifkan Akun?',
                text: "Mahasiswa tidak akan bisa login ke sistem.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_nonaktifkan_mhs.php?npm=' + npm;
                }
            });
        }
        // fungsi button nonaktifkan akun - start

        // fungsi button mengaktifkan akun kembali - start
        function konfirmasiAktifkan(npm) {
            Swal.fire({
                title: 'Aktifkan Akun?',
                text: "Akun akan diaktifkan kembali dan mahasiswa bisa login.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Aktifkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'adm_aktifkan_mhs.php?npm=' + npm;
                }
            });
        }
        // fungsi button mengaktifkan akun kembali - end
    </script>

</body>

</html>