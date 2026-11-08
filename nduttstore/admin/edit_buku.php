<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama'] ?? 'Administrator';

$id = (int) ($_GET['id'] ?? 0);

$query = mysqli_query($koneksi, "
    SELECT * FROM buku WHERE id = $id
");

if (!$query || mysqli_num_rows($query) == 0) {
    die("Data buku tidak ditemukan!");
}

$buku = mysqli_fetch_assoc($query);

$kategori = mysqli_query($koneksi, "
    SELECT * FROM kategori ORDER BY nama_kategori ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Buku - NduttStore</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: #f4f6fb;
    color: #333;
}

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: linear-gradient(180deg,#667eea,#764ba2);
    padding: 25px 15px;
    color: white;
}

.logo {
    text-align: center;
    margin-bottom: 35px;
}

.menu a {
    display: block;
    padding: 14px 15px;
    margin-bottom: 8px;
    color: white;
    text-decoration: none;
    border-radius: 10px;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.2);
}

.main {
    margin-left: 250px;
    padding: 30px;
}

.topbar, .content {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    margin-bottom: 25px;
}

.topbar {
    display: flex;
    justify-content: space-between;
}

.topbar span {
    color: #667eea;
    font-weight: bold;
}

.form-group {
    margin-bottom: 17px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
}

input:focus, select:focus, textarea:focus {
    border-color: #667eea;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

button, .btn-kembali {
    display: inline-block;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    background: #667eea;
    color: white;
    text-decoration: none;
    cursor: pointer;
}

.btn-kembali {
    background: #777;
}

.preview {
    width: 110px;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

@media(max-width:800px) {
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
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="kategori.php">📂 Kategori Buku</a>
        <a href="buku.php" class="active">📚 Data Buku</a>
        <a href="users.php">👥 Data Pengguna</a>
        <a href="pesanan.php">🛒 Pesanan</a>
        <a href="pesan.php">✉️ Pesan Masuk</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h2>✏️ Edit Data Buku</h2>
        <div>Halo, <span><?= htmlspecialchars($nama_admin) ?></span></div>
    </div>

    <div class="content">

        <h3 style="margin-bottom:25px;">Form Edit Buku</h3>

        <form action="buku.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="edit_buku" value="1">
            <input type="hidden" name="id" value="<?= $buku['id'] ?>">
            <input type="hidden" name="gambar_lama"
                   value="<?= htmlspecialchars($buku['gambar'] ?? '') ?>">

            <div class="form-group">
                <label>Judul Buku</label>
                <input type="text" name="judul"
                       value="<?= htmlspecialchars($buku['judul']) ?>" required>
            </div>

            <div class="form-group">
                <label>Penulis</label>
                <input type="text" name="penulis"
                       value="<?= htmlspecialchars($buku['penulis']) ?>" required>
            </div>

            <div class="form-group">
                <label>Penerbit</label>
                <input type="text" name="penerbit"
                       value="<?= htmlspecialchars($buku['penerbit']) ?>" required>
            </div>

            <div class="form-group">
                <label>Tahun Terbit</label>
                <input type="number" name="tahun_terbit"
                       value="<?= htmlspecialchars($buku['tahun_terbit']) ?>" required>
            </div>

            <div class="form-group">
                <label>Harga</label>
                <input type="number" name="harga"
                       value="<?= htmlspecialchars($buku['harga']) ?>" required>
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stok"
                       value="<?= htmlspecialchars($buku['stok']) ?>" required>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori_id" required>
                    <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                        <option value="<?= $k['id'] ?>"
                            <?= $buku['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Gambar Buku Saat Ini</label><br>

                <?php if (!empty($buku['gambar'])): ?>
                    <img class="preview"
                         src="../uploads/<?= htmlspecialchars($buku['gambar']) ?>"
                         alt="Cover buku">
                <?php else: ?>
                    <p>Belum ada gambar.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Ganti Gambar (Opsional)</label>
                <input type="file" name="gambar" accept="image/*">
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi"><?= htmlspecialchars($buku['deskripsi'] ?? '') ?></textarea>
            </div>

            <button type="submit">💾 Simpan Perubahan</button>
            <a href="buku.php" class="btn-kembali">Kembali</a>

        </form>

    </div>
</div>

</body>
</html>
