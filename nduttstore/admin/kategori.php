
```php
<?php
require_once __DIR__ . "/../config/koneksi.php";

// CEK LOGIN ADMIN
if (!isset($_SESSION['admin_id'])) {
    header("Location: /nduttstore/admin/login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama'] ?? 'Administrator';

// TAMBAH KATEGORI
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['nama_kategori'] ?? '')
    );

    if ($nama !== '') {
        mysqli_query(
            $koneksi,
            "INSERT INTO kategori (nama_kategori) VALUES ('$nama')"
        );
    }

    header("Location: /nduttstore/admin/kategori.php");
    exit;
}

// HAPUS KATEGORI
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    mysqli_query(
        $koneksi,
        "DELETE FROM kategori WHERE id = $id"
    );

    header("Location: /nduttstore/admin/kategori.php");
    exit;
}

// DATA KATEGORI
$query = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY id DESC"
);

if (!$query) {
    die("Query kategori gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kategori Buku - NduttStore</title>

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
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #667eea, #764ba2);
    color: white;
    padding: 25px 15px;
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
}

.topbar h1 {
    font-size: 26px;
    color: #333;
}

.admin {
    color: #667eea;
    font-weight: bold;
}

/* CONTENT */
.content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}

.content h2 {
    margin-bottom: 10px;
    color: #444;
}

.content p {
    color: #777;
    margin-bottom: 25px;
    line-height: 1.6;
}

/* FORM */
.form-tambah {
    display: flex;
    gap: 10px;
    margin: 20px 0 30px;
}

.form-tambah input {
    flex: 1;
    max-width: 450px;
    padding: 13px 15px;
    border: 1px solid #ddd;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
}

.form-tambah input:focus {
    border-color: #667eea;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 13px 22px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary:hover {
    opacity: .9;
}

/* TABLE */
.table-container {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
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

.btn-hapus {
    display: inline-block;
    background: #ffebee;
    color: #e53935;
    padding: 8px 14px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
}

.btn-hapus:hover {
    background: #e53935;
    color: white;
}

/* RESPONSIVE */
@media(max-width: 1000px) {
    .main {
        padding: 20px;
    }
}

@media(max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    .main {
        margin-left: 200px;
        padding: 15px;
    }

    .topbar {
        flex-wrap: wrap;
        gap: 12px;
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

    .form-tambah {
        flex-direction: column;
    }

    .form-tambah input {
        max-width: 100%;
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
        <li>
            <a href="/nduttstore/admin/dashboard.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/kategori.php" class="active">
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
                👥 Data User
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/pesanan.php">
                🛒 Pesanan
            </a>
        </li>

        <li>
            <a href="/nduttstore/admin/pesan.php">
                💬 Pesan Masuk
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
        <h1>📂 Kategori Buku</h1>

        <div class="admin">
            👤 <?= htmlspecialchars($nama_admin); ?>
        </div>
    </div>

    <div class="content">

        <h2>Kelola Kategori Buku 📚</h2>

        <p>
            Tambahkan dan kelola kategori buku yang tersedia
            di toko NduttStore.
        </p>

        <h3>➕ Tambah Kategori Baru</h3>

        <form method="POST" class="form-tambah">

            <input
                type="text"
                name="nama_kategori"
                placeholder="Masukkan nama kategori..."
                required
            >

            <button type="submit" name="tambah" class="btn-primary">
                + Tambah Kategori
            </button>

        </form>

        <h3>📋 Daftar Kategori</h3>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <?= htmlspecialchars($row['nama_kategori']); ?>
                            </td>

                            <td>
                                <a
                                    class="btn-hapus"
                                    href="/nduttstore/admin/kategori.php?hapus=<?= (int)$row['id']; ?>"
                                    onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                                >
                                    🗑 Hapus
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3" style="text-align:center;">
                            Belum ada kategori buku.
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
```
