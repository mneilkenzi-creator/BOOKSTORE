```php
<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama']
    ?? $_SESSION['admin_username']
    ?? 'Administrator';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: users.php");
    exit;
}

// Ambil data pengguna
$stmt_user = $koneksi->prepare(
    "SELECT id, nama, email FROM users WHERE id = ? LIMIT 1"
);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user) {
    header("Location: users.php");
    exit;
}

// Ambil semua pesanan milik pengguna
$stmt_pesanan = $koneksi->prepare(
    "SELECT
        id,
        nomor_pesanan,
        nama_penerima,
        total_harga,
        metode_pembayaran,
        status,
        status_pembayaran,
        bukti_pembayaran,
        tanggal_pesanan,
        tanggal_verifikasi
    FROM pesanan
    WHERE user_id = ?
    ORDER BY id DESC"
);

$stmt_pesanan->bind_param("i", $user_id);
$stmt_pesanan->execute();
$pesanan = $stmt_pesanan->get_result();

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bukti Pembayaran Pengguna - NduttStore</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f3fa;
    color: #25213a;
}

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #32165f, #5c258e);
    padding: 28px 18px;
    color: white;
}

.logo {
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 38px;
    padding-left: 10px;
}

.logo span {
    color: #ed4ca4;
}

.admin-info {
    padding: 13px;
    background: rgba(255,255,255,.12);
    border-radius: 12px;
    margin-bottom: 25px;
    font-size: 13px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px 15px;
    border-radius: 9px;
    margin-bottom: 7px;
    transition: .2s;
    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255,255,255,.18);
}

.main {
    margin-left: 250px;
    padding: 35px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 15px;
}

.header h1 {
    font-size: 27px;
    color: #32165f;
}

.header p {
    color: #777;
    margin-top: 5px;
    font-size: 14px;
}

.back-btn {
    background: #32165f;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 9px;
    font-size: 13px;
}

.back-btn:hover {
    background: #5c258e;
}

.user-card {
    background: white;
    padding: 23px;
    border-radius: 15px;
    box-shadow: 0 4px 18px rgba(40,20,70,.06);
    margin-bottom: 25px;
}

.user-card h3 {
    color: #32165f;
    margin-bottom: 15px;
}

.user-card p {
    margin: 9px 0;
    color: #555;
    font-size: 14px;
}

.order-card {
    background: white;
    border-radius: 15px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 4px 18px rgba(40,20,70,.06);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 1px solid #eee;
    padding-bottom: 16px;
    margin-bottom: 18px;
}

.order-header h3 {
    color: #32165f;
    font-size: 18px;
}

.status {
    display: inline-block;
    background: #fff1d6;
    color: #a56a00;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 20px;
}

.detail-item {
    background: #faf9fc;
    padding: 13px;
    border-radius: 9px;
}

.detail-item small {
    color: #888;
    display: block;
    margin-bottom: 6px;
}

.detail-item strong {
    color: #333;
    font-size: 14px;
}

.proof-title {
    color: #32165f;
    margin: 18px 0 12px;
}

.proof-image {
    max-width: 100%;
    width: 420px;
    max-height: 500px;
    object-fit: contain;
    border-radius: 12px;
    border: 1px solid #eee;
    background: #fafafa;
    display: block;
}

.no-proof {
    padding: 18px;
    background: #fff8e8;
    border-radius: 10px;
    color: #946200;
    font-size: 14px;
}

.empty {
    background: white;
    padding: 35px;
    text-align: center;
    border-radius: 15px;
    color: #777;
}

@media(max-width: 800px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">Ndutt<span>Store.</span></div>

    <div class="admin-info">
        Administrator<br>
        <strong><?= htmlspecialchars($nama_admin); ?></strong>
    </div>

    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="kategori.php">📚 Kategori Buku</a>
    <a href="buku.php">📖 Data Buku</a>
    <a href="users.php" class="active">👥 Data User</a>
    <a href="pesanan.php">🛒 Pesanan</a>
    <a href="pesan.php">✉️ Pesan Masuk</a>
    <a href="logout.php">🚪 Logout</a>
</aside>

<main class="main">

    <div class="header">
        <div>
            <h1>Bukti Pembayaran</h1>
            <p>Daftar bukti pembayaran dari pengguna NduttStore.</p>
        </div>

        <a href="users.php" class="back-btn">← Kembali ke Data User</a>
    </div>

    <section class="user-card">
        <h3>Informasi Pengguna</h3>

        <p><strong>Nama:</strong>
            <?= htmlspecialchars($user['nama']); ?>
        </p>

        <p><strong>Email:</strong>
            <?= htmlspecialchars($user['email']); ?>
        </p>

        <p><strong>ID Pengguna:</strong>
            <?= (int)$user['id']; ?>
        </p>
    </section>

    <h2 style="color:#32165f; margin-bottom:18px;">
        Riwayat Pembayaran
    </h2>

    <?php if ($pesanan->num_rows > 0): ?>

        <?php while ($row = $pesanan->fetch_assoc()): ?>

            <section class="order-card">

                <div class="order-header">
                    <h3>
                        Pesanan #<?= htmlspecialchars(
                            $row['nomor_pesanan'] ?: $row['id']
                        ); ?>
                    </h3>

                    <span class="status">
                        <?= htmlspecialchars(
                            $row['status_pembayaran'] ?: 'Belum ada status'
                        ); ?>
                    </span>
                </div>

                <div class="detail-grid">

                    <div class="detail-item">
                        <small>Nama Penerima</small>
                        <strong>
                            <?= htmlspecialchars($row['nama_penerima']); ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <small>Total Pembayaran</small>
                        <strong>
                            <?= rupiah($row['total_harga']); ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <small>Metode Pembayaran</small>
                        <strong>
                            <?= htmlspecialchars($row['metode_pembayaran']); ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <small>Status Pesanan</small>
                        <strong>
                            <?= htmlspecialchars($row['status']); ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <small>Tanggal Pesanan</small>
                        <strong>
                            <?= htmlspecialchars($row['tanggal_pesanan']); ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <small>Tanggal Verifikasi</small>
                        <strong>
                            <?= htmlspecialchars(
                                $row['tanggal_verifikasi'] ?: '-'
                            ); ?>
                        </strong>
                    </div>

                </div>

                <h4 class="proof-title">Bukti Transfer / Pembayaran</h4>

                <?php if (!empty($row['bukti_pembayaran'])): ?>

                    <?php
                    $file_bukti = "../assets/" .
                        ltrim($row['bukti_pembayaran'], "/\\");
                    ?>

                    <?php if (is_file(__DIR__ . "/" . $file_bukti)): ?>

                        <a href="<?= htmlspecialchars($file_bukti); ?>"
                           target="_blank">
                            <img
                                src="<?= htmlspecialchars($file_bukti); ?>"
                                class="proof-image"
                                alt="Bukti pembayaran"
                            >
                        </a>

                        <p style="font-size:12px;color:#888;margin-top:10px;">
                            Klik gambar untuk melihat ukuran penuh.
                        </p>

                    <?php else: ?>

                        <div class="no-proof">
                            File bukti pembayaran tidak ditemukan di folder
                            penyimpanan.
                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="no-proof">
                        Pengguna belum mengunggah bukti pembayaran.
                    </div>

                <?php endif; ?>

            </section>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>Belum Ada Pesanan</h3>
            <p>Pengguna ini belum mempunyai riwayat pesanan.</p>
        </div>

    <?php endif; ?>

</main>

</body>
</html>

<?php
$stmt_pesanan->close();
?>