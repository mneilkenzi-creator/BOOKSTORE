
<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Menerima ID buku dari GET maupun POST
$id_buku = (int) (
    $_POST['id']
    ?? $_POST['id_buku']
    ?? $_GET['id']
    ?? $_GET['id_buku']
    ?? $_GET['buku_id']
    ?? 0
);

// Menerima jumlah pembelian
$jumlah = (int) (
    $_POST['jumlah']
    ?? $_GET['jumlah']
    ?? 1
);

// Fungsi menampilkan pemberitahuan
function tampilPesan($judul, $pesan, $tipe = 'error') {
    $warna = $tipe === 'success' ? '#16a34a' : '#dc2626';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Informasi Keranjang - NduttStore</title>
        <style>
            * {
                box-sizing: border-box;
                font-family: 'Segoe UI', sans-serif;
            }
            body {
                margin: 0;
                background: #f8f5ff;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .box {
                background: white;
                max-width: 430px;
                width: 100%;
                padding: 35px;
                border-radius: 18px;
                text-align: center;
                box-shadow: 0 8px 30px rgba(0,0,0,.08);
            }
            h2 {
                color: <?= $warna ?>;
            }
            p {
                color: #666;
                line-height: 1.7;
            }
            a {
                display: block;
                padding: 13px;
                background: linear-gradient(135deg,#9333ea,#ec4899);
                color: white;
                text-decoration: none;
                border-radius: 9px;
                font-weight: bold;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($judul) ?></h2>
            <p><?= htmlspecialchars($pesan) ?></p>
            <a href="buku.php">Kembali ke Daftar Buku</a>
            <a href="keranjang.php">Lihat Keranjang</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Validasi ID
if ($id_buku <= 0) {
    tampilPesan(
        "ID Buku Tidak Valid",
        "ID buku tidak ditemukan. Silakan kembali ke halaman buku."
    );
}

// Validasi jumlah
if ($jumlah <= 0) {
    tampilPesan(
        "Jumlah Tidak Valid",
        "Jumlah pembelian minimal adalah 1 buku."
    );
}

// Mulai transaksi database
mysqli_begin_transaction($koneksi);

try {

    // Kunci data buku agar stok tidak dibeli bersamaan
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, judul, stok
        FROM buku
        WHERE id = ?
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception("Gagal memeriksa buku.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_buku);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $buku = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$buku) {
        throw new Exception("Buku tidak ditemukan.");
    }

    $stok = (int) $buku['stok'];

    // Cek stok tersedia
    if ($stok <= 0) {
        throw new Exception(
            "Maaf, stok buku " . $buku['judul'] . " sedang habis."
        );
    }

    // Periksa apakah buku sudah ada di keranjang
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, jumlah
        FROM keranjang
        WHERE user_id = ? AND buku_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception("Gagal memeriksa keranjang.");
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $id_buku);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $cart = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    // Hitung total jumlah yang akan berada di keranjang
    $jumlah_lama = $cart ? (int)$cart['jumlah'] : 0;
    $jumlah_baru = $jumlah_lama + $jumlah;

    // Jangan izinkan pembelian melebihi stok
    if ($jumlah_baru > $stok) {

        $sisa = $stok - $jumlah_lama;

        if ($sisa < 0) {
            $sisa = 0;
        }

        throw new Exception(
            "Stok buku " . $buku['judul'] .
            " hanya tersedia " . $stok .
            " buku. Di keranjang sudah ada " . $jumlah_lama .
            " buku. Maksimal tambahan yang bisa dibeli adalah " .
            $sisa . " buku."
        );
    }

    // Update jika buku sudah ada di keranjang
    if ($cart) {

        $cart_id = (int)$cart['id'];

        $stmt = mysqli_prepare($koneksi, "
            UPDATE keranjang
            SET jumlah = ?
            WHERE id = ? AND user_id = ?
        ");

        if (!$stmt) {
            throw new Exception("Gagal memperbarui keranjang.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $jumlah_baru,
            $cart_id,
            $user_id
        );

    } else {

        // Insert jika buku belum ada
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO keranjang (user_id, buku_id, jumlah)
            VALUES (?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Gagal menambahkan buku.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $user_id,
            $id_buku,
            $jumlah
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Buku gagal dimasukkan ke keranjang.");
    }

    mysqli_stmt_close($stmt);

    // Simpan transaksi
    mysqli_commit($koneksi);

    header("Location: keranjang.php");
    exit;

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    tampilPesan(
        "Gagal Menambahkan Buku",
        $e->getMessage()
    );
}
?>
