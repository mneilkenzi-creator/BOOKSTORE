
<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['checkout'])) {
    header("Location: dashboard.php");
    exit;
}

$checkout = $_SESSION['checkout'];

$nama = $checkout['nama'] ?? '';
$telepon = $checkout['telepon'] ?? '';
$alamat = $checkout['alamat'] ?? '';
$total = (int)($checkout['total'] ?? 0);

$metode = $_SESSION['metode_pembayaran'] ?? 'COD';
$status = $_SESSION['status_pembayaran'] ?? 'menunggu_verifikasi';

$nomor_pesanan = $_SESSION['nomor_pesanan'] ?? '-';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pembayaran Berhasil - NduttStore</title>

<style>
*{box-sizing:border-box;margin:0;padding:0}

body{
    font-family:Arial,sans-serif;
    min-height:100vh;
    background:linear-gradient(135deg,#f1e7ff,#ffe5f3);
    color:#39234f;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.card{
    background:white;
    max-width:550px;
    width:100%;
    padding:40px 30px;
    border-radius:25px;
    text-align:center;
    box-shadow:0 12px 35px #542b7820;
}

.success-icon{
    width:100px;
    height:100px;
    border-radius:50%;
    background:#e6f9ed;
    color:#20a45b;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:55px;
    margin:0 auto 22px;
}

h1{
    color:#803eb8;
    margin-bottom:12px;
}

.subtitle{
    color:#888;
    line-height:1.6;
}

.info{
    background:#faf5ff;
    padding:20px;
    border-radius:15px;
    text-align:left;
    margin:25px 0;
}

.info h3{
    color:#853fc0;
    margin-bottom:15px;
}

.row{
    display:flex;
    justify-content:space-between;
    gap:15px;
    margin:13px 0;
    font-size:14px;
}

.row span:first-child{
    color:#888;
}

.row strong{
    text-align:right;
    overflow-wrap:anywhere;
}

.total{
    border-top:1px solid #e5d9f0;
    padding-top:15px;
    font-size:19px;
    color:#853fc0;
}

.status{
    background:#fff4dc;
    color:#865b12;
    padding:13px;
    border-radius:10px;
    font-size:13px;
    margin-bottom:20px;
    line-height:1.5;
}

button{
    width:100%;
    padding:16px;
    border:0;
    border-radius:12px;
    background:linear-gradient(90deg,#853fc0,#df54a7);
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
}

button:hover{opacity:.9}

.footer{
    color:#aaa;
    font-size:12px;
    margin-top:22px;
}
</style>
</head>

<body>

<div class="card">

    <div class="success-icon">✓</div>

    <h1>Pesanan Berhasil!</h1>

    <p class="subtitle">
        Terima kasih sudah berbelanja di NduttStore.
        Pesanan kamu berhasil dikirim untuk diproses.
    </p>

    <div class="info">

        <h3>📚 Detail Pesanan</h3>

        <div class="row">
            <span>Nomor Pesanan</span>
            <strong><?= htmlspecialchars((string)$nomor_pesanan) ?></strong>
        </div>

        <div class="row">
            <span>Nama Penerima</span>
            <strong><?= htmlspecialchars($nama) ?></strong>
        </div>

        <div class="row">
            <span>Telepon</span>
            <strong><?= htmlspecialchars($telepon) ?></strong>
        </div>

        <div class="row">
            <span>Alamat</span>
            <strong><?= htmlspecialchars($alamat) ?></strong>
        </div>

        <div class="row">
            <span>Metode Pembayaran</span>
            <strong><?= htmlspecialchars($metode) ?></strong>
        </div>

        <div class="row total">
            <span>Total</span>
            <strong>Rp<?= number_format($total,0,',','.') ?></strong>
        </div>

    </div>

    <div class="status">
        <?php if ($metode === 'QRIS'): ?>
            Status pembayaran: Menunggu verifikasi admin.
        <?php else: ?>
            Status pembayaran: Bayar saat pesanan diterima (COD).
        <?php endif; ?>
    </div>

    <button onclick="window.location.href='dashboard.php'">
        Kembali ke Beranda →
    </button>

    <div class="footer">
        © 2026 NduttStore | Happy Reading 📚
    </div>

</div>

</body>
</html>
