<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: /nduttstore/admin/login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama']
    ?? $_SESSION['admin_username']
    ?? 'Administrator';

$pesan = "";

// ==========================================
// UBAH STATUS PESANAN
// ==========================================

if (isset($_POST['ubah_status'])) {

    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $status_valid = [
        'Menunggu',
        'Sedang Diantar',
        'Pesanan Selesai'
    ];

    if ($id > 0 && in_array($status, $status_valid, true)) {

        $stmt = $koneksi->prepare("
            UPDATE pesanan
            SET status = ?
            WHERE id = ?
        ");

        $stmt->bind_param("si", $status, $id);
        $berhasil = $stmt->execute();
        $stmt->close();

        header("Location: /nduttstore/admin/pesanan.php?berhasil=" . ($berhasil ? "1" : "0"));
        exit;
    }
}

// ==========================================
// HAPUS PESANAN
// ==========================================

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    if ($id > 0) {

        // Hapus detail pesanan terlebih dahulu
        $stmt_detail = $koneksi->prepare("
            DELETE FROM detail_pesanan
            WHERE pesanan_id = ?
        ");

        if ($stmt_detail) {
            $stmt_detail->bind_param("i", $id);
            $stmt_detail->execute();
            $stmt_detail->close();
        }

        // Hapus pesanan
        $stmt = $koneksi->prepare("
            DELETE FROM pesanan
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $berhasil = $stmt->execute();
        $stmt->close();

        header("Location: /nduttstore/admin/pesanan.php?terhapus=" . ($berhasil ? "1" : "0"));
        exit;
    }
}

// ==========================================
// PENCARIAN
// ==========================================

$cari = trim($_GET['cari'] ?? '');
$cari_sql = mysqli_real_escape_string($koneksi, $cari);

// ==========================================
// STATISTIK
// ==========================================

$total_query = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM pesanan"
);

$total_pesanan = mysqli_fetch_assoc($total_query)['total'] ?? 0;

$menunggu_query = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM pesanan WHERE status = 'Menunggu'"
);

$total_menunggu = mysqli_fetch_assoc($menunggu_query)['total'] ?? 0;

$selesai_query = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM pesanan WHERE status = 'Pesanan Selesai'"
);

$total_selesai = mysqli_fetch_assoc($selesai_query)['total'] ?? 0;

// ==========================================
// AMBIL DATA PESANAN
// ==========================================

