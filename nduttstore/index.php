
<?php
require_once __DIR__ . "/config/koneksi.php";

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

// Ambil buku terbaru
$query_buku = mysqli_query($koneksi, "
    SELECT * FROM buku
    ORDER BY id DESC
    LIMIT 8
");

if (!$query_buku) {
    die("Gagal mengambil data buku: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NduttStore | Toko Buku Online</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">Ndutt<span>Store.</span></a>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="user/buku.php">Katalog Buku</a>
        <a href="user/about.php">About Us</a>
        <a href="user/contact.php">Contact</a>
    </div>

    <div class="nav-actions">
        <a href="user/login.php" class="login-btn">Login</a>
        <a href="user/register.php" class="register-btn">Daftar</a>
    </div>
</nav>

<header class="hero">
    <div class="hero-content">
        <span class="hero-label">WELCOME TO NDUTTSTORE</span>
        <h1>Temukan Dunia Baru<br>Lewat <span>Buku Favoritmu.</span></h1>
        <p>
            Jelajahi berbagai koleksi buku menarik.
            Temukan inspirasi, pengetahuan, dan cerita
            baru hanya di NduttStore.
        </p>
        <a href="user/buku.php" class="hero-btn">Jelajahi Koleksi →</a>
    </div>

    <div class="hero-decoration">
        <div class="book-icon">📚</div>
        <span>READ MORE, EXPLORE MORE</span>
    </div>
</header>

<section class="catalog">
    <div class="section-heading">
        <div>
            <span class="section-label">OUR COLLECTION</span>
            <h2>Buku Pilihan Terbaru</h2>
            <p>Temukan bacaan yang cocok untukmu.</p>
        </div>
        <a href="user/buku.php" class="see-all">Lihat Semua →</a>
    </div>

    <div class="book-grid">
        <?php if (mysqli_num_rows($query_buku) > 0): ?>
            <?php while ($buku = mysqli_fetch_assoc($query_buku)): ?>
                <article class="book-card">
                    <div class="book-image">
                        <?php if (!empty($buku['gambar'])): ?>
                            <img
                                src="assets/img/<?= htmlspecialchars($buku['gambar']) ?>"
                                alt="<?= htmlspecialchars($buku['judul']) ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                            >
                            <div class="image-fallback" style="display:none">📚</div>
                        <?php else: ?>
                            <div class="image-fallback">📚</div>
                        <?php endif; ?>
                    </div>

                    <div class="book-info">
                        <h3><?= htmlspecialchars($buku['judul']) ?></h3>
                        <p class="author">
                            <?= htmlspecialchars($buku['penulis']) ?>
                        </p>
                        <div class="book-bottom">
                            <strong>
                                Rp <?= number_format((float)$buku['harga'], 0, ',', '.') ?>
                            </strong>
                        </div>
                        <a
                            class="detail-btn"
                            href="user/detail_buku.php?id=<?= (int)$buku['id'] ?>"
                        >
                            Lihat Detail
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-books">
                <span>📖</span>
                <h3>Belum ada buku</h3>
                <p>Buku akan ditampilkan setelah admin menambah koleksi.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="promo">
    <h2>Satu Buku, Banyak Cerita.</h2>
    <p>Mulai petualangan membaca bersama NduttStore.</p>
    <a href="user/register.php">Gabung Sekarang →</a>
</section>

<footer>
    <h2>Ndutt<span>Store.</span></h2>
    <p>Tempat menemukan cerita dan pengetahuan baru.</p>
    <small>© 2026 NduttStore. All Rights Reserved.</small>
</footer>

</body>
</html>
