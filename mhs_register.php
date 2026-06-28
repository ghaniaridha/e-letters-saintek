<?php
session_start();
include "koneksi.php";

$queryProdi = "SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi ASC";
$resultProdi = mysqli_query($koneksi, $queryProdi);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $npm = $_POST['npm'];
    $nama_mhs = $_POST['nama_mhs'];
    $id_prodi = $_POST['id_prodi'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if ($password !== $konfirmasi_password) {
        $_SESSION['error'] = "Kata sandi dan konfirmasi tidak cocok!";
        header("Location: mhs_register.php");
        exit;
    }

    $stmt = $koneksi->prepare("SELECT npm FROM mahasiswa WHERE npm = ?");
    $stmt->bind_param("s", $npm);
    $stmt->execute();
    $hasil_cek = $stmt->get_result();

    if ($hasil_cek->num_rows > 0) {
        $_SESSION['error'] = "NPM sudah terdaftar! Silakan Masuk.";
        header("Location: mhs_register.php");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt_insert = $koneksi->prepare("INSERT INTO mahasiswa (npm, nama_mhs, id_prodi, email, password, status) 
                VALUES (?, ?, ?, ?, ?, 0)");
    $stmt_insert->bind_param("sssss", $npm, $nama_mhs, $id_prodi, $email, $hashed_password);

    if ($stmt_insert->execute()) {
        $_SESSION['success'] = "Pendaftaran berhasil! Silakan tunggu persetujuan Admin.";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan sistem saat mendaftar.";
        header("Location: mhs_register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="register.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>

    <form class="login-form box-register" method="POST" action="">
        <div class="form-container">
            <div class="form-image">
            </div>

            <div class="form-content">
                <img src="images/logo1.png" alt="image" class="span-2" />

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message span-2">
                        <?= $_SESSION['error']; ?>
                    </div>
                <?php unset($_SESSION['error']);
                endif; ?>

                <div class="box-input-data">
                    <i class="fa-regular fa-id-card"></i>
                    <input type="text" name="npm" placeholder="NPM" required>
                </div>

                <div class="box-input-data">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="nama_mhs" placeholder="Nama Lengkap" required>
                </div>

                <div class="box-input-data">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <select name="id_prodi" class="select-field" required>
                        <option value="" disabled selected>Pilih Program Studi</option>
                        <?php while ($row = mysqli_fetch_assoc($resultProdi)) {
                            echo "<option value='" . $row['id_prodi'] . "'>" . $row['nama_prodi'] . "</option>";
                        } ?>
                    </select>
                </div>

                <div class="box-input-data">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Mahasiswa" required>
                </div>

                <div class="box-input-pass">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Kata Sandi" required>
                    <i id="toggle-password" class="fa-regular fa-eye-slash" style="cursor: pointer;"></i>
                </div>

                <div class="box-input-pass">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirm_password" name="konfirmasi_password" placeholder="Konfirmasi" required>
                    <i id="toggle-confirm" class="fa-regular fa-eye-slash toggle-icon" style="cursor: pointer;"></i>
                </div>

                <button type="submit" class="btn-input span-2">Daftar</button>
                <p class="register-link span-2">
                    Sudah punya akun? <a href="index.php">Masuk di sini</a>
                </p>
            </div>
        </div>
    </form>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            }
        }

        document.getElementById("toggle-password").addEventListener("click", function() {
            togglePass("password", "toggle-password");
        });

        document.getElementById("toggle-confirm").addEventListener("click", function() {
            togglePass("confirm_password", "toggle-confirm");
        });
    </script>

</body>

</html>