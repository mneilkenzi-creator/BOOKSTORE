```php
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama = htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        body {
            background: #f8f7fc;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #25213a;
        }

        .about-hero {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            text-align: center;
            padding: 85px 20px;
        }

        .about-hero h1 {
            font-size: 43px;
            margin-bottom: 15px;
        }

        .about-hero p {
            font-size: 17px;
            max-width: 650px;
            margin: auto;
            line-height: 1.8;
        }

        .about-container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .about-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(50, 22, 95, .07);
            margin-bottom: 25px;
            line-height: 1.8;
        }

        .about-card h2 {
            color: #7041d3;
            margin-bottom: 15px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 25px;
        }

        .about-feature {
            background: white;
            border-radius: 18px;
            padding: 28px 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(50, 22, 95, .06);
        }

        .about-feature .icon {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .about-feature h3 {
            color: #32165f;
            margin-bottom: 10px;
        }

        .about-feature p {
            color: #777;
            font-size: 14px;
            line-height: 1.7;
        }

        .about-footer {
            background: #321451;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
        }

        @media(max-width: 700px) {
            .about-hero h1 {
                font-size: 32px;
            }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .about-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<section class="about-hero">
    <h1>Tentang NduttStore</h1>
    <p>
        Selamat datang di NduttStore, tempat menemukan berbagai buku
        menarik untuk menambah pengetahuan, inspirasi, dan pengalaman membaca.
    </p>
</section>

<main class="about-container">

    <section class="about-card">
        <h2>Siapa Kami?</h2>
        <p>
            NduttStore merupakan toko buku online yang dibuat untuk
            memberikan pengalaman berbelanja buku secara praktis dan nyaman.
            Kami menyediakan berbagai pilihan buku yang dapat dijelajahi
            pelanggan melalui website.
        </p>
        <p>
            Melalui NduttStore, pelanggan dapat melihat katalog buku,
            memasukkan buku ke keranjang, melakukan pemesanan, dan melihat
            riwayat pesanan dalam satu tempat.
        </p>
    </section>

    <section class="about-card">
        <h2>Visi Kami</h2>
        <p>
            Menjadi toko buku online yang mudah digunakan dan membantu
            masyarakat mendapatkan akses terhadap berbagai bacaan yang
            bermanfaat.
        </p>
    </section>

    <section>
        <h2 style="text-align:center;color:#32165f;">
            Mengapa Memilih NduttStore?
        </h2>

        <div class="about-grid">

            <div class="about-feature">
                <div class="icon">📚</div>
                <h3>Pilihan Buku</h3>
                <p>
                    Jelajahi berbagai koleksi buku sesuai minat dan kebutuhan.
                </p>
            </div>

            <div class="about-feature">
                <div class="icon">🛒</div>
                <h3>Belanja Praktis</h3>
                <p>
                    Pilih buku, masukkan ke keranjang, dan lakukan pemesanan
                    dengan mudah.
                </p>
            </div>

            <div class="about-feature">
                <div class="icon">💳</div>
                <h3>Pilihan Pembayaran</h3>
                <p>
                    Tersedia pilihan metode pembayaran sesuai fitur yang
                    disediakan toko.
                </p>
            </div>

        </div>
    </section>

</main>

<footer class="about-footer">
    <h3>NduttStore.</h3>
    <p>Temukan buku favoritmu, mulai pengalaman membaca yang baru.</p>
    <p>&copy; <?= date('Y'); ?> NduttStore. All Rights Reserved.</p>
</footer>

</body>
</html>