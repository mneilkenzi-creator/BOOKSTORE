<?php
require_once __DIR__ . "/../config/koneksi.php";

// CEK LOGIN ADMIN
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama'] ?? 'Administrator';

// TOTAL USER
$query_user = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total FROM users
");

if (!$query_user) {
    die("Error total user: " . mysqli_error($koneksi));
}

$data_user = mysqli_fetch_assoc($query_user);
$total_user = (int) ($data_user['total'] ?? 0);


// TOTAL BUKU
$query_buku = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total FROM buku
");

if (!$query_buku) {
    die("Error total buku: " . mysqli_error($koneksi));
}

$data_buku = mysqli_fetch_assoc($query_buku);
$total_buku = (int) ($data_buku['total'] ?? 0);


// TOTAL PESANAN
$query_pesanan = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total FROM pesanan
");

if (!$query_pesanan) {
    die("Error total pesanan: " . mysqli_error($koneksi));
}

$data_pesanan = mysqli_fetch_assoc($query_pesanan);
$total_pesanan = (int) ($data_pesanan['total'] ?? 0);


// TOTAL PENDAPATAN
// QRIS/BANK dihitung jika pembayaran berhasil.
// COD dihitung jika pesanan sudah selesai.
$query_pendapatan = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE
        status_pembayaran = 'Berhasil'
        OR (
            metode_pembayaran IN ('COD', 'Payment at Delivery')
            AND status IN ('Pesanan Selesai', 'Selesai')
        )
");

if (!$query_pendapatan) {
    die("Error total pendapatan: " . mysqli_error($koneksi));
}

$data_pendapatan = mysqli_fetch_assoc($query_pendapatan);
$total_pendapatan = (float) ($data_pendapatan['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - NduttStore</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f6fb;
            color: #333;
        }

        .sidebar {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #667eea, #764ba2);
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo h2 {
            font-size: 25px;
        }

        .logo p {
            font-size: 13px;
            opacity: .8;
            margin-top: 5px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: white;
            padding: 14px 15px;
            border-radius: 10px;
            transition: .3s;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,.2);
            transform: translateX(4px);
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .topbar h1 {
            font-size: 26px;
            color: #333;
        }

        .admin {
            color: #667eea;
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: .3s;
            min-width: 0;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            min-width: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: #eee;
        }

        .stat-info {
            min-width: 0;
        }

        .stat-info h3 {
            font-size: 14px;
            color: #777;
            margin-bottom: 8px;
        }

        .stat-info h2 {
            font-size: 23px;
            color: #333;
            overflow-wrap: anywhere;
        }

        .stat-info .pendapatan {
            font-size: 20px;
        }

        .content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .content h2 {
            margin-bottom: 10px;
            color: #444;
        }

        .content p {
            color: #777;
            line-height: 1.6;
        }

        .quick-menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 25px;
        }

        .quick-menu a {
            text-decoration: none;
            padding: 20px;
            border-radius: 12px;
            background: #f7f8ff;
            color: #555;
            font-weight: bold;
            transition: .3s;
        }

        .quick-menu a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        .catatan {
            margin-top: 20px;
            padding: 15px;
            background: #f7f8ff;
            border-radius: 10px;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        @media(max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-menu {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media(max-width: 500px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">

    <div class="logo">
        <h2>📚 NduttStore</h2>
        <p>Admin Panel</p>
    </div>

    <ul class="menu">

        <li>
            <a href="dashboard.php" class="active">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="kategori.php">
                📂 Kategori Buku
            </a>
        </li>

        <li>
            <a href="buku.php">
                📚 Data Buku
            </a>
        </li>

        <li>
            <a href="users.php">
                👥 Data User
            </a>
        </li>

        <li>
            <a href="pesanan.php">
                🛒 Pesanan
            </a>
        </li>

        <li>
            <a href="pesan.php">
                💬 Pesan Masuk
            </a>
        </li>

        <li>
            <a href="logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</div>

<div class="main">

    <div class="topbar">

        <h1>🏠 Dashboard Admin</h1>

        <div class="admin">
            👤 <?= htmlspecialchars($nama_admin); ?>
        </div>

    </div>

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">👥</div>

            <div class="stat-info">
                <h3>Total User</h3>
                <h2><?= number_format($total_user, 0, ',', '.'); ?></h2>
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon">📚</div>

            <div class="stat-info">
                <h3>Total Buku</h3>
                <h2><?= number_format($total_buku, 0, ',', '.'); ?></h2>
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon">🛒</div>

            <div class="stat-info">
                <h3>Total Pesanan</h3>
                <h2><?= number_format($total_pesanan, 0, ',', '.'); ?></h2>
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon">💰</div>

            <div class="stat-info">
                <h3>Total Pendapatan</h3>

                <h2 class="pendapatan">
                    Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?>
                </h2>
            </div>

        </div>

    </div>

    <div class="content">

        <h2>Selamat Datang di NduttStore 👋</h2>

        <p>
            Kelola toko buku NduttStore melalui panel administrator.
            Kamu dapat mengelola kategori, data buku, user, pesanan,
            dan pesan masuk dari halaman ini.
        </p>

        <div class="quick-menu">

            <a href="kategori.php">
                📂 Kelola Kategori
            </a>

            <a href="buku.php">
                📚 Kelola Buku
            </a>

            <a href="users.php">
                👥 Lihat User
            </a>

            <a href="pesanan.php">
                🛒 Kelola Pesanan
            </a>

            <a href="pesan.php">
                💬 Pesan Masuk
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </div>

        <div class="catatan">
            💡 Pendapatan QRIS dan BANK dihitung setelah pembayaran berhasil
            diverifikasi admin. Pembayaran COD dihitung setelah pesanan selesai
            dan uang diterima. Pesanan yang masih menunggu tidak dihitung.
        </div>

    </div>

</div>

</body>
</html>