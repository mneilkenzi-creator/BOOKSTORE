<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$pesan = "";
$error = "";

// PROSES UPDATE JUMLAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_jumlah'])) {

    $id_keranjang = (int) ($_POST['id_keranjang'] ?? 0);
    $jumlah = (int) ($_POST['jumlah'] ?? 1);

    $stmt = mysqli_prepare($koneksi, "
        SELECT k.id, b.stok
        FROM keranjang k
        JOIN buku b ON k.buku_id = b.id
        WHERE k.id = ? AND k.user_id = ?
    ");

    if (!$stmt) {
        die("Query gagal: " . mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_keranjang, $user_id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$data) {
        $error = "Data keranjang tidak ditemukan.";
    } elseif ($jumlah < 1) {
        $error = "Jumlah minimal pembelian adalah 1.";
    } elseif ($jumlah > (int)$data['stok']) {
        $error = "Jumlah melebihi stok tersedia. Stok saat ini: " . $data['stok'];
    } else {

        $stmt = mysqli_prepare($koneksi, "
            UPDATE keranjang
            SET jumlah = ?
            WHERE id = ? AND user_id = ?
        ");

        mysqli_stmt_bind_param($stmt, "iii", $jumlah, $id_keranjang, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: keranjang.php?update=berhasil");
        exit;
    }
}

// PROSES HAPUS BUKU
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare($koneksi, "
        DELETE FROM keranjang
        WHERE id = ? AND user_id = ?
    ");

    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: keranjang.php?hapus=berhasil");
    exit;
}

if (isset($_GET['update'])) {
    $pesan = "Jumlah buku berhasil diperbarui!";
}

if (isset($_GET['hapus'])) {
    $pesan = "Buku berhasil dihapus dari keranjang.";
}

// AMBIL DATA KERANJANG
$query = mysqli_query($koneksi, "
    SELECT
        k.id AS id_keranjang,
        k.jumlah,
        b.id AS id_buku,
        b.judul,
        b.harga,
        b.gambar,
        b.stok
    FROM keranjang k
    JOIN buku b ON k.buku_id = b.id
    WHERE k.user_id = $user_id
    ORDER BY k.id DESC
");

if (!$query) {
    die("Gagal mengambil keranjang: " . mysqli_error($koneksi));
}

$items = [];
$total = 0;
$total_item = 0;

while ($row = mysqli_fetch_assoc($query)) {
    $items[] = $row;

    $total += (int)$row['harga'] * (int)$row['jumlah'];
    $total_item += (int)$row['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Keranjang - NduttStore</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f8f7fc;
    color: #25213a;
}

.container {
    width: 90%;
    max-width: 1150px;
    margin: 40px auto;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    color: #32165f;
    font-size: 30px;
}

.header p {
    color: #777;
    margin-top: 7px;
}

.alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    background: #e8f8ed;
    color: #23733c;
}

.alert.error {
    background: #ffe9e9;
    color: #b42318;
}

.cart-layout {
    display: grid;
    grid-template-columns: 1fr 330px;
    gap: 22px;
    align-items: start;
}

.cart-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.cart-item {
    background: white;
    padding: 20px;
    border-radius: 16px;
    display: flex;
    gap: 20px;
    align-items: center;
    box-shadow: 0 4px 18px rgba(40, 20, 70, .05);
}

.cart-item img {
    width: 115px;
    height: 145px;
    object-fit: cover;
    border-radius: 10px;
    background: #eee;
}

.book-info {
    flex: 1;
    min-width: 0;
}

.book-info h3 {
    font-size: 18px;
    color: #32165f;
    margin-bottom: 8px;
}

.price {
    color: #e34a9c;
    font-weight: 700;
    margin-bottom: 12px;
}

.stock {
    color: #777;
    font-size: 13px;
    margin-bottom: 12px;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
}

.qty-control {
    display: flex;
    align-items: center;
    border: 1px solid #e4ddec;
    border-radius: 9px;
    overflow: hidden;
    height: 38px;
}

.qty-control button {
    width: 35px;
    height: 38px;
    border: none;
    background: #f2eafa;
    color: #54258a;
    font-size: 20px;
    cursor: pointer;
}

.qty-control button:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.qty-control input {
    width: 48px;
    height: 38px;
    text-align: center;
    border: none;
    outline: none;
    font-size: 15px;
    font-weight: 700;
}

.update-btn {
    border: none;
    background: #7041d3;
    color: white;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.update-btn:hover {
    background: #5425a7;
}

.subtotal {
    font-weight: 700;
    color: #32165f;
    margin-top: 12px;
}

.delete-link {
    display: inline-block;
    color: #e04464;
    font-size: 13px;
    margin-top: 10px;
    text-decoration: none;
}

.summary {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(40, 20, 70, .05);
    position: sticky;
    top: 100px;
}

.summary h2 {
    color: #32165f;
    font-size: 21px;
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin: 13px 0;
    color: #666;
}

.summary-total {
    border-top: 1px solid #eee;
    padding-top: 17px;
    margin-top: 18px;
    font-size: 19px;
    font-weight: 800;
    color: #32165f;
}

.checkout-btn {
    display: block;
    text-align: center;
    background: linear-gradient(110deg, #7041d3, #ed4ca4);
    color: white;
    padding: 14px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    margin-top: 22px;
}

.continue {
    display: block;
    text-align: center;
    color: #7041d3;
    margin-top: 15px;
    text-decoration: none;
    font-size: 14px;
}

.empty {
    background: white;
    text-align: center;
    padding: 65px 20px;
    border-radius: 16px;
}

.empty .icon {
    font-size: 65px;
    margin-bottom: 15px;
}

.empty h2 {
    color: #32165f;
    margin-bottom: 10px;
}

.empty p {
    color: #777;
    margin-bottom: 22px;
}

.empty a {
    display: inline-block;
    background: #7041d3;
    color: white;
    padding: 12px 22px;
    border-radius: 9px;
    text-decoration: none;
}

@media(max-width: 800px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }

    .summary {
        position: static;
    }
}

@media(max-width: 520px) {
    .container {
        width: 94%;
        margin: 25px auto;
    }

    .cart-item {
        padding: 13px;
        gap: 12px;
        align-items: flex-start;
    }

    .cart-item img {
        width: 85px;
        height: 115px;
    }

    .book-info h3 {
        font-size: 15px;
    }

    .quantity-form {
        gap: 6px;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="container">

    <div class="header">
        <h1>🛒 Keranjang Belanja</h1>
        <p>Atur jumlah buku dan periksa pesanan kamu sebelum checkout.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (count($items) > 0): ?>

    <div class="cart-layout">

        <div class="cart-list">

            <?php foreach ($items as $item): ?>

                <?php
                $stok = (int)$item['stok'];
                $jumlah = (int)$item['jumlah'];

                $gambar = !empty($item['gambar'])
                    ? "../assets/img/" . $item['gambar']
                    : "../assets/img/default-book.png";
                ?>

                <div class="cart-item">

                    <img
                        src="<?= htmlspecialchars($gambar) ?>"
                        alt="<?= htmlspecialchars($item['judul']) ?>"
                        onerror="this.src='../assets/img/default-book.png'"
                    >

                    <div class="book-info">

                        <h3>
                            <?= htmlspecialchars($item['judul']) ?>
                        </h3>

                        <div class="price">
                            Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                        </div>

                        <div class="stock">
                            Stok tersedia:
                            <strong><?= $stok ?></strong> buku
                        </div>

                        <form method="POST" class="quantity-form">

                            <input
                                type="hidden"
                                name="id_keranjang"
                                value="<?= $item['id_keranjang'] ?>"
                            >

                            <div class="qty-control">

                                <button
                                    type="button"
                                    onclick="ubahJumlah(this, -1)"
                                    <?= $jumlah <= 1 ? 'disabled' : '' ?>
                                >−</button>

                                <input
                                    type="number"
                                    name="jumlah"
                                    value="<?= $jumlah ?>"
                                    min="1"
                                    max="<?= $stok ?>"
                                    required
                                >

                                <button
                                    type="button"
                                    onclick="ubahJumlah(this, 1)"
                                    <?= $jumlah >= $stok ? 'disabled' : '' ?>
                                >+</button>

                            </div>

                            <button
                                type="submit"
                                name="update_jumlah"
                                class="update-btn"
                            >
                                Simpan
                            </button>

                        </form>

                        <div class="subtotal">
                            Subtotal:
                            Rp <?= number_format(
                                $item['harga'] * $jumlah,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                        <a
                            class="delete-link"
                            href="keranjang.php?hapus=<?= $item['id_keranjang'] ?>"
                            onclick="return confirm('Hapus buku ini dari keranjang?')"
                        >
                            🗑 Hapus buku
                        </a>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="summary">

            <h2>Ringkasan Belanja</h2>

            <div class="summary-row">
                <span>Jumlah jenis buku</span>
                <strong><?= count($items) ?> buku</strong>
            </div>

            <div class="summary-row">
                <span>Total item</span>
                <strong><?= $total_item ?> buah</strong>
            </div>

            <div class="summary-row summary-total">
                <span>Total Harga</span>
                <span>
                    Rp <?= number_format($total, 0, ',', '.') ?>
                </span>
            </div>

            <a href="checkout.php" class="checkout-btn">
                Lanjut ke Checkout →
            </a>

            <a href="buku.php" class="continue">
                ← Lanjut Belanja
            </a>

        </div>

    </div>

    <?php else: ?>

        <div class="empty">
            <div class="icon">🛒</div>

            <h2>Keranjang Kamu Masih Kosong</h2>

            <p>
                Yuk, pilih buku favorit kamu dan tambahkan ke keranjang!
            </p>

            <a href="buku.php">
                📚 Lihat Koleksi Buku
            </a>
        </div>

    <?php endif; ?>

</div>

<script>
function ubahJumlah(button, perubahan) {

    const form = button.closest('form');
    const input = form.querySelector('input[name="jumlah"]');

    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 1;

    let jumlah = parseInt(input.value) || min;

    jumlah += perubahan;

    if (jumlah < min) {
        jumlah = min;
    }

    if (jumlah > max) {
        jumlah = max;
    }

    input.value = jumlah;

    const buttons = form.querySelectorAll('.qty-control button');

    buttons[0].disabled = jumlah <= min;
    buttons[1].disabled = jumlah >= max;
}
</script>

</body>
</html>