$query = mysqli_query($koneksi, "
    SELECT
        pesanan.*,
        users.nama
    FROM pesanan
    LEFT JOIN users ON pesanan.user_id = users.id
    WHERE users.nama LIKE '%$cari_sql%'
       OR pesanan.status LIKE '%$cari_sql%'
       OR pesanan.id LIKE '%$cari_sql%'
       OR pesanan.nomor_pesanan LIKE '%$cari_sql%'
    ORDER BY pesanan.id DESC
");

if (!$query) {
    die("Gagal mengambil data pesanan: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Pesanan - NduttStore</title>

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
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 15px;
    background: linear-gradient(180deg, #667eea, #764ba2);
    color: white;
    overflow-y: auto;
}

.logo {
    text-align: center;
    margin-bottom: 35px;
}

.logo h2 {
    font-size: 23px;
    margin-bottom: 5px;
}

.logo p {
    font-size: 12px;
    opacity: .8;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 8px;
}

.menu a {
    display: block;
    color: white;
    text-decoration: none;
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

.topbar h2 {
    font-size: 22px;
}

.topbar span {
    color: #667eea;
    font-weight: bold;
}

/* STATISTIK */
.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    display: flex;
    align-items: center;
    gap: 17px;
}

.card-icon {
    width: 55px;
    height: 55px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    background: #eeeaff;
}

.card p {
    color: #777;
    margin-bottom: 10px;
    font-size: 13px;
}

.card h2 {
    font-size: 25px;
    color: #667eea;
}

/* CONTENT */
.content {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.content-header h3 {
    color: #444;
}

/* SEARCH */
.search {
    display: flex;
    gap: 8px;
}

.search input {
    padding: 11px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
}

.search input:focus {
    border-color: #667eea;
}

/* BUTTON */
button, .btn {
    border: none;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    color: white;
    background: #667eea;
    text-decoration: none;
    font-size: 13px;
    transition: .3s;
}

button:hover, .btn:hover {
    opacity: .85;
}

.btn-reset {
    background: #eee;
    color: #555;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.table-wrap {
    overflow-x: auto;
}

th {
    background: #667eea;
    color: white;
    text-align: left;
}

th, td {
    padding: 15px 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

/* STATUS */
.status {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 12px;
    background: #fff2cc;
    color: #946200;
    white-space: nowrap;
}

.status.selesai {
    background: #d8f8e5;
    color: #16834a;
}

.status.diantar {
    background: #dceaff;
    color: #2865bb;
}

/* AKSI */
.aksi {
    display: flex;
    align-items: center;
    gap: 7px;
}

.aksi select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
}

.btn-simpan {
    background: #667eea;
}

.btn-hapus {
    background: #e74c3c;
}

/* NOTIFIKASI */
.notif {
    padding: 13px;
    background: #d8f8e5;
    color: #16834a;
    border-radius: 9px;
    margin-bottom: 18px;
}

.notif.error {
    background: #ffe5e5;
    color: #c62828;
}

/* KOSONG */
.kosong {
    text-align: center;
    padding: 35px;
    color: #888;
}

/* RESPONSIVE */
@media(max-width: 1000px) {
    .cards {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 800px) {
    .sidebar {
        width: 190px;
    }

    .main {
        margin-left: 190px;
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
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
        width: 100%;
        flex-wrap: wrap;
    }

    .search input {
        flex: 1;
        min-width: 150px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">
        <h2>📚 NduttStore</h2>
        <p>ADMIN PANEL</p>
    </div>

    <ul class="menu">
        <li>
            <a href="/nduttstore/admin/dashboard.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/kategori.php">
                📂 Kategori Buku
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/buku.php">
                📚 Data Buku
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/users.php">
                👥 Data Pengguna
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/pesanan.php" class="active">
                🛒 Pesanan
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/pesan.php">
                ✉️ Pesan Masuk
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/logout.php">
                🚪 Logout
            </a>
        </li>
    </ul>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h2>🛒 Data Pesanan</h2>

        <div>
            Halo,
            <span><?= htmlspecialchars($nama_admin); ?></span>
        </div>
    </div>

    <?php if (isset($_GET['berhasil'])): ?>

        <?php if ($_GET['berhasil'] == '1'): ?>
            <div class="notif">
                Status pesanan berhasil diperbarui!
            </div>
        <?php else: ?>
            <div class="notif error">
                Gagal memperbarui status pesanan.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (isset($_GET['terhapus'])): ?>

        <?php if ($_GET['terhapus'] == '1'): ?>
            <div class="notif">
                Pesanan berhasil dihapus!
            </div>
        <?php else: ?>
            <div class="notif error">
                Gagal menghapus pesanan.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="cards">

        <div class="card">
            <div class="card-icon">📦</div>

            <div>
                <p>Total Pesanan</p>
                <h2><?= (int)$total_pesanan; ?></h2>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">⏳</div>

            <div>
                <p>Pesanan Menunggu</p>
                <h2><?= (int)$total_menunggu; ?></h2>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">✅</div>

            <div>
                <p>Pesanan Selesai</p>
                <h2><?= (int)$total_selesai; ?></h2>
            </div>
        </div>

    </div>

    <!-- DATA PESANAN -->
    <div class="content">

        <div class="content-header">

            <h3>Daftar Semua Pesanan</h3>

            <form method="GET" class="search">

                <input
                    type="text"
                    name="cari"
                    placeholder="Cari pesanan..."
                    value="<?= htmlspecialchars($cari); ?>"
                >

                <button type="submit">
                    Cari
                </button>

                <a
                    href="/nduttstore/admin/pesanan.php"
                    class="btn btn-reset"
                >
                    Reset
                </a>

            </form>

        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Pesanan</th>
                        <th>Nama Pembeli</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Ubah Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                        <?php
                        $status = $row['status'] ?? 'Menunggu';

                        $class = 'status';

                        if ($status === 'Pesanan Selesai') {
                            $class .= ' selesai';
                        } elseif ($status === 'Sedang Diantar') {
                            $class .= ' diantar';
                        }
                        ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <b>#<?= htmlspecialchars($row['nomor_pesanan'] ?? $row['id']); ?></b>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama'] ?? 'Pengguna'); ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    (float)$row['total_harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </td>

                            <td>
                                <span class="<?= $class; ?>">
                                    <?= htmlspecialchars($status); ?>
                                </span>
                            </td>

                            <td>

                                <form method="POST" class="aksi">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row['id']; ?>"
                                    >

                                    <select name="status">

                                        <option
                                            value="Menunggu"
                                            <?= $status === 'Menunggu' ? 'selected' : ''; ?>
                                        >
                                            Menunggu
                                        </option>

                                        <option
                                            value="Sedang Diantar"
                                            <?= $status === 'Sedang Diantar' ? 'selected' : ''; ?>
                                        >
                                            Sedang Diantar
                                        </option>

                                        <option
                                            value="Pesanan Selesai"
                                            <?= $status === 'Pesanan Selesai' ? 'selected' : ''; ?>
                                        >
                                            Pesanan Selesai
                                        </option>

                                    </select>

                                    <button
                                        type="submit"
                                        name="ubah_status"
                                        class="btn-simpan"
                                    >
                                        Simpan
                                    </button>

                                </form>

                            </td>

                            <td>

                                <a
                                    class="btn btn-hapus"
                                    href="/nduttstore/admin/pesanan.php?hapus=<?= (int)$row['id']; ?>"
                                    onclick="return confirm('Yakin ingin menghapus pesanan ini?')"
                                >
                                    Hapus
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="kosong">
                            📦 Belum ada data pesanan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>