```php
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* Ambil data keranjang */
$query = mysqli_query(
    $koneksi,
    "SELECT
        k.id AS id_keranjang,
        k.buku_id,
        k.jumlah,
        b.judul,
        b.harga,
        b.gambar,
        b.stok
     FROM keranjang k
     INNER JOIN buku b ON k.buku_id = b.id
     WHERE k.user_id = $user_id
     ORDER BY k.id DESC"
);

$items = [];
$total = 0;

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $items[] = $row;
        $total += (int) $row['harga'] * (int) $row['jumlah'];
    }
}

if (count($items) === 0) {
    header("Location: keranjang.php");
    exit;
}

/* Ambil data pengguna jika tersedia */
$nama_user = $_SESSION['nama'] ?? '';
$no_hp_user = '';

$query_user = mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $user_id LIMIT 1"
);

if ($query_user && mysqli_num_rows($query_user) > 0) {
    $data_user = mysqli_fetch_assoc($query_user);
    $nama_user = $data_user['nama'] ?? $nama_user;
    $no_hp_user = $data_user['no_hp'] ?? '';
}

$error = '';

/* Proses form checkout */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $telepon === '' || $alamat === '') {
        $error = "Semua data pengiriman wajib diisi.";
    } else {
        // Periksa stok terbaru sebelum melanjutkan
        $stok_valid = true;

        foreach ($items as $item) {
            $stok = (int) $item['stok'];
            $jumlah = (int) $item['jumlah'];

            if ($jumlah <= 0 || $jumlah > $stok) {
                $stok_valid = false;
                break;
            }
        }

        if (!$stok_valid) {
            $error = "Ada jumlah buku yang melebihi stok tersedia. Periksa keranjang kamu.";
        } else {
            $_SESSION['checkout'] = [
                'nama' => $nama,
                'telepon' => $telepon,
                'alamat' => $alamat,
                'total' => $total
            ];

            header("Location: pembayaran.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .checkout-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 22px;
        }

        .checkout-heading {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 28px;
        }

        .checkout-heading h1 {
            font-size: 29px;
            margin-bottom: 8px;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .checkout-card {
            background: white;
            padding: 27px;
            border-radius: 17px;
            box-shadow: 0 5px 22px rgba(40, 20, 70, .07);
        }

        .checkout-card h2 {
            color: #321451;
            margin-bottom: 22px;
            font-size: 21px;
        }

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            color: #444;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 15px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #7041d3;
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        .checkout-total {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #eee;
            color: #321451;
            font-size: 19px;
            font-weight: 700;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 9px;
            margin-top: 22px;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: #7041d3;
            text-decoration: none;
            font-weight: 600;
        }

        .error-message {
            background: #fff0f0;
            color: #c62828;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        @media(max-width: 800px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<main class="checkout-container">

    <section class="checkout-heading">
        <h1>Checkout Pesanan 📦</h1>
        <p>Lengkapi alamat pengiriman sebelum memilih metode pembayaran.</p>
    </section>

    <div class="checkout-layout">

        <section class="checkout-card">
            <h2>Informasi Pengiriman</h2>

            <?php if ($error !== ''): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php">

                <div class="form-group">
                    <label>Nama Penerima</label>
                    <input
                        type="text"
                        name="nama"
                        placeholder="Masukkan nama penerima"
                        value="<?= htmlspecialchars($_POST['nama'] ?? $nama_user); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input
                        type="tel"
                        name="telepon"
                        placeholder="Contoh: 081234567890"
                        value="<?= htmlspecialchars($_POST['telepon'] ?? $no_hp_user); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea
                        name="alamat"
                        placeholder="Masukkan alamat lengkap pengiriman"
                        required
                    ><?= htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-checkout">
                    Lanjut ke Pembayaran →
                </button>

            </form>

            <a href="keranjang.php" class="btn-back">
                ← Kembali ke Keranjang
            </a>
        </section>

        <aside class="checkout-card">
            <h2>Ringkasan Pesanan</h2>

            <?php foreach ($items as $item): ?>
                <div class="checkout-item">
                    <span>
                        <?= htmlspecialchars($item['judul']); ?>
                        × <?= (int) $item['jumlah']; ?>
                    </span>

                    <strong>
                        Rp <?= number_format(
                            (int) $item['harga'] * (int) $item['jumlah'],
                            0,
                            ',',
                            '.'
                        ); ?>
                    </strong>
                </div>
            <?php endforeach; ?>

            <div class="checkout-total">
                <span>Total</span>
                <span>
                    Rp <?= number_format($total, 0, ',', '.'); ?>
                </span>
            </div>
        </aside>

    </div>

</main>

</body>
</html>