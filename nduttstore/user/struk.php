<?php
require_once __DIR__ . "/../config/koneksi.php";

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ambil ID pesanan dari URL atau nomor pesanan dari session
$id_pesanan = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nomor_session = $_SESSION['nomor_pesanan'] ?? '';

// Ambil data pesanan
if ($id_pesanan > 0) {
    $query = mysqli_prepare(
        $koneksi,
        "SELECT * FROM pesanan WHERE id = ? AND user_id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($query, "ii", $id_pesanan, $user_id);
} elseif ($nomor_session !== '') {
    $query = mysqli_prepare(
        $koneksi,
        "SELECT * FROM pesanan WHERE nomor_pesanan = ? AND user_id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($query, "si", $nomor_session, $user_id);
} else {
    header("Location: pesanan.php");
    exit;
}

mysqli_stmt_execute($query);
$hasil = mysqli_stmt_get_result($query);
$pesanan = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($query);

// Jika pesanan tidak ditemukan
if (!$pesanan) {
    header("Location: pesanan.php");
    exit;
}

$id_pesanan = (int) $pesanan['id'];

// Ambil detail buku dalam pesanan
$query_detail = mysqli_prepare(
    $koneksi,
    "SELECT detail_pesanan.*, buku.judul
     FROM detail_pesanan
     LEFT JOIN buku ON detail_pesanan.buku_id = buku.id
     WHERE detail_pesanan.pesanan_id = ?"
);

mysqli_stmt_bind_param($query_detail, "i", $id_pesanan);
mysqli_stmt_execute($query_detail);
$detail = mysqli_stmt_get_result($query_detail);

// Data struk
$nomor = $pesanan['nomor_pesanan'] ?? '-';
$nama = $pesanan['nama_penerima'] ?? '-';
$waktu = $pesanan['tanggal_pesanan'] ?? '-';
$total = (float) ($pesanan['total_harga'] ?? 0);
$metode = $pesanan['metode_pembayaran'] ?? '-';
$status_pesanan = $pesanan['status'] ?? 'Menunggu';
$status_pembayaran = $pesanan['status_pembayaran'] ?? 'Menunggu Pembayaran';

// Fungsi rupiah
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Struk Pesanan - NduttStore</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f0edf6;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #25213a;
            padding: 30px 15px;
            margin: 0;
        }

        .struk {
            width: 430px;
            max-width: 100%;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(50, 22, 95, .10);
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #ddd;
            padding-bottom: 20px;
        }

        .logo {
            font-size: 27px;
            font-weight: 800;
            color: #32165f;
        }

        .logo span {
            color: #ed4ca4;
        }

        .header p {
            color: #888;
            font-size: 13px;
            margin: 5px 0;
        }

        .judul-struk {
            color: #7041d3 !important;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 15px !important;
        }

        .info {
            padding: 18px 0;
            border-bottom: 1px dashed #ddd;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 10px 0;
            font-size: 13px;
        }

        .info-row span:first-child {
            color: #777;
        }

        .info-row strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            color: #32165f;
            margin: 20px 0 10px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0edf5;
            font-size: 13px;
        }

        .item-info {
            flex: 1;
        }

        .item-info strong {
            display: block;
            margin-bottom: 5px;
        }

        .item-info small {
            color: #888;
        }

        .item-price {
            font-weight: bold;
            white-space: nowrap;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            border-top: 2px dashed #ddd;
            margin-top: 15px;
            padding-top: 18px;
            font-size: 17px;
            font-weight: 800;
            color: #32165f;
        }

        .status-box {
            margin-top: 20px;
            padding: 15px;
            background: #f5f0ff;
            border-radius: 12px;
            text-align: center;
        }

        .status-box p {
            margin: 5px 0;
            font-size: 13px;
        }

        .status-box strong {
            color: #7041d3;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 18px;
            border-top: 1px dashed #ddd;
            color: #888;
            font-size: 12px;
            line-height: 1.7;
        }

        .buttons {
            width: 430px;
            max-width: 100%;
            margin: 20px auto;
        }

        .buttons button,
        .buttons a {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            cursor: pointer;
        }

        .btn-print {
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            color: white;
        }

        .btn-orders {
            background: #32165f;
            color: white;
        }

        .btn-home {
            background: white;
            color: #7041d3;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .struk {
                box-shadow: none;
                border-radius: 0;
                width: 100%;
                max-width: 100%;
            }

            .buttons {
                display: none;
            }
        }

        @media(max-width: 480px) {
            .struk {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

<div class="struk">

    <div class="header">
        <div class="logo">Ndutt<span>Store.</span></div>
        <p>Toko Buku Online</p>
        <p class="judul-struk">STRUK PESANAN</p>
    </div>

    <div class="info">

        <div class="info-row">
            <span>No. Pesanan</span>
            <strong><?= aman($nomor) ?></strong>
        </div>

        <div class="info-row">
            <span>Nama Penerima</span>
            <strong><?= aman($nama) ?></strong>
        </div>

        <div class="info-row">
            <span>Waktu Pesanan</span>
            <strong><?= aman($waktu) ?></strong>
        </div>

        <div class="info-row">
            <span>Metode Pembayaran</span>
            <strong><?= aman($metode) ?></strong>
        </div>

    </div>

    <div class="section-title">Detail Pembelian</div>

    <?php if (mysqli_num_rows($detail) > 0): ?>

        <?php while ($item = mysqli_fetch_assoc($detail)): ?>

            <?php
            $judul_buku = $item['judul'] ?? 'Buku tidak tersedia';
            $jumlah = (int) $item['jumlah'];
            $harga = (float) $item['harga'];
            $subtotal = $harga * $jumlah;
            ?>

            <div class="item">

                <div class="item-info">
                    <strong><?= aman($judul_buku) ?></strong>
                    <small>
                        <?= $jumlah ?> × <?= rupiah($harga) ?>
                    </small>
                </div>

                <div class="item-price">
                    <?= rupiah($subtotal) ?>
                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="item">
            <span>Detail buku tidak ditemukan.</span>
        </div>

    <?php endif; ?>

    <?php mysqli_stmt_close($query_detail); ?>

    <div class="total">
        <span>TOTAL</span>
        <span><?= rupiah($total) ?></span>
    </div>

    <div class="status-box">
        <p>Status Pesanan: <strong><?= aman($status_pesanan) ?></strong></p>
        <p>Status Pembayaran: <strong><?= aman($status_pembayaran) ?></strong></p>
    </div>

    <div class="footer">
        Terima kasih telah berbelanja di NduttStore.<br>
        Selamat membaca dan semoga harimu menyenangkan!<br>
        <strong>NduttStore.</strong>
    </div>

</div>

<div class="buttons">

    <button class="btn-print" onclick="window.print()">
        🖨️ Cetak Struk
    </button>

    <a href="pesanan.php" class="btn-orders">
        📦 Lihat Pesanan
    </a>

    <a href="dashboard.php" class="btn-home">
        🏠 Kembali ke Dashboard
    </a>

</div>

</body>
</html>