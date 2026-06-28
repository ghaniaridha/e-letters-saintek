<?php
session_start();
include "koneksi.php";

$query = mysqli_query($koneksi, "
    SELECT m.*, p.nama_prodi 
    FROM mahasiswa m 
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi 
    ORDER BY m.status ASC, m.nama_mhs ASC
");
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
                            <?php $no = 1;
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
                                                    '<?= htmlspecialchars($row['npm'], ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_mhs'], ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['nama_prodi'], ENT_QUOTES); ?>',
                                                    '<?= htmlspecialchars($row['email'], ENT_QUOTES); ?>',
                                                    '<?= $row['status']; ?>'
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

            </div>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        //fungsi button rincian data - start
        function lihatDetail(npm, nama, prodi, email, status) {
            let htmlContent = `
        <div class="swal-detail-box">
            <p><b>NPM:</b> ${npm}</p>
            <p><b>Nama:</b> ${nama}</p>
            <p><b>Prodi:</b> ${prodi}</p>
            <p><b>Email:</b> ${email}</p>
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
                icon: (status == 1) ? 'success' : 'info',
                showConfirmButton: false
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