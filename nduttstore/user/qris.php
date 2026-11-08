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

if ($_SESSION['metode_pembayaran'] !== 'QRIS') {
    header("Location: pembayaran.php");
    exit;
}

$total = (int) ($_SESSION['checkout']['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QRIS - NduttStore</title>
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
            text-align: center;
        }

        .payment-title h1 {
            color: #32165f;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .payment-title p {
            color: #777;
            margin-bottom: 25px;
        }

        .total-box {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            padding: 22px;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .total-box small {
            display: block;
            margin-bottom: 8px;
        }

        .total-box h2 {
            font-size: 30px;
        }

        .qris-box {
            border: 1px solid #eee7f7;
            border-radius: 18px;
            padding: 25px;
            background: #fdfbff;
            margin-bottom: 20px;
        }

        .qris-label {
            font-size: 22px;
            font-weight: 800;
            color: #32165f;
            margin-bottom: 18px;
        }

        .qris-image {
            width: 340px;
            height: auto;
            max-width: 100%;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 10px;
            background: white;
        }

        .qris-placeholder {
            width: 260px;
            height: 260px;
            max-width: 100%;
            margin: auto;
            border: 2px dashed #c9b7e9;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #7041d3;
            background: white;
            padding: 20px;
            box-sizing: border-box;
        }

        .qris-placeholder strong {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .qris-placeholder span {
            font-size: 13px;
            color: #777;
        }

        .notice {
            background: #fff8e9;
            border: 1px solid #ffe4a8;
            color: #79520a;
            padding: 15px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            text-align: left;
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
        }

        .btn-confirm:hover {
            opacity: .9;
        }

        .btn-back {
            display: block;
            margin-top: 15px;
            color: #7041d3;
            text-decoration: none;
            font-weight: 600;
        }

        @media(max-width: 600px) {
            .payment-card {
                padding: 22px;
            }

            .total-box h2 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="payment-container">
    <div class="payment-card">

        <div class="payment-title">
            <h1>Pembayaran QRIS</h1>
            <p>Scan kode QRIS menggunakan aplikasi pembayaran yang mendukung QRIS.</p>
        </div>

        <div class="total-box">
            <small>Total yang harus dibayar</small>
            <h2>Rp <?= number_format($total, 0, ',', '.'); ?></h2>
        </div>

        <div class="qris-box">
            <div class="qris-label">NDUTTSTORE QRIS</div>

            <?php $qrisPath = __DIR__ . "/../assets/img/qris.jpeg"; ?>
            <?php if (file_exists($qrisPath) && filesize($qrisPath) > 0): ?>

                <img
                    src="../assets/img/qris.jpeg?v=<?= filemtime($qrisPath); ?>"
                    alt="QRIS NduttStore"
                    class="qris-image"
                >

            <?php else: ?>

                <div class="qris-placeholder">
                    <strong>QRIS</strong>
                    <span>File qris.jpeg belum ditemukan atau kosong.</span>
                    <span>Letakkan gambar di assets/img/qris.jpeg</span>
                </div>

            <?php endif; ?>
        </div>

        <div class="notice">
            <strong>Petunjuk pembayaran:</strong>
            <ol>
                <li>Buka aplikasi pembayaran yang mendukung QRIS.</li>
                <li>Scan kode QRIS toko yang resmi.</li>
                <li>Periksa nama penerima dan nominal pembayaran.</li>
                <li>Selesaikan pembayaran melalui aplikasi tersebut.</li>
                <li>Simpan bukti pembayaran untuk diperiksa admin.</li>
            </ol>

            <br>

            <strong>Catatan:</strong>
            Tombol di bawah hanya mengirim pesanan,
            bukan memverifikasi pembayaran secara otomatis.
        </div>

        <form action="proses_pesanan.php" method="POST"
              onsubmit="return confirm('Kirim pesanan dengan metode QRIS? Pastikan pembayaran sudah dilakukan.')">

            <input type="hidden" name="konfirmasi_qris" value="1">

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