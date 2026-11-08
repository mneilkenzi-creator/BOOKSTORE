```php
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['checkout']) || !is_array($_SESSION['checkout'])) {
    header("Location: keranjang.php");
    exit;
}

$checkout = $_SESSION['checkout'];
$total = (int) ($checkout['total'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = $_POST['metode_pembayaran'] ?? '';

    $metode_valid = ['COD', 'QRIS', 'BANK'];

    if (!in_array($metode, $metode_valid, true)) {
        $error = "Silakan pilih metode pembayaran yang tersedia.";
    } else {
        $_SESSION['metode_pembayaran'] = $metode;

        if ($metode === 'QRIS') {
            header("Location: qris.php");
            exit;
        }

        if ($metode === 'BANK') {
            header("Location: transfer_bank.php");
            exit;
        }

        if ($metode === 'COD') {
            header("Location: proses_pesanan.php");
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
    <title>Pembayaran - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .payment-container {
            max-width: 1050px;
            margin: 40px auto;
            padding: 0 22px;
        }

        .payment-heading {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 28px;
        }

        .payment-heading h1 {
            font-size: 29px;
            margin-bottom: 8px;
        }

        .payment-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
            align-items: start;
        }

        .payment-card,
        .payment-summary {
            background: white;
            padding: 27px;
            border-radius: 17px;
            box-shadow: 0 5px 22px rgba(40, 20, 70, .07);
        }

        .payment-card h2,
        .payment-summary h2 {
            color: #321451;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 19px;
            border: 1px solid #e8e0f0;
            border-radius: 13px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: .2s;
        }

        .payment-option:hover {
            border-color: #7041d3;
            background: #faf7ff;
        }

        .payment-option input {
            width: 18px;
            height: 18px;
            accent-color: #7041d3;
        }

        .payment-icon {
            font-size: 29px;
            width: 45px;
            text-align: center;
        }

        .payment-option h3 {
            color: #321451;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .payment-option p {
            color: #888;
            font-size: 13px;
        }

        .payment-total {
            color: #ed4ca4;
            font-size: 25px;
            font-weight: 700;
            margin: 15px 0;
        }

        .payment-note {
            color: #777;
            font-size: 13px;
            line-height: 1.7;
        }

        .btn-pay {
            width: 100%;
            border: none;
            padding: 14px;
            border-radius: 9px;
            color: white;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: #7041d3;
            text-decoration: none;
            font-weight: 600;
        }

        .payment-error {
            background: #fff0f0;
            color: #c62828;
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 18px;
        }

        @media(max-width: 800px) {
            .payment-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<main class="payment-container">

    <section class="payment-heading">
        <h1>Pilih Pembayaran 💳</h1>
        <p>Pilih metode pembayaran yang paling nyaman untukmu.</p>
    </section>

    <div class="payment-layout">

        <section class="payment-card">

            <h2>Metode Pembayaran</h2>

            <?php if ($error !== ''): ?>
                <div class="payment-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="pembayaran.php">

                <label class="payment-option">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="COD"
                        required
                    >
                    <span class="payment-icon">🚚</span>
                    <span>
                        <h3>Cash on Delivery (COD)</h3>
                        <p>Bayar langsung ketika pesanan diterima.</p>
                    </span>
                </label>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="QRIS"
                        required
                    >
                    <span class="payment-icon">📱</span>
                    <span>
                        <h3>QRIS</h3>
                        <p>Pembayaran melalui aplikasi yang mendukung QRIS.</p>
                    </span>
                </label>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="BANK"
                        required
                    >
                    <span class="payment-icon">🏦</span>
                    <span>
                        <h3>Transfer Bank</h3>
                        <p>Transfer melalui rekening bank.</p>
                    </span>
                </label>

                <button type="submit" class="btn-pay">
                    Lanjutkan Pembayaran →
                </button>

            </form>

            <a href="checkout.php" class="btn-back">
                ← Kembali ke Checkout
            </a>

        </section>

        <aside class="payment-summary">

            <h2>Total Pembayaran</h2>

            <p class="payment-note">
                Total yang harus dibayarkan:
            </p>

            <div class="payment-total">
                Rp <?= number_format($total, 0, ',', '.'); ?>
            </div>

            <p class="payment-note">
                Pastikan nominal pembayaran sesuai dengan ringkasan pesananmu.
            </p>

        </aside>

    </div>

</main>

</body>
</html>
```