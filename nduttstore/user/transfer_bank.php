<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['checkout']) || !isset($_SESSION['metode_pembayaran'])) {
    header("Location: keranjang.php");
    exit;
}

if ($_SESSION['metode_pembayaran'] !== 'BANK') {
    header("Location: pembayaran.php");
    exit;
}

$total = (int) ($_SESSION['checkout']['total'] ?? 0);
$nama = htmlspecialchars($_SESSION['checkout']['nama'] ?? 'Pelanggan');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Bank - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        body {
            background: #f8f7fc;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #25213a;
        }

        .payment-container {
            max-width: 750px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .payment-card {
            background: white;
            padding: 35px;
            border-radius: 22px;
            box-shadow: 0 10px 35px rgba(50, 22, 95, .09);
        }

        .payment-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .payment-title h1 {
            color: #32165f;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .payment-title p {
            color: #777;
        }

        .total-box {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            text-align: center;
            padding: 22px;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .total-box small {
            display: block;
            margin-bottom: 8px;
            opacity: .9;
        }

        .total-box h2 {
            font-size: 30px;
        }

        .bank-box {
            border: 1px solid #eee7f7;
            border-radius: 17px;
            padding: 25px;
            margin-bottom: 20px;
            background: #fdfbff;
        }

        .bank-logo {
            font-size: 25px;
            font-weight: 800;
            color: #174a91;
            margin-bottom: 18px;
        }

        .bank-label {
            color: #777;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .bank-number {
            font-size: 25px;
            font-weight: 800;
            color: #32165f;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .bank-owner {
            font-size: 17px;
            font-weight: 700;
        }

        .notice {
            background: #fff8e9;
            border: 1px solid #ffe4a8;
            color: #79520a;
            padding: 15px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .btn-confirm {
            display: block;
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 12px;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .btn-confirm:hover {
            opacity: .9;
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #7041d3;
            text-decoration: none;
            font-weight: 600;
        }

        @media(max-width: 600px) {
            .payment-card {
                padding: 22px;
            }

            .bank-number {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="payment-container">
    <div class="payment-card">

        <div class="payment-title">
            <h1>Transfer Bank</h1>
            <p>Silakan transfer sesuai nominal pembayaran.</p>
        </div>

        <div class="total-box">
            <small>Total yang harus dibayar</small>
            <h2>Rp <?= number_format($total, 0, ',', '.'); ?></h2>
        </div>

        <div class="bank-box">
            <div class="bank-logo">BCA</div>

            <div class="bank-label">Nomor Rekening</div>
            <div class="bank-number">1234567890</div>

            <div class="bank-label">Atas Nama</div>
            <div class="bank-owner">NDUTTSTORE</div>
        </div>

        <div class="notice">
            <strong>Perhatian:</strong><br>
            Nomor rekening di atas adalah contoh/demo. Ganti dengan rekening
            resmi milik toko sebelum menerima pembayaran sungguhan.
            Konfirmasi pembayaran harus diperiksa oleh admin.
        </div>

        <form action="proses_pesanan.php" method="POST"
              onsubmit="return confirm('Kirim pesanan dengan metode transfer bank?')">

            <input type="hidden" name="konfirmasi_transfer" value="1">

            <button type="submit" class="btn-confirm">
                Konfirmasi Pesanan
            </button>
        </form>

        <a href="pembayaran.php" class="btn-back">
            ← Kembali ke Metode Pembayaran
        </a>

    </div>
</div>

</body>
</html>