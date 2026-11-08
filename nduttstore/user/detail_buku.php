```php
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_buku = 0;

if (isset($_GET['id'])) {
    $id_buku = (int) $_GET['id'];
} elseif (isset($_GET['buku_id'])) {
    $id_buku = (int) $_GET['buku_id'];
}

if ($id_buku <= 0) {
    header("Location: buku.php");
    exit;
}

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM buku WHERE id = $id_buku LIMIT 1"
);

if (!$query || mysqli_num_rows($query) === 0) {
    header("Location: buku.php");
    exit;
}

$buku = mysqli_fetch_assoc($query);

$judul = $buku['judul'] ?? 'Buku';
$harga = (int) ($buku['harga'] ?? 0);
$gambar = $buku['gambar'] ?? '';
$penulis = $buku['penulis'] ?? 'Tidak diketahui';
$deskripsi = $buku['deskripsi'] ?? 'Belum ada deskripsi buku.';
$stok = isset($buku['stok']) ? (int) $buku['stok'] : 0;

$pesan = '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .detail-container {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 25px;
        }

        .detail-card {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 40px;
            background: white;
            padding: 35px;
            border-radius: 22px;
            box-shadow: 0 8px 30px rgba(40, 20, 70, .08);
        }

        .detail-image {
            width: 100%;
            height: 460px;
            object-fit: contain;
            background: #f8f4fc;
            border-radius: 15px;
        }

        .detail-info {
            padding: 10px 0;
        }

        .detail-info h1 {
            color: #321451;
            font-size: 30px;
            margin-bottom: 18px;
        }

        .detail-info .penulis {
            color: #777;
            margin-bottom: 18px;
        }

        .detail-info .harga {
            color: #ed4ca4;
            font-size: 27px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .detail-info h3 {
            color: #321451;
            margin-bottom: 10px;
        }

        .detail-info .deskripsi {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
            white-space: pre-line;
        }

        .stok-info {
            margin-bottom: 22px;
            color: #555;
        }

        .form-keranjang {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-keranjang input {
            width: 85px;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
        }

        .btn-keranjang {
            border: none;
            padding: 14px 22px;
            border-radius: 9px;
            color: white;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            font-weight: 600;
            cursor: pointer;
        }

        .btn-kembali {
            display: inline-block;
            margin-top: 22px;
            color: #7041d3;
            text-decoration: none;
            font-weight: 600;
        }

        .stok-habis {
            color: #d33;
            font-weight: 600;
        }

        @media(max-width: 750px) {
            .detail-card {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .detail-image {
                height: 350px;
            }

            .detail-info h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<main class="detail-container">

    <section class="detail-card">

        <div>
            <?php if (!empty($gambar)): ?>
                <img
                    class="detail-image"
                    src="../assets/img/<?= htmlspecialchars($gambar); ?>"
                    alt="<?= htmlspecialchars($judul); ?>"
                    onerror="this.src='https://placehold.co/400x500?text=NduttStore'"
                >
            <?php else: ?>
                <img
                    class="detail-image"
                    src="https://placehold.co/400x500?text=NduttStore"
                    alt="Sampul buku"
                >
            <?php endif; ?>
        </div>

        <div class="detail-info">

            <h1><?= htmlspecialchars($judul); ?></h1>

            <p class="penulis">
                ✍️ Penulis: <?= htmlspecialchars($penulis); ?>
            </p>

            <div class="harga">
                Rp <?= number_format($harga, 0, ',', '.'); ?>
            </div>

            <h3>Deskripsi Buku</h3>

            <p class="deskripsi">
                <?= htmlspecialchars($deskripsi); ?>
            </p>

            <p class="stok-info">
                📦 Stok tersedia:
                <strong><?= $stok; ?></strong>
            </p>

            <?php if ($stok > 0): ?>

                <form action="cart.php" method="POST" class="form-keranjang">

                    <input type="hidden" name="id_buku" value="<?= $id_buku; ?>">

                    <input type="hidden" name="id" value="<?= $id_buku; ?>">

                    <input
                        type="number"
                        name="jumlah"
                        value="1"
                        min="1"
                        max="<?= $stok; ?>"
                        required
                    >

                    <button type="submit" class="btn-keranjang">
                        🛒 Tambah ke Keranjang
                    </button>

                </form>

            <?php else: ?>

                <p class="stok-habis">Maaf, stok buku sedang habis.</p>

            <?php endif; ?>

            <a href="buku.php" class="btn-kembali">
                ← Kembali ke Daftar Buku
            </a>

        </div>

    </section>

</main>

</body>
</html>