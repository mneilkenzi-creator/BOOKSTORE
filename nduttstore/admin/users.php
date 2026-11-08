<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: /nduttstore/admin/login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama']
    ?? $_SESSION['admin_username']
    ?? 'Administrator';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

function badgeStatus($status) {
    $status = strtolower(trim((string)$status));

    if ($status === 'berhasil') {
        return 'success';
    }

    if ($status === 'ditolak') {
        return 'danger';
    }

    if ($status === 'belum dibayar') {
        return 'secondary';
    }

    return 'warning';
}

// ==========================================
// TOKEN KEAMANAN FORM
// ==========================================

if (empty($_SESSION['csrf_users'])) {
    $_SESSION['csrf_users'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_users'];

// ==========================================
// PROSES VERIFIKASI PEMBAYARAN
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['aksi_verifikasi'])) {

    $token_post = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $token_post)) {
        $_SESSION['flash_users'] = [
            'type' => 'danger',
            'message' => 'Permintaan tidak valid. Silakan coba lagi.'
        ];

        header("Location: /nduttstore/admin/users.php");
        exit;
    }

    $aksi = $_POST['aksi_verifikasi'];
    $pesanan_id = (int)($_POST['pesanan_id'] ?? 0);
    $user_id_redirect = (int)($_POST['user_id'] ?? 0);

    if (!in_array($aksi, ['terima', 'tolak'], true)
        || $pesanan_id <= 0
        || $user_id_redirect <= 0) {

        $_SESSION['flash_users'] = [
            'type' => 'danger',
            'message' => 'Data verifikasi tidak valid.'
        ];

        header("Location: /nduttstore/admin/users.php");
        exit;
    }

    // Ambil data pesanan dan pastikan bukti tersedia
    $stmt_cek = $koneksi->prepare("
        SELECT id, user_id, status_pembayaran, bukti_pembayaran
        FROM pesanan
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");

    if (!$stmt_cek) {
        die("Gagal menyiapkan pemeriksaan pesanan.");
    }

    $stmt_cek->bind_param("ii", $pesanan_id, $user_id_redirect);
    $stmt_cek->execute();

    $data_cek = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if (!$data_cek) {
        $_SESSION['flash_users'] = [
            'type' => 'danger',
            'message' => 'Pesanan tidak ditemukan.'
        ];
    } elseif (empty($data_cek['bukti_pembayaran'])) {
        $_SESSION['flash_users'] = [
            'type' => 'danger',
            'message' => 'Pesanan belum memiliki bukti pembayaran.'
        ];
    } elseif (strtolower(trim($data_cek['status_pembayaran'] ?? '')) !== 'menunggu verifikasi') {
        $_SESSION['flash_users'] = [
            'type' => 'danger',
            'message' => 'Pesanan ini sudah diproses atau belum siap diverifikasi.'
        ];
    } else {

        $status_baru = ($aksi === 'terima')
            ? 'Berhasil'
            : 'Ditolak';

        $stmt_update = $koneksi->prepare("
            UPDATE pesanan
            SET status_pembayaran = ?,
                tanggal_verifikasi = NOW()
            WHERE id = ?
              AND user_id = ?
              AND status_pembayaran = 'Menunggu Verifikasi'
              AND bukti_pembayaran IS NOT NULL
              AND bukti_pembayaran <> ''
        ");

        if (!$stmt_update) {
            die("Gagal menyiapkan perubahan status pembayaran.");
        }

        $stmt_update->bind_param(
            "sii",
            $status_baru,
            $pesanan_id,
            $user_id_redirect
        );

        $stmt_update->execute();

        if ($stmt_update->affected_rows > 0) {
            $_SESSION['flash_users'] = [
                'type' => 'success',
                'message' => $aksi === 'terima'
                    ? 'Pembayaran berhasil diterima dan diverifikasi.'
                    : 'Pembayaran berhasil ditolak.'
            ];
        } else {
            $_SESSION['flash_users'] = [
                'type' => 'danger',
                'message' => 'Status pembayaran tidak berubah. Silakan periksa kembali.'
            ];
        }

        $stmt_update->close();
    }

    header(
        "Location: /nduttstore/admin/users.php?detail="
        . $user_id_redirect
        . "#detail-pembayaran"
    );
    exit;
}

// ==========================================
// NOTIFIKASI
// ==========================================

$flash = $_SESSION['flash_users'] ?? null;
unset($_SESSION['flash_users']);

// ==========================================
// PENCARIAN USER
// ==========================================

$cari = trim($_GET['cari'] ?? '');
$cari_sql = mysqli_real_escape_string($koneksi, $cari);

$query = mysqli_query($koneksi, "
    SELECT *
    FROM users
    WHERE nama LIKE '%$cari_sql%'
       OR email LIKE '%$cari_sql%'
    ORDER BY id DESC
");

if (!$query) {
    die("Query user gagal: " . mysqli_error($koneksi));
}

// ==========================================
// TOTAL USER
// ==========================================

$result_total = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total FROM users
");

$total_user = mysqli_fetch_assoc($result_total)['total'] ?? 0;

// ==========================================
// CEK KOLOM TANGGAL
// ==========================================

$kolom_tanggal = false;

$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM users");

while ($kolom = mysqli_fetch_assoc($cek_kolom)) {
    if ($kolom['Field'] == 'created_at') {
        $kolom_tanggal = true;
        break;
    }
}

// ==========================================
// DETAIL BUKTI PEMBAYARAN USER
// ==========================================

$user_detail = null;
$pesanan = null;
$error_detail = '';

$detail_id = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;

if ($detail_id > 0) {

    $stmt_user = $koneksi->prepare("
        SELECT id, nama, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt_user->bind_param("i", $detail_id);
    $stmt_user->execute();

    $user_detail = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if ($user_detail) {

        $stmt_pesanan = $koneksi->prepare("
            SELECT
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
            ORDER BY id DESC
        ");

        if ($stmt_pesanan) {
            $stmt_pesanan->bind_param("i", $detail_id);
            $stmt_pesanan->execute();
            $pesanan = $stmt_pesanan->get_result();
        } else {
            $error_detail = "Gagal mengambil data pesanan: " . $koneksi->error;
        }

    } else {
        $error_detail = "Data pengguna tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data User - NduttStore</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f6fb;
    color: #333;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #667eea, #764ba2);
    color: white;
    padding: 25px 15px;
    top: 0;
    left: 0;
    overflow-y: auto;
}

.logo {
    text-align: center;
    margin-bottom: 35px;
}

.logo h2 {
    font-size: 25px;
}

.logo p {
    font-size: 13px;
    opacity: .8;
    margin-top: 5px;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 8px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: white;
    padding: 14px 15px;
    border-radius: 10px;
    transition: .3s;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.2);
    transform: translateX(4px);
}

/* MAIN */
.main {
    margin-left: 250px;
    padding: 30px;
}

/* TOPBAR */
.topbar {
    background: white;
    padding: 20px 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.topbar h1 {
    font-size: 26px;
}

.admin {
    color: #667eea;
    font-weight: bold;
}

/* STATISTIK */
.stat-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 25px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    background: #eeeaff;
}

.stat-info h3 {
    font-size: 14px;
    color: #777;
    margin-bottom: 8px;
}

.stat-info h2 {
    font-size: 23px;
}

/* CONTENT */
.content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    margin-bottom: 25px;
}

.content h2 {
    margin-bottom: 10px;
    color: #444;
}

.content > p {
    color: #777;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* SEARCH */
.search {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.search input {
    flex: 1;
    padding: 13px 15px;
    border: 1px solid #ddd;
    border-radius: 9px;
    outline: none;
}

.search input:focus {
    border-color: #667eea;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 13px 20px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: bold;
}

.btn-cancel {
    background: #eee;
    color: #444;
    padding: 13px 18px;
    border-radius: 9px;
    text-decoration: none;
}

/* TABLE */
.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    background: #667eea;
    color: white;
    padding: 15px;
    text-align: left;
}

td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

tr:hover td {
    background: #f7f8ff;
}

/* USER */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 8px;
}

/* TOMBOL */
.btn-bukti {
    display: inline-block;
    background: #eeeaff;
    color: #6741bd;
    padding: 9px 13px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    transition: .3s;
    white-space: nowrap;
}

.btn-bukti:hover {
    background: #6741bd;
    color: white;
}

/* DETAIL */
.detail-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    margin-top: 25px;
    scroll-margin-top: 20px;
}

.detail-section h2 {
    color: #444;
    margin-bottom: 10px;
}

.detail-section > p {
    color: #777;
    margin-bottom: 20px;
    line-height: 1.6;
}

.user-info {
    background: #f7f5ff;
    border-left: 4px solid #764ba2;
    border-radius: 10px;
    padding: 18px;
    margin-bottom: 25px;
}

.user-info p {
    margin: 8px 0;
    font-size: 14px;
}

.order-card {
    border: 1px solid #eee;
    border-radius: 13px;
    padding: 22px;
    margin-bottom: 20px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 18px;
}

.order-header h3 {
    color: #6741bd;
    font-size: 17px;
}

.status {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.success {
    background: #e4f8eb;
    color: #168342;
}

.warning {
    background: #fff1d6;
    color: #a56a00;
}

.danger {
    background: #ffe5e5;
    color: #c62828;
}

.secondary {
    background: #eee;
    color: #555;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 20px;
}

.detail-item {
    background: #faf9fc;
    padding: 14px;
    border-radius: 9px;
    overflow-wrap: anywhere;
}

.detail-item small {
    color: #888;
    display: block;
    margin-bottom: 7px;
}

.detail-item strong {
    color: #333;
    font-size: 14px;
}

.proof-title {
    color: #6741bd;
    margin: 20px 0 12px;
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

.btn-kembali {
    display: inline-block;
    background: #eeeaff;
    color: #6741bd;
    padding: 11px 16px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 20px;
}

.alert {
    padding: 15px 18px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #e4f8eb;
    color: #168342;
}

.alert-danger {
    background: #ffe5e5;
    color: #b42318;
}

/* VERIFIKASI */
.verifikasi-box {
    background: #faf9fc;
    border: 1px solid #eee;
    border-radius: 11px;
    padding: 18px;
    margin-top: 20px;
}

.verifikasi-box h4 {
    color: #444;
    margin-bottom: 12px;
}

.verifikasi-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-terima,
.btn-tolak {
    border: none;
    padding: 11px 17px;
    border-radius: 8px;
    color: white;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}

.btn-terima {
    background: #20a45b;
}

.btn-terima:hover {
    background: #168348;
}

.btn-tolak {
    background: #dc3545;
}

.btn-tolak:hover {
    background: #b92332;
}

.verifikasi-selesai {
    padding: 13px;
    background: #f1f0f5;
    color: #625c70;
    border-radius: 9px;
    font-size: 13px;
}

.empty {
    background: white;
    padding: 35px;
    text-align: center;
    border-radius: 15px;
    color: #777;
}

/* RESPONSIVE */
@media(max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    .main {
        margin-left: 200px;
        padding: 15px;
    }
}

@media(max-width: 550px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
    }

    .search {
        flex-direction: column;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .content,
    .detail-section {
        padding: 18px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">
        <h2>📚 NduttStore</h2>
        <p>Admin Panel</p>
    </div>

    <ul class="menu">
        <li><a href="/nduttstore/admin/dashboard.php">🏠 Dashboard</a></li>
        <li><a href="/nduttstore/admin/kategori.php">📂 Kategori Buku</a></li>
        <li><a href="/nduttstore/admin/buku.php">📚 Data Buku</a></li>
        <li><a href="/nduttstore/admin/users.php" class="active">👥 Data User</a></li>
        <li><a href="/nduttstore/admin/pesanan.php">🛒 Pesanan</a></li>
        <li><a href="/nduttstore/admin/pesan.php">💬 Pesan Masuk</a></li>
        <li><a href="/nduttstore/admin/logout.php">🚪 Logout</a></li>
    </ul>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1>👥 Data User</h1>

        <div class="admin">
            👤 <?= e($nama_admin); ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']); ?>">
            <?= e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="stat-card">

        <div class="stat-icon">👥</div>

        <div class="stat-info">
            <h3>Total Pengguna Terdaftar</h3>
            <h2><?= (int)$total_user; ?> User</h2>
        </div>

    </div>

    <!-- DATA USER -->
    <div class="content">

        <h2>Kelola Data Pengguna</h2>

        <p>
            Lihat data pengguna dan periksa bukti pembayaran
            dari setiap pelanggan NduttStore.
        </p>

        <form method="GET" class="search">

            <input
                type="text"
                name="cari"
                placeholder="Cari nama atau email pengguna..."
                value="<?= e($cari); ?>"
            >

            <button type="submit" class="btn-primary">
                Cari
            </button>

            <a href="/nduttstore/admin/users.php" class="btn-cancel">
                Reset
            </a>

        </form>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengguna</th>
                        <th>Email</th>

                        <?php if ($kolom_tanggal): ?>
                            <th>Tanggal Daftar</th>
                        <?php endif; ?>

                        <th>Bukti Pembayaran</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <span class="user-avatar">
                                    <?= e(strtoupper(substr($row['nama'], 0, 1))); ?>
                                </span>

                                <b><?= e($row['nama']); ?></b>
                            </td>

                            <td><?= e($row['email']); ?></td>

                            <?php if ($kolom_tanggal): ?>
                                <td>
                                    <?= !empty($row['created_at'])
                                        ? e(date('d-m-Y', strtotime($row['created_at'])))
                                        : '-'; ?>
                                </td>
                            <?php endif; ?>

                            <td>
                                <a
                                    href="/nduttstore/admin/users.php?detail=<?= (int)$row['id']; ?><?= $cari !== '' ? '&cari=' . urlencode($cari) : ''; ?>#detail-pembayaran"
                                    class="btn-bukti"
                                >
                                    💳 Bukti Pembayaran
                                </a>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="<?= $kolom_tanggal ? 5 : 4; ?>"
                            style="text-align:center;padding:35px;"
                        >
                            👥 Belum ada pengguna terdaftar.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- DETAIL PEMBAYARAN -->
    <?php if ($detail_id > 0): ?>

        <div class="detail-section" id="detail-pembayaran">

            <a
                href="/nduttstore/admin/users.php<?= $cari !== '' ? '?cari=' . urlencode($cari) : ''; ?>"
                class="btn-kembali"
            >
                ← Tutup Detail
            </a>

            <h2>💳 Detail Bukti Pembayaran</h2>

            <p>
                Riwayat transaksi dan bukti pembayaran pengguna.
            </p>

            <?php if ($error_detail !== ''): ?>

                <div class="alert alert-danger">
                    <?= e($error_detail); ?>
                </div>

            <?php elseif ($user_detail): ?>

                <div class="user-info">

                    <p>
                        <strong>Nama Pengguna:</strong>
                        <?= e($user_detail['nama']); ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= e($user_detail['email']); ?>
                    </p>

                    <p>
                        <strong>ID Pengguna:</strong>
                        <?= (int)$user_detail['id']; ?>
                    </p>

                </div>

                <h3 style="margin-bottom:18px;color:#444;">
                    Riwayat Transaksi
                </h3>

                <?php if ($pesanan && $pesanan->num_rows > 0): ?>

                    <?php while ($row = $pesanan->fetch_assoc()): ?>

                        <?php
                            $status_bayar = $row['status_pembayaran']
                                ?: 'Menunggu Verifikasi';

                            $status_lower = strtolower(trim($status_bayar));

                            $status_class = badgeStatus($status_bayar);

                            $nomor_pesanan = !empty($row['nomor_pesanan'])
                                ? $row['nomor_pesanan']
                                : $row['id'];

                            $ada_bukti = !empty($row['bukti_pembayaran']);

                            $boleh_verifikasi = (
                                $ada_bukti &&
                                $status_lower === 'menunggu verifikasi'
                            );
                        ?>

                        <div class="order-card">

                            <div class="order-header">

                                <h3>
                                    Pesanan #<?= e($nomor_pesanan); ?>
                                </h3>

                                <span class="status <?= e($status_class); ?>">
                                    <?= e($status_bayar); ?>
                                </span>

                            </div>

                            <div class="detail-grid">

                                <div class="detail-item">
                                    <small>Nama Penerima</small>
                                    <strong>
                                        <?= e($row['nama_penerima']); ?>
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
                                        <?= e($row['metode_pembayaran']); ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <small>Status Pesanan</small>
                                    <strong>
                                        <?= e($row['status']); ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <small>Tanggal Pesanan</small>
                                    <strong>
                                        <?= e($row['tanggal_pesanan']); ?>
                                    </strong>
                                </div>

                                <div class="detail-item">
                                    <small>Tanggal Verifikasi</small>
                                    <strong>
                                        <?= e($row['tanggal_verifikasi'] ?: '-'); ?>
                                    </strong>
                                </div>

                            </div>

                            <h4 class="proof-title">
                                📷 Bukti Transfer / Pembayaran
                            </h4>

                            <?php if ($ada_bukti): ?>

                                <?php
                                    $nama_file = basename(
                                        str_replace(
                                            '\\',
                                            '/',
                                            $row['bukti_pembayaran']
                                        )
                                    );

                                    $path_fisik = __DIR__
                                        . "/../assets/bukti_pembayaran/"
                                        . $nama_file;

                                    $path_web = "/nduttstore/assets/bukti_pembayaran/"
                                        . rawurlencode($nama_file);
                                ?>

                                <?php if (is_file($path_fisik)): ?>

                                    <a
                                        href="<?= e($path_web); ?>"
                                        target="_blank"
                                    >
                                        <img
                                            src="<?= e($path_web); ?>"
                                            class="proof-image"
                                            alt="Bukti pembayaran pengguna"
                                        >
                                    </a>

                                    <p style="font-size:12px;color:#888;margin-top:10px;">
                                        Klik gambar untuk melihat ukuran penuh.
                                    </p>

                                <?php else: ?>

                                    <div class="no-proof">
                                        File bukti tercatat di database,
                                        tetapi gambar tidak ditemukan di folder.
                                    </div>

                                <?php endif; ?>

                            <?php else: ?>

                                <div class="no-proof">
                                    Pengguna belum mengunggah bukti pembayaran
                                    untuk pesanan ini.
                                </div>

                            <?php endif; ?>

                            <!-- TOMBOL VERIFIKASI -->
                            <div class="verifikasi-box">

                                <h4>🔎 Verifikasi Pembayaran</h4>

                                <?php if ($boleh_verifikasi): ?>

                                    <p style="font-size:13px;color:#777;margin-bottom:15px;">
                                        Periksa bukti dan pastikan pembayaran
                                        benar-benar sudah diterima sebelum
                                        menekan tombol verifikasi.
                                    </p>

                                    <div class="verifikasi-actions">

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin pembayaran ini sudah diterima dan sesuai?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrf_token); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="pesanan_id"
                                                value="<?= (int)$row['id']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int)$user_detail['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aksi_verifikasi"
                                                value="terima"
                                                class="btn-terima"
                                            >
                                                ✓ Terima Pembayaran
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menolak bukti pembayaran ini?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrf_token); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="pesanan_id"
                                                value="<?= (int)$row['id']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int)$user_detail['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aksi_verifikasi"
                                                value="tolak"
                                                class="btn-tolak"
                                            >
                                                ✕ Tolak Pembayaran
                                            </button>
                                        </form>

                                    </div>

                                <?php elseif ($status_lower === 'berhasil'): ?>

                                    <div class="verifikasi-selesai">
                                        ✓ Pembayaran sudah diterima dan diverifikasi.
                                    </div>

                                <?php elseif ($status_lower === 'ditolak'): ?>

                                    <div class="verifikasi-selesai">
                                        ✕ Pembayaran ditolak. Menunggu user
                                        mengunggah bukti pembayaran baru.
                                    </div>

                                <?php elseif (!$ada_bukti): ?>

                                    <div class="verifikasi-selesai">
                                        Belum ada bukti pembayaran yang dapat diperiksa.
                                    </div>

                                <?php else: ?>

                                    <div class="verifikasi-selesai">
                                        Status pembayaran saat ini:
                                        <?= e($status_bayar); ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty">
                        <h3>Belum Ada Transaksi</h3>
                        <p>Pengguna ini belum mempunyai riwayat pesanan.</p>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
if (isset($stmt_pesanan) && $stmt_pesanan) {
    $stmt_pesanan->close();
}
?>