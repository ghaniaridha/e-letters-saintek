<?php
session_start();
include "koneksi.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="shortcut icon" href="images/Logo UINRIL(2).png" />
    <link rel="stylesheet" href="index.css" media="screen" title="no title">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous">
</head>

<body>
    <form class="login-form" method="POST" action="">
        <div class="form-container">
            <div class="form-image">
                <a class="navbar-brand">
                </a>
            </div>
            <div class="form-content">
                <img src="images/AKADEMIK FST1.jpg" alt="image" />

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?= $_SESSION['error']; ?>
                    </div>
                <?php unset($_SESSION['error']);
                endif; ?>

                <div class="box-input-data">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="login_id" placeholder="NIP/NPM/NPA" required>
                </div>
                <div class="box-input-pass">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="PASSWORD" required>
                    <i id="toggle-password" class="fa-regular fa-eye-slash"></i>
                </div>

                <button type="submit" class="btn-input">Login</button>
            </div>
        </div>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $login_id = $_POST['login_id'];
        $password = $_POST['password'];

        $queryAdmin = "SELECT * FROM admin WHERE npa='$login_id'";
        $resultAdmin = mysqli_query($koneksi, $queryAdmin);
        $dataAdmin = mysqli_fetch_assoc($resultAdmin);

        if ($dataAdmin && password_verify($password, $dataAdmin['password'])) {
            $_SESSION['id_admin'] = $dataAdmin['id_admin'];
            $_SESSION['nama_lengkap'] = $dataAdmin['nama_admin'];
            $_SESSION['nama'] = $dataAdmin['npa'];
            $_SESSION['role'] = 'admin';
            header("Location:adm_dashboard.php");
            exit;
        }

        $queryDosen = "SELECT * FROM dosen WHERE nip='$login_id'";
        $resultDosen = mysqli_query($koneksi, $queryDosen);
        $dataDosen = mysqli_fetch_assoc($resultDosen);

        if ($dataDosen && password_verify($password, $dataDosen['password'])) {
            $_SESSION['id_dosen'] = $dataDosen['id_dosen'];
            $_SESSION['nama_lengkap'] = $dataDosen['nama_dosen'];
            $_SESSION['nama'] = $dataDosen['nip'];
            $_SESSION['nip'] = $dataDosen['nip'];
            $_SESSION['jabatan'] = $dataDosen['jabatan'];
            if (strtolower($dataDosen['role_akses']) == 'pimpinan') {
                $_SESSION['role'] = 'pimpinan';
                header("Location: pimpinan_dashboard.php");
            } else {
                $_SESSION['role'] = 'dosen';
                header("Location: dosen_dashboard.php");
            }
            exit;
        }

        $queryMhs = "SELECT * FROM mahasiswa WHERE npm='$login_id'";
        $resultMhs = mysqli_query($koneksi, $queryMhs);
        $dataMhs = mysqli_fetch_assoc($resultMhs);

        if ($dataMhs && password_verify($password, $dataMhs['password'])) {
            $_SESSION['id_mhs'] = $dataMhs['id_mhs'];
            $_SESSION['nama_lengkap'] = $dataMhs['nama_mhs'];
            $_SESSION['nama'] = $dataMhs['npm'];
            $_SESSION['npm'] = $dataMhs['npm'];
            $_SESSION['prodi'] = $dataMhs['prodi'];
            $_SESSION['id_pa'] = $dataMhs['id_pa'];
            $_SESSION['id_pb1'] = $dataMhs['id_pb1'];
            $_SESSION['id_pb2'] = $dataMhs['id_pb2'];
            $_SESSION['semester'] = $dataMhs['semester'];
            $_SESSION['role'] = 'mahasiswa';
            header("Location: mhs_dashboard.php");
            exit;
        }

        $_SESSION['error'] = "Login gagal! NIP/NPM/NPA atau password salah.";
        header("Location: index.php");
        exit;
    }
    ?>

    <script>
        const togglePassword = document.getElementById("toggle-password");
        const passwordInput = document.getElementById("password");

        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);

            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    </script>
</body>

</html>