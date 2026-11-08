<?php
require_once __DIR__ . "/../config/koneksi.php";

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$nomor_pesanan = $_SESSION['nomor_pesanan'] ?? '';

if ($nomor_pesanan === '') {
    header("Location: pesanan.php");
    exit;
}

// AMBIL PESANAN MILIK USER
$stmt = mysqli_prepare($koneksi, "
    SELECT *
    FROM pesanan
    WHERE nomor_pesanan = ?
    AND user_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Gagal menyiapkan data pesanan.");
}

mysqli_stmt_bind_param($stmt, "si", $nomor_pesanan, $user_id);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

$pesanan_id = (int) $pesanan['id'];

// AMBIL DETAIL BUKU
$stmt_detail = mysqli_prepare($koneksi, "
    SELECT dp.*, b.judul
    FROM detail_pesanan dp
    LEFT JOIN buku b ON b.id = dp.buku_id
    WHERE dp.pesanan_id = ?
");

if (!$stmt_detail) {
    die("Gagal mengambil detail buku.");
}

mysqli_stmt_bind_param($stmt_detail, "i", $pesanan_id);
mysqli_stmt_execute($stmt_detail);

$detail = mysqli_stmt_get_result($stmt_detail);

$metode = strtoupper($pesanan['metode_pembayaran'] ?? '');

if ($metode === 'QRIS' || $metode === 'BANK') {
    $status_pembayaran = $pesanan['status_pembayaran'] ?? 'Menunggu Verifikasi';
} else {
    $status_pembayaran = $pesanan['status_pembayaran'] ?? 'Belum Dibayar';
}

$tanggal = $pesanan['tanggal_pesanan'] ?? '-';

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pesanan Berhasil - NduttStore</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #f2e7ff, #ffe5f2);
    color: #38234e;
}

.navbar {
    background: linear-gradient(90deg, #7739b4, #d94fa4);
    padding: 20px 7%;
    color: white;
    font-size: 23px;
    font-weight: bold;
}

.container {
    max-width: 850px;
    margin: 45px auto;
    padding: 15px;
}

.card {
    background: white;
    border-radius: 22px;
    padding: 35px;
    box-shadow: 0 10px 35px rgba(70,30,100,.12);
}

.success {
    text-align: center;
    padding: 20px;
}

.success-icon {
    width: 85px;
    height: 85px;
    background: #e7faed;
    color: #20a45a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
    margin: auto auto 20px;
}

h1 {
    color: #7138b8;
    margin-bottom: 10px;
}

.subtitle {
    color: #888;
    line-height: 1.6;
}

.order-box {
    background: #faf5ff;
    border-radius: 15px;
    padding: 22px;
    margin-top: 25px;
}

.order-row {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #eadff4;
}

.order-row:last-child {
    border-bottom: none;
}

.order-row span {
    color: #777;
}

.order-row strong {
    text-align: right;
    overflow-wrap: anywhere;
}

.status {
    display: inline-block;
    padding: 8px 13px;
    border-radius: 20px;
    background: #fff0d4;
    color: #9b6500;
    font-size: 13px;
}

.total {
    color: #853fc0;
    font-size: 22px;
}

.buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
}

.btn {
    flex: 1;
    min-width: 180px;
    padding: 15px;
    text-align: center;
    text-decoration: none;
    border-radius: 12px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    font-size: 15px;
}

.btn-primary {
    background: linear-gradient(90deg, #853fc0, #e052a8);
    color: white;
}

.btn:hover {
    opacity: .88;
}

.note {
    text-align: center;
    color: #888;
    font-size: 13px;
    margin-top: 25px;
    line-height: 1.6;
}

@media(max-width: 600px) {
    .card {
        padding: 20px;
    }

    .order-row {
        font-size: 14px;
    }
}
</style>
</head>

<body>

<div class="navbar">📚 NduttStore.</div>

<div class="container">
<div class="card">

    <div class="success">
        <div class="success-icon">✓</div>

        <h1>Pesanan Berhasil Dibuat!</h1>

        <p class="subtitle">
            Terima kasih sudah berbelanja di NduttStore.
            Pesanan kamu sudah masuk ke sistem kami.
        </p>
    </div>

    <div class="order-box">

        <h3>📦 Informasi Pesanan</h3>

        <div class="order-row">
            <span>Nomor Pesanan</span>
            <strong><?= aman($pesanan['nomor_pesanan']) ?></strong>
        </div>

        <div class="order-row">
            <span>Nama Penerima</span>
            <strong><?= aman($pesanan['nama_penerima']) ?></strong>
        </div>

        <div class="order-row">
            <span>Nomor Telepon</span>
            <strong><?= aman($pesanan['no_hp']) ?></strong>
        </div>

        <div class="order-row">
            <span>Metode Pembayaran</span>
            <strong><?= aman($metode) ?></strong>
        </div>

        <div class="order-row">
            <span>Tanggal Pesanan</span>
            <strong><?= aman($tanggal) ?></strong>
        </div>

        <div class="order-row">
            <span>Total Pembayaran</span>
            <strong class="total">
                Rp<?= number_format((float)$pesanan['total_harga'], 0, ',', '.') ?>
            </strong>
        </div>

        <div class="order-row">
            <span>Status Pembayaran</span>
            <strong>
                <span class="status"><?= aman($status_pembayaran) ?></span>
            </strong>
        </div>

    </div>

    <div class="order-box">

        <h3>📚 Detail Buku</h3>

        <?php if (mysqli_num_rows($detail) > 0): ?>

            <?php while ($buku = mysqli_fetch_assoc($detail)): ?>

                <div class="order-row">
                    <span>
                        <?= aman($buku['judul'] ?? 'Buku tidak tersedia') ?>
                        × <?= (int)$buku['jumlah'] ?>
                    </span>

                    <strong>
                        Rp<?= number_format(
                            (float)$buku['harga'] * (int)$buku['jumlah'],
                            0, ',', '.'
                        ) ?>
                    </strong>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Detail buku belum tersedia.</p>

        <?php endif; ?>

    </div>

    <?php mysqli_stmt_close($stmt_detail); ?>

    <!-- SATU TOMBOL SAJA -->
    <div class="buttons">

        <a class="btn btn-primary" href="pesanan.php">
            📦 PESANAN SAYA
        </a>

    </div>

    <p class="note">
        Simpan informasi pesanan kamu.
        Pembayaran QRIS dan Transfer Bank masih menunggu verifikasi admin.
    </p>

</div>
</div>

</body>
</html>