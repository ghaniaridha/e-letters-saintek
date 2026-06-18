<?php
include "admin_header.php";

/** @var mysqli $koneksi */

$query = mysqli_query($koneksi, "
    SELECT *
    FROM mahasiswa
    ORDER BY nama_mhs ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna</title>

    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">

    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">

        <div class="page-title">
            <h1>Kelola Pengguna</h1>
            <p>Daftar seluruh mahasiswa yang terdaftar pada sistem.</p>
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
                        <th width="150">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($query)) { ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['npm']); ?></td>
                            <td><?= htmlspecialchars($row['nama_mhs']); ?></td>
                            <td><?= htmlspecialchars($row['prodi']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td>
                                <button type="button"
                                        class="btn btn-detail"
                                        onclick="lihatDetail(
                                            '<?= htmlspecialchars($row['npm'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['nama_mhs'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['prodi'], ENT_QUOTES); ?>',
                                            '<?= htmlspecialchars($row['email'], ENT_QUOTES); ?>'
                                        )">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            Belum ada pengguna.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function lihatDetail(npm, nama, prodi, email) {
    Swal.fire({
        title: 'Detail Mahasiswa',
        html: `
            <div style="text-align:left; line-height:1.9;">
                <p><b>NPM:</b> ${npm}</p>
                <p><b>Nama Mahasiswa:</b> ${nama}</p>
                <p><b>Program Studi:</b> ${prodi}</p>
                <p><b>Email:</b> ${email}</p>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#2c4664'
    });
}
</script>

</body>
</html>