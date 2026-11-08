```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

// ======================================
// CEK LOGIN
// ======================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION['checkout']) ||
    !is_array($_SESSION['checkout'])
) {
    header("Location: keranjang.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$checkout = $_SESSION['checkout'];

// ======================================
// AMBIL DATA CHECKOUT
// ======================================
$nama = trim($checkout['nama'] ?? '');
$alamat = trim($checkout['alamat'] ?? '');
$telepon = trim($checkout['telepon'] ?? '');

if ($nama === '' || $alamat === '' || $telepon === '') {
    die("Data pengiriman tidak lengkap. Silakan checkout ulang.");
}

// ======================================
// VALIDASI METODE PEMBAYARAN
// ======================================
$metode = $_SESSION['metode_pembayaran'] ?? '';

if (!in_array($metode, ['COD', 'QRIS', 'BANK'], true)) {
    die("Metode pembayaran tidak valid.");
}

if ($metode === 'BANK') {
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        ($_POST['konfirmasi_transfer'] ?? '') !== '1'
    ) {
        die("Silakan konfirmasi pesanan dari halaman transfer bank.");
    }
}

if ($metode === 'QRIS') {
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        ($_POST['konfirmasi_qris'] ?? '') !== '1'
    ) {
        die("Silakan konfirmasi pesanan dari halaman QRIS.");
    }
}

// ======================================
// STATUS PEMBAYARAN
// ======================================
if ($metode === 'COD') {
    $status_pembayaran = 'Belum Dibayar';
} else {
    $status_pembayaran = 'Menunggu Verifikasi';
}

// ======================================
// MULAI TRANSAKSI DATABASE
// ======================================
mysqli_begin_transaction($koneksi);

try {

    // Ambil keranjang dan kunci stok buku
    $query = mysqli_query($koneksi, "
        SELECT
            k.buku_id,
            k.jumlah,
            b.judul,
            b.harga,
            b.stok
        FROM keranjang k
        INNER JOIN buku b ON b.id = k.buku_id
        WHERE k.user_id = $user_id
        FOR UPDATE
    ");

    if (!$query || mysqli_num_rows($query) === 0) {
        throw new Exception("Keranjang kosong atau buku tidak ditemukan.");
    }

    $items = [];
    $total = 0;

    while ($row = mysqli_fetch_assoc($query)) {

        $jumlah = (int) $row['jumlah'];
        $stok = (int) $row['stok'];

        if ($jumlah <= 0) {
            throw new Exception("Jumlah pembelian tidak valid.");
        }

        if ($stok < $jumlah) {
            throw new Exception(
                "Stok buku " . $row['judul'] .
                " tidak mencukupi. Stok tersedia: " . $stok
            );
        }

        $items[] = $row;
        $total += (float) $row['harga'] * $jumlah;
    }

    if ($total <= 0) {
        throw new Exception("Total pesanan tidak valid.");
    }

    // ======================================
    // BUAT NOMOR PESANAN
    // ======================================
    $nomor_pesanan = 'ND-' . date('YmdHis') . '-' . $user_id;

    // ======================================
    // SIMPAN PESANAN
    // ======================================
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO pesanan (
            user_id,
            nama_penerima,
            alamat,
            no_hp,
            total_harga,
            metode_pembayaran,
            status,
            nomor_pesanan,
            status_pembayaran
        ) VALUES (?, ?, ?, ?, ?, ?, 'Menunggu', ?, ?)
    ");

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan pesanan: " . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssdsss",
        $user_id,
        $nama,
        $alamat,
        $telepon,
        $total,
        $metode,
        $nomor_pesanan,
        $status_pembayaran
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Gagal menyimpan pesanan: " . mysqli_stmt_error($stmt)
        );
    }

    $pesanan_id = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    // ======================================
    // SIMPAN DETAIL PESANAN DAN KURANGI STOK
    // ======================================
    foreach ($items as $item) {

        $buku_id = (int) $item['buku_id'];
        $jumlah = (int) $item['jumlah'];
        $harga = (float) $item['harga'];

        $detail = mysqli_query($koneksi, "
            INSERT INTO detail_pesanan (
                pesanan_id,
                buku_id,
                jumlah,
                harga
            ) VALUES (
                $pesanan_id,
                $buku_id,
                $jumlah,
                $harga
            )
        ");

        if (!$detail) {
            throw new Exception(
                "Gagal menyimpan detail: " . mysqli_error($koneksi)
            );
        }

        $update_stok = mysqli_query($koneksi, "
            UPDATE buku
            SET stok = stok - $jumlah
            WHERE id = $buku_id
            AND stok >= $jumlah
        ");

        if (!$update_stok || mysqli_affected_rows($koneksi) !== 1) {
            throw new Exception("Gagal memperbarui stok buku.");
        }
    }

    // ======================================
    // HAPUS KERANJANG
    // ======================================
    $hapus = mysqli_query($koneksi, "
        DELETE FROM keranjang
        WHERE user_id = $user_id
    ");

    if (!$hapus) {
        throw new Exception("Gagal mengosongkan keranjang.");
    }

    // ======================================
    // SELESAIKAN TRANSAKSI
    // ======================================
    mysqli_commit($koneksi);

    // ======================================
    // SIMPAN DATA PESANAN TERAKHIR
    // ======================================
    $_SESSION['nomor_pesanan'] = $nomor_pesanan;
    $_SESSION['pesanan_id'] = $pesanan_id;
    $_SESSION['status_pembayaran'] = $status_pembayaran;
    $_SESSION['metode_pembayaran_terakhir'] = $metode;

    // Bersihkan session checkout
    unset($_SESSION['checkout']);
    unset($_SESSION['metode_pembayaran']);

    // ======================================
    // ARAHKAN SESUAI METODE
    // ======================================
    if ($metode === 'COD') {
        header("Location: berhasil.php");
        exit;
    }

    // QRIS dan BANK menuju halaman upload bukti
    header("Location: upload_bukti.php?id=" . $pesanan_id);
    exit;

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    die(
        "Pesanan gagal diproses: " .
        htmlspecialchars($e->getMessage())
    );
}