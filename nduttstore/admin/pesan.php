<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: /nduttstore/admin/login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama'] ?? 'Administrator';

// Cek tabel pesan
$cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'pesan'");

if (!$cek_tabel || mysqli_num_rows($cek_tabel) == 0) {
    die("Tabel pesan tidak ditemukan di database NduttStore.");
}

// Ambil struktur kolom
$hasil_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pesan");
$kolom = [];

while ($c = mysqli_fetch_assoc($hasil_kolom)) {
    $kolom[] = $c['Field'];
}

// Cari nama kolom yang cocok
function cariKolom($daftar, $pilihan) {
    foreach ($pilihan as $p) {
        if (in_array($p, $daftar, true)) {
            return $p;
        }
    }
    return null;
}

$id_col = cariKolom($kolom, ['id', 'id_pesan']);
$nama_col = cariKolom($kolom, ['nama', 'nama_pengirim', 'pengirim']);
$email_col = cariKolom($kolom, ['email', 'email_pengirim']);
$subjek_col = cariKolom($kolom, ['subjek', 'judul']);
$isi_col = cariKolom($kolom, ['isi_pesan', 'pesan', 'isi', 'message', 'pesan_masuk']);
$tanggal_col = cariKolom($kolom, ['tanggal', 'created_at', 'tanggal_pesan', 'waktu']);

// Hapus pesan
if (isset($_GET['hapus']) && $id_col) {
    $id = (int) $_GET['hapus'];

    mysqli_query(
        $koneksi,
        "DELETE FROM pesan WHERE `$id_col` = $id"
    );

    header("Location: /nduttstore/admin/pesan.php?terhapus=1");
    exit;
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');
$where = '';

if ($cari !== '') {
    $kata = mysqli_real_escape_string($koneksi, $cari);
    $cari_kolom = [];

    foreach ([$nama_col, $email_col, $subjek_col, $isi_col] as $c) {
        if ($c) {
            $cari_kolom[] = "`$c` LIKE '%$kata%'";
        }
    }

    if ($cari_kolom) {
        $where = "WHERE (" . implode(" OR ", $cari_kolom) . ")";
    }
}

// Urutan data
$order = $id_col
    ? "ORDER BY `$id_col` DESC"
    : ($tanggal_col ? "ORDER BY `$tanggal_col` DESC" : "");

// Ambil data
$query = mysqli_query(
    $koneksi,
    "SELECT * FROM pesan $where $order"
);

if (!$query) {
    die("Gagal mengambil pesan: " . mysqli_error($koneksi));
}

$total = mysqli_num_rows($query);

// Fungsi menampilkan data dengan aman
function tampil($row, $col) {
    if (!$col || !isset($row[$col]) || $row[$col] === '') {
        return '-';
    }

    return htmlspecialchars((string)$row[$col], ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Masuk - NduttStore</title>

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

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 15px;
    background: linear-gradient(180deg, #667eea, #764ba2);
    color: white;
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

.menu a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px 15px;
    border-radius: 10px;
    margin-bottom: 8px;
    transition: .3s;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.2);
    transform: translateX(4px);
}

.main {
    margin-left: 250px;
    padding: 30px;
}

.topbar {
    background: white;
    padding: 20px 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topbar span {
    color: #667eea;
    font-weight: bold;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    margin-bottom: 25px;
}

.card p {
    color: #777;
    margin-bottom: 10px;
}

.card h2 {
    color: #667eea;
    font-size: 28px;
}

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
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

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

button {
    background: #667eea;
    color: white;
    border: none;
    padding: 11px 16px;
    border-radius: 8px;
    cursor: pointer;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th {
    background: #f1f3ff;
    color: #555;
    text-align: left;
}

th, td {
    padding: 15px 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    vertical-align: top;
}

.isi {
    max-width: 300px;
    overflow-wrap: anywhere;
    line-height: 1.5;
}

.btn-hapus {
    background: #e74c3c;
    color: white;
    padding: 9px 12px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    display: inline-block;
}

.notif {
    padding: 13px;
    background: #d8f8e5;
    color: #16834a;
    border-radius: 9px;
    margin-bottom: 18px;
}

.kosong {
    text-align: center;
    padding: 35px;
    color: #888;
}

@media(max-width: 800px) {
    .sidebar {
        width: 190px;
    }

    .main {
        margin-left: 190px;
        padding: 15px;
    }
}
</style>
</head>

<body>

<div class="sidebar">
    <div class="logo">
        <h2>📚 NduttStore</h2>
        <p>ADMIN PANEL</p>
    </div>

    <div class="menu">
        <a href="/nduttstore/admin/dashboard.php">🏠 Dashboard</a>
        <a href="/nduttstore/admin/kategori.php">📂 Kategori Buku</a>
        <a href="/nduttstore/admin/buku.php">📚 Data Buku</a>
        <a href="/nduttstore/admin/users.php">👥 Data Pengguna</a>
        <a href="/nduttstore/admin/pesanan.php">🛒 Pesanan</a>
        <a href="/nduttstore/admin/pesan.php" class="active">✉️ Pesan Masuk</a>
        <a href="/nduttstore/admin/logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h2>✉️ Pesan Masuk</h2>
        <div>Halo, <span><?= htmlspecialchars($nama_admin, ENT_QUOTES, 'UTF-8') ?></span></div>
    </div>

    <?php if (isset($_GET['terhapus'])): ?>
        <div class="notif">Pesan berhasil dihapus!</div>
    <?php endif; ?>

    <div class="card">
        <p>📩 Jumlah Pesan Ditampilkan</p>
        <h2><?= $total ?></h2>
    </div>

    <div class="content">

        <div class="content-header">
            <h3>Daftar Pesan Pengguna</h3>

            <form method="GET" class="search">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari pesan..."
                    value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>"
                >
                <button type="submit">Cari</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <?php if ($nama_col): ?><th>Nama</th><?php endif; ?>
                        <?php if ($email_col): ?><th>Email</th><?php endif; ?>
                        <?php if ($subjek_col): ?><th>Subjek</th><?php endif; ?>
                        <?php if ($isi_col): ?><th>Isi Pesan</th><?php endif; ?>
                        <?php if ($tanggal_col): ?><th>Tanggal</th><?php endif; ?>
                        <?php if ($id_col): ?><th>Aksi</th><?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <?php if ($nama_col): ?>
                                <td><?= tampil($row, $nama_col) ?></td>
                            <?php endif; ?>

                            <?php if ($email_col): ?>
                                <td><?= tampil($row, $email_col) ?></td>
                            <?php endif; ?>

                            <?php if ($subjek_col): ?>
                                <td><?= tampil($row, $subjek_col) ?></td>
                            <?php endif; ?>

                            <?php if ($isi_col): ?>
                                <td class="isi">
                                    <?= nl2br(tampil($row, $isi_col)) ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($tanggal_col): ?>
                                <td><?= tampil($row, $tanggal_col) ?></td>
                            <?php endif; ?>

                            <?php if ($id_col): ?>
                                <td>
                                    <a
                                        href="/nduttstore/admin/pesan.php?hapus=<?= (int)$row[$id_col] ?>"
                                        class="btn-hapus"
                                        onclick="return confirm('Yakin ingin menghapus pesan ini?')"
                                    >Hapus</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="kosong">
                            Belum ada pesan masuk.
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