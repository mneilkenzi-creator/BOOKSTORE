
<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID pesanan tidak valid.");
}

$query = mysqli_query($koneksi, "
    SELECT * FROM pesanan
    WHERE id = $id AND user_id = $user_id
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) === 0) {
    die("Pesanan tidak ditemukan.");
}

$pesanan = mysqli_fetch_assoc($query);

$detail = mysqli_query($koneksi, "
    SELECT dp.*, b.judul
    FROM detail_pesanan dp
    JOIN buku b ON b.id = dp.buku_id
    WHERE dp.pesanan_id = $id
");

if (!$detail) {
    die("Gagal mengambil detail: " . mysqli_error($koneksi));
}

$metode = strtoupper($pesanan['metode_pembayaran'] ?? '');

if ($metode === 'QRIS' || $metode === 'BANK') {
    $status_bayar = "Menunggu Verifikasi";
} else {
    $status_bayar = "Belum Dibayar (COD)";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pesanan - NduttStore</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6fb;
    color: #30204c;
}

.navbar {
    background: linear-gradient(90deg,#7739b4,#d94fa4);
    padding: 20px 7%;
    color: white;
    font-size: 23px;
    font-weight: bold;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 15px;
}

.back {
    display: inline-block;
    color: #853fc0;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
}

h1 {
    margin-bottom: 8px;
}

.subtitle {
    color: #888;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 18px;
    margin-bottom: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}

.card h2 {
    margin-top: 0;
    font-size: 19px;
}

.row-info {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 13px 0;
    border-bottom: 1px solid #eee;
}

.row-info:last-child {
    border-bottom: none;
}

.row-info span {
    color: #888;
}

.row-info strong {
    text-align: right;
    overflow-wrap: anywhere;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 14px 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    color: #853fc0;
    background: #faf5ff;
}

.total {
    text-align: right;
    color: #e052a8;
    font-size: 23px;
    font-weight: bold;
    margin-top: 20px;
}

.status {
    display: inline-block;
    padding: 8px 13px;
    border-radius: 20px;
    background: #fff1d5;
    color: #a56b00;
    font-size: 13px;
}

.buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 14px 22px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    text-align: center;
}

.btn-primary {
    color: white;
    background: linear-gradient(90deg,#853fc0,#e052a8);
}

.btn-secondary {
    background: #eee8f5;
    color: #7138b8;
}

@media(max-width:600px) {
    .card { padding: 17px; }
    table { font-size: 12px; }
    th, td { padding: 10px 5px; }
    .row-info { font-size: 13px; }
}
</style>
</head>

<body>

<div class="navbar">📚 NduttStore</div>

<div class="container">

<a href="pesanan.php" class="back">← Kembali ke Pesanan Saya</a>

<h1>Detail Pesanan 📦</h1>
<p class="subtitle">Informasi lengkap pesanan kamu.</p>

<div class="card">
    <h2>Informasi Pesanan</h2>

    <div class="row-info">
        <span>Nomor Pesanan</span>
        <strong><?= htmlspecialchars($pesanan['nomor_pesanan']) ?></strong>
    </div>

    <div class="row-info">
        <span>Tanggal Pesanan</span>
        <strong><?= htmlspecialchars($pesanan['tanggal_pesanan']) ?></strong>
    </div>

    <div class="row-info">
        <span>Nama Penerima</span>
        <strong><?= htmlspecialchars($pesanan['nama_penerima']) ?></strong>
    </div>

    <div class="row-info">
        <span>Nomor Telepon</span>
        <strong><?= htmlspecialchars($pesanan['no_hp']) ?></strong>
    </div>

    <div class="row-info">
        <span>Alamat Pengiriman</span>
        <strong><?= nl2br(htmlspecialchars($pesanan['alamat'])) ?></strong>
    </div>
</div>

<div class="card">
    <h2>Daftar Buku yang Dibeli 📚</h2>

    <table>
        <thead>
            <tr>
                <th>Nama Buku</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($detail) > 0): ?>
            <?php while ($buku = mysqli_fetch_assoc($detail)):
                $harga = (float) $buku['harga'];
                $jumlah = (int) $buku['jumlah'];
                $subtotal = $harga * $jumlah;
            ?>
            <tr>
                <td><?= htmlspecialchars($buku['judul']) ?></td>
                <td>Rp<?= number_format($harga,0,',','.') ?></td>
                <td><?= $jumlah ?></td>
                <td>Rp<?= number_format($subtotal,0,',','.') ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">Belum ada detail buku.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="total">
        Total: Rp<?= number_format((float)$pesanan['total_harga'],0,',','.') ?>
    </div>
</div>

<div class="card">
    <h2>Informasi Pembayaran 💳</h2>

    <div class="row-info">
        <span>Metode Pembayaran</span>
        <strong><?= htmlspecialchars($metode) ?></strong>
    </div>

    <div class="row-info">
        <span>Status Pembayaran</span>
        <strong><?= htmlspecialchars($status_bayar) ?></strong>
    </div>

    <div class="row-info">
        <span>Status Pesanan</span>
        <strong><span class="status"><?= htmlspecialchars($pesanan['status']) ?></span></strong>
    </div>

    <div class="buttons">
        <a class="btn btn-primary"
           href="bukti_pembayaran.php?nomor=<?= urlencode($pesanan['nomor_pesanan']) ?>">
            🧾 Lihat Struk
        </a>

        <a class="btn btn-secondary" href="pesanan.php">
            Kembali
        </a>
    </div>
</div>

</div>
</body>
</html>
