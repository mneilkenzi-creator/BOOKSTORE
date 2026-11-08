<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$nama_user = $_SESSION['nama'] ?? 'Pelanggan';

/* Ambil data user */
$query_user = mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $user_id LIMIT 1"
);

if ($query_user && mysqli_num_rows($query_user) > 0) {
    $data_user = mysqli_fetch_assoc($query_user);
    $nama_user = $data_user['nama'] ?? $nama_user;
}

/* Hitung keranjang */
$total_keranjang = 0;
$query_cart = mysqli_query(
    $koneksi,
    "SELECT COALESCE(SUM(jumlah), 0) AS total
     FROM keranjang
     WHERE user_id = $user_id"
);

if ($query_cart) {
    $cart_data = mysqli_fetch_assoc($query_cart);
    $total_keranjang = (int) $cart_data['total'];
}

/* Hitung pesanan */
$total_pesanan = 0;
$query_pesanan = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE user_id = $user_id"
);

if ($query_pesanan) {
    $pesanan_data = mysqli_fetch_assoc($query_pesanan);
    $total_pesanan = (int) $pesanan_data['total'];
}

/* Ambil buku terbaru */
$query_buku = mysqli_query(
    $koneksi,
    "SELECT * FROM buku ORDER BY id DESC LIMIT 8"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .dashboard-user {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 25px;
        }

        .welcome-box {
            background: linear-gradient(120deg, #321451, #7041d3, #ed4ca4);
            color: white;
            padding: 42px;
            border-radius: 22px;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(75, 35, 120, .18);
        }

        .welcome-box h1 {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .welcome-box p {
            font-size: 15px;
            line-height: 1.7;
            opacity: .95;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(40, 20, 70, .06);
        }

        .stat-card .stat-icon {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .stat-card h3 {
            color: #777;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .stat-card strong {
            font-size: 30px;
            color: #321451;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 35px 0 20px;
        }

        .section-heading h2 {
            color: #321451;
            font-size: 24px;
        }

        .section-heading a {
            color: #7041d3;
            font-weight: 600;
            text-decoration: none;
        }

        .dashboard-book-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .dashboard-book-card {
            background: white;
            border-radius: 17px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(40, 20, 70, .07);
            transition: .25s;
        }

        .dashboard-book-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-book-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f0eaf7;
        }

        .book-info {
            padding: 17px;
        }

        .book-info h3 {
            font-size: 16px;
            color: #321451;
            margin-bottom: 8px;
        }

        .book-info p {
            color: #ed4ca4;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .book-info a {
            display: block;
            text-align: center;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            color: white;
            padding: 10px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: 600;
        }

        .empty-book {
            background: white;
            padding: 30px;
            border-radius: 15px;
            color: #777;
            text-align: center;
            grid-column: 1 / -1;
        }

        .dashboard-footer {
            text-align: center;
            padding: 30px 15px;
            color: #888;
            margin-top: 50px;
        }

        @media (max-width: 900px) {
            .dashboard-book-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .welcome-box {
                padding: 28px;
            }

            .welcome-box h1 {
                font-size: 25px;
            }

            .dashboard-book-grid {
                grid-template-columns: 1fr;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<main class="dashboard-user">

    <section class="welcome-box">
        <h1>Halo, <?= htmlspecialchars($nama_user); ?>! 👋</h1>
        <p>
            Selamat datang di NduttStore. Temukan berbagai buku menarik
            dan pesan buku favoritmu dengan mudah.
        </p>
    </section>

    <section class="dashboard-stats">

        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <h3>Buku di Keranjang</h3>
            <strong><?= $total_keranjang; ?></strong>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <h3>Total Pesanan</h3>
            <strong><?= $total_pesanan; ?></strong>
        </div>

    </section>

    <div class="section-heading">
        <h2>Rekomendasi Buku</h2>
        <a href="buku.php">Lihat Semua Buku →</a>
    </div>

    <section class="dashboard-book-grid">

        <?php if ($query_buku && mysqli_num_rows($query_buku) > 0): ?>

            <?php while ($buku = mysqli_fetch_assoc($query_buku)): ?>

                <?php
                $id_buku = (int) $buku['id'];
                $judul = $buku['judul'] ?? 'Buku tanpa judul';
                $harga = (int) ($buku['harga'] ?? 0);
                $gambar = $buku['gambar'] ?? '';
                ?>

                <article class="dashboard-book-card">

                    <?php if (!empty($gambar)): ?>
                        <img
                            src="../assets/img/<?= htmlspecialchars($gambar); ?>"
                            alt="<?= htmlspecialchars($judul); ?>"
                            onerror="this.src='https://placehold.co/400x500?text=NduttStore'"
                        >
                    <?php else: ?>
                        <img
                            src="https://placehold.co/400x500?text=NduttStore"
                            alt="Sampul buku"
                        >
                    <?php endif; ?>

                    <div class="book-info">
                        <h3><?= htmlspecialchars($judul); ?></h3>

                        <p>
                            Rp <?= number_format($harga, 0, ',', '.'); ?>
                        </p>

                        <a href="detail_buku.php?id=<?= $id_buku; ?>">
                            Lihat Detail
                        </a>
                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-book">
                Belum ada buku yang tersedia.
            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="dashboard-footer">
    © <?= date('Y'); ?> NduttStore. Semua Hak Dilindungi.
</footer>

</body>
</html>