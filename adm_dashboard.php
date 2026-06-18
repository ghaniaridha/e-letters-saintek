<?php include "admin_header.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include "admin_sidebar.php"; ?>

    <main class="main-content">
        <div class="page-title">
            <h1>Dashboard Admin</h1>
            <p>Selamat datang di halaman admin layanan akademik FST.</p>
        </div>

        <div class="card-grid">
            <div class="card">
                <i class="fa-solid fa-file-word"></i>
                <h3>12</h3>
                <p>Template Surat</p>
            </div>

            <div class="card">
                <i class="fa-solid fa-envelope-open-text"></i>
                <h3>24</h3>
                <p>Permohonan Masuk</p>
            </div>

            <div class="card">
                <i class="fa-solid fa-users"></i>
                <h3>150</h3>
                <p>Pengguna</p>
            </div>

            <div class="card">
                <i class="fa-solid fa-file-export"></i>
                <h3>18</h3>
                <p>Surat Keluar</p>
            </div>
        </div>

        <div class="chart-container">
            <h2>Statistik Permohonan Surat</h2>
            <canvas id="suratChart"></canvas>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('suratChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
        datasets: [{
            label: 'Jumlah Permohonan Surat',
            data: [12, 19, 15, 22, 18, 30, 27, 24, 20, 26, 17, 14],
            backgroundColor: '#ffcc00',
            borderColor: '#2c4664',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>