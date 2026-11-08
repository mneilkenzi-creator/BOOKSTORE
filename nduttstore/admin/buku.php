<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: /nduttstore/admin/login.php");
    exit;
}

$nama_admin = $_SESSION['admin_nama'] ?? 'Administrator';

$folder_upload = __DIR__ . "/../uploads/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

$pesan = "";

// TAMBAH BUKU
if (isset($_POST['tambah'])) {

    $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul']));
    $penulis = mysqli_real_escape_string($koneksi, trim($_POST['penulis']));
    $penerbit = mysqli_real_escape_string($koneksi, trim($_POST['penerbit']));
    $tahun = (int) $_POST['tahun_terbit'];
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];
    $kategori = (int) $_POST['kategori_id'];
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi']));

    $gambar = "";

    if (!empty($_FILES['gambar']['name'])) {

        $ekstensi = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ekstensi, $allowed) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK || $_FILES['gambar']['size'] > 3 * 1024 * 1024) {
            header("Location: /nduttstore/admin/buku.php?status=upload_gagal");
            exit;
        }

        $gambar = uniqid("buku_") . "." . $ekstensi;

        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $folder_upload . $gambar)) {
            header("Location: /nduttstore/admin/buku.php?status=upload_gagal");
            exit;
        }
    }

    $sql = mysqli_query($koneksi, "
        INSERT INTO buku
        (judul, penulis, penerbit, tahun_terbit, harga, stok, kategori_id, gambar, deskripsi)
        VALUES
        ('$judul', '$penulis', '$penerbit', '$tahun', '$harga', '$stok', '$kategori', '$gambar', '$deskripsi')
    ");

    if (!$sql && $gambar !== "" && file_exists($folder_upload . $gambar)) {
        unlink($folder_upload . $gambar);
    }

    header("Location: /nduttstore/admin/buku.php?status=" . ($sql ? "tambah" : "gagal"));
    exit;
}

// EDIT BUKU
if (isset($_POST['edit'])) {

    $id = (int) $_POST['id'];
    $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul']));
    $penulis = mysqli_real_escape_string($koneksi, trim($_POST['penulis']));
    $penerbit = mysqli_real_escape_string($koneksi, trim($_POST['penerbit']));
    $tahun = (int) $_POST['tahun_terbit'];
    $harga = (float) $_POST['harga'];
    $stok = (int) $_POST['stok'];
    $kategori = (int) $_POST['kategori_id'];
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi']));

    $gambar_baru = "";

    if (!empty($_FILES['gambar']['name'])) {

        $ekstensi = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ekstensi, $allowed) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK || $_FILES['gambar']['size'] > 3 * 1024 * 1024) {
            header("Location: /nduttstore/admin/buku.php?status=upload_gagal");
            exit;
        }

        $gambar_baru = uniqid("buku_") . "." . $ekstensi;

        if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $folder_upload . $gambar_baru)) {
            header("Location: /nduttstore/admin/buku.php?status=upload_gagal");
            exit;
        }
    }

    if ($gambar_baru !== "") {

        $sql = mysqli_query($koneksi, "
            UPDATE buku SET
            judul='$judul',
            penulis='$penulis',
            penerbit='$penerbit',
            tahun_terbit='$tahun',
            harga='$harga',
            stok='$stok',
            kategori_id='$kategori',
            gambar='$gambar_baru',
            deskripsi='$deskripsi'
            WHERE id='$id'
        ");

        if ($sql) {
            $lama = mysqli_query($koneksi, "SELECT gambar FROM buku WHERE id='$id'");

            // Gambar lama dihapus setelah update berhasil.
            // Nama lama diambil dari form.
            $gambar_lama = basename($_POST['gambar_lama'] ?? '');

            if ($gambar_lama !== "" && file_exists($folder_upload . $gambar_lama)) {
                unlink($folder_upload . $gambar_lama);
            }
        } else {
            if (file_exists($folder_upload . $gambar_baru)) {
                unlink($folder_upload . $gambar_baru);
            }
        }

    } else {

        $sql = mysqli_query($koneksi, "
            UPDATE buku SET
            judul='$judul',
            penulis='$penulis',
            penerbit='$penerbit',
            tahun_terbit='$tahun',
            harga='$harga',
            stok='$stok',
            kategori_id='$kategori',
            deskripsi='$deskripsi'
            WHERE id='$id'
        ");
    }

    header("Location: /nduttstore/admin/buku.php?status=" . ($sql ? "edit" : "gagal"));
    exit;
}

// HAPUS BUKU
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $cari = mysqli_query($koneksi, "SELECT gambar FROM buku WHERE id='$id'");
    $data = mysqli_fetch_assoc($cari);

    $sql = mysqli_query($koneksi, "DELETE FROM buku WHERE id='$id'");

    if ($sql && !empty($data['gambar'])) {
        $file = $folder_upload . basename($data['gambar']);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    header("Location: /nduttstore/admin/buku.php?status=" . ($sql ? "hapus" : "gagal"));
    exit;
}

// DATA KATEGORI
$kategori_query = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

if (!$kategori_query) {
    die("Query kategori gagal: " . mysqli_error($koneksi));
}

// PENCARIAN
$cari = mysqli_real_escape_string($koneksi, trim($_GET['cari'] ?? ''));

$query = mysqli_query($koneksi, "
    SELECT buku.*, kategori.nama_kategori
    FROM buku
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
    WHERE buku.judul LIKE '%$cari%'
       OR buku.penulis LIKE '%$cari%'
    ORDER BY buku.id DESC
");

if (!$query) {
    die("Query buku gagal: " . mysqli_error($koneksi));
}

$total_buku = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM buku"));
$total_stok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(stok),0) AS total FROM buku"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Buku - NduttStore</title>

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

/* STATISTIK */
.stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
    display: flex;
    align-items: center;
    gap: 18px;
}

.stat-icon {
    width: 55px;
    height: 55px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
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
}

.content h2 {
    margin-bottom: 10px;
    color: #444;
}

.content p {
    color: #777;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* BUTTON */
.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    opacity: .9;
}

.btn-edit {
    background: #e8edff;
    color: #5568d9;
    padding: 8px 12px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
}

.btn-hapus {
    background: #ffebee;
    color: #e53935;
    padding: 8px 12px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
}

/* SEARCH */
.search {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.search input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 9px;
    outline: none;
}

.search input:focus {
    border-color: #667eea;
}

/* TABLE */
.table-container {
    overflow-x: auto;
    margin-top: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #667eea;
    color: white;
    padding: 14px;
    text-align: left;
}

td {
    padding: 13px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

tr:hover td {
    background: #f7f8ff;
}

.cover {
    width: 55px;
    height: 70px;
    object-fit: cover;
    border-radius: 7px;
}

.no-cover {
    width: 55px;
    height: 70px;
    background: #eeeaff;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    z-index: 100;
    inset: 0;
    background: rgba(0,0,0,.55);
    justify-content: center;
    align-items: center;
    padding: 15px;
}

.modal:target {
    display: flex;
}

.modal-box {
    background: white;
    width: 550px;
    max-width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 25px;
    border-radius: 16px;
}

.modal-box h2 {
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
    font-size: 13px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
}

.form-group textarea {
    min-height: 90px;
    resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #667eea;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

.btn-cancel {
    padding: 12px 18px;
    background: #eee;
    color: #444;
    border-radius: 9px;
    text-decoration: none;
}

/* ALERT */
.alert {
    padding: 13px;
    border-radius: 9px;
    margin-bottom: 20px;
    background: #e8f5e9;
    color: #2e7d32;
}

.alert.error {
    background: #ffebee;
    color: #c62828;
}

/* RESPONSIVE */
@media(max-width: 1000px) {
    .stats {
        grid-template-columns: 1fr 1fr;
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

    .stats {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
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
        <li><a href="/nduttstore/admin/buku.php" class="active">📚 Data Buku</a></li>
        <li><a href="/nduttstore/admin/users.php">👥 Data User</a></li>
        <li><a href="/nduttstore/admin/pesanan.php">🛒 Pesanan</a></li>
        <li><a href="/nduttstore/admin/pesan.php">💬 Pesan Masuk</a></li>
        <li><a href="/nduttstore/admin/logout.php">🚪 Logout</a></li>
    </ul>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1>📚 Data Buku</h1>
        <div class="admin">
            👤 <?= htmlspecialchars($nama_admin); ?>
        </div>
    </div>

    <?php
    $status = $_GET['status'] ?? '';

    if ($status == 'tambah') echo '<div class="alert">Buku berhasil ditambahkan!</div>';
    if ($status == 'edit') echo '<div class="alert">Buku berhasil diperbarui!</div>';
    if ($status == 'hapus') echo '<div class="alert">Buku berhasil dihapus!</div>';
    if ($status == 'gagal') echo '<div class="alert error">Operasi gagal. Periksa struktur tabel database.</div>';
    if ($status == 'upload_gagal') echo '<div class="alert error">Upload gambar gagal. Gunakan JPG, PNG, atau WEBP maksimal 3 MB.</div>';
    ?>

    <div class="stats">

        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <h3>Total Judul Buku</h3>
                <h2><?= $total_buku; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3>Total Stok Buku</h3>
                <h2><?= $total_stok; ?></h2>
            </div>
        </div>

    </div>

    <div class="content">

        <h2>Kelola Data Buku</h2>

        <p>
            Tambahkan, perbarui, cari, dan kelola koleksi buku NduttStore.
        </p>

        <a href="#tambah" class="btn-primary">+ Tambah Buku Baru</a>

        <form method="GET" class="search">
            <input
                type="text"
                name="cari"
                placeholder="Cari judul atau penulis buku..."
                value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>"
            >
            <button class="btn-primary" type="submit">Cari</button>
            <a href="/nduttstore/admin/buku.php" class="btn-cancel">Reset</a>
        </form>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Sampul</th>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
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
                            <?php if (!empty($row['gambar']) && file_exists($folder_upload . basename($row['gambar']))): ?>
                                <img class="cover" src="/nduttstore/uploads/<?= rawurlencode(basename($row['gambar'])); ?>" alt="Sampul buku">
                            <?php else: ?>
                                <div class="no-cover">📖</div>
                            <?php endif; ?>
                        </td>

                        <td><b><?= htmlspecialchars($row['judul']); ?></b></td>
                        <td><?= htmlspecialchars($row['penulis']); ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
                        <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                        <td><?= (int)$row['stok']; ?></td>

                        <td>
                            <a class="btn-edit" href="#edit<?= $row['id']; ?>">Edit</a>

                            <a
                                class="btn-hapus"
                                href="/nduttstore/admin/buku.php?hapus=<?= (int)$row['id']; ?>"
                                onclick="return confirm('Yakin ingin menghapus buku ini?')"
                            >Hapus</a>
                        </td>
                    </tr>

                    <!-- MODAL EDIT -->
                    <div class="modal" id="edit<?= $row['id']; ?>">
                        <div class="modal-box">

                            <h2>✏️ Edit Buku</h2>

                            <form method="POST" enctype="multipart/form-data">

                                <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($row['gambar'] ?? '', ENT_QUOTES); ?>">

                                <div class="form-grid">

                                    <div class="form-group full">
                                        <label>Judul Buku</label>
                                        <input type="text" name="judul" value="<?= htmlspecialchars($row['judul']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Penulis</label>
                                        <input type="text" name="penulis" value="<?= htmlspecialchars($row['penulis']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Penerbit</label>
                                        <input type="text" name="penerbit" value="<?= htmlspecialchars($row['penerbit']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tahun Terbit</label>
                                        <input type="number" name="tahun_terbit" min="1901" max="2155" value="<?= (int)$row['tahun_terbit']; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Harga</label>
                                        <input type="number" name="harga" min="0" value="<?= htmlspecialchars($row['harga']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Stok</label>
                                        <input type="number" name="stok" min="0" value="<?= (int)$row['stok']; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Kategori</label>
                                        <select name="kategori_id" required>
                                            <option value="">Pilih Kategori</option>
                                            <?php
                                            mysqli_data_seek($kategori_query, 0);
                                            while ($kat = mysqli_fetch_assoc($kategori_query)):
                                            ?>
                                                <option value="<?= (int)$kat['id']; ?>" <?= $row['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group full">
                                        <label>Ganti Sampul (Opsional)</label>
                                        <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                                    </div>

                                    <div class="form-group full">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi"><?= htmlspecialchars($row['deskripsi'] ?? ''); ?></textarea>
                                    </div>

                                </div>

                                <div class="modal-actions">
                                    <a href="/nduttstore/admin/buku.php" class="btn-cancel">Batal</a>
                                    <button type="submit" name="edit" class="btn-primary">Simpan Perubahan</button>
                                </div>

                            </form>
                        </div>
                    </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" style="text-align:center;padding:35px;">
                            📚 Belum ada data buku. Silakan tambahkan buku baru.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- MODAL TAMBAH -->
<div class="modal" id="tambah">

    <div class="modal-box">

        <h2>➕ Tambah Buku Baru</h2>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <div class="form-group full">
                    <label>Judul Buku</label>
                    <input type="text" name="judul" placeholder="Masukkan judul buku" required>
                </div>

                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="penulis" placeholder="Nama penulis" required>
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" placeholder="Nama penerbit" required>
                </div>

                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" min="1901" max="2155" placeholder="2026" required>
                </div>

                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" min="0" placeholder="50000" required>
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" min="0" placeholder="10" required>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori_id" required>
                        <option value="">Pilih Kategori</option>

                        <?php
                        mysqli_data_seek($kategori_query, 0);
                        while ($kat = mysqli_fetch_assoc($kategori_query)):
                        ?>
                            <option value="<?= (int)$kat['id']; ?>">
                                <?= htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

                <div class="form-group full">
                    <label>Gambar Sampul</label>
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                    <small>Maksimal 3 MB. JPG, PNG, WEBP.</small>
                </div>

                <div class="form-group full">
                    <label>Deskripsi Buku</label>
                    <textarea name="deskripsi" placeholder="Masukkan deskripsi buku..."></textarea>
                </div>

            </div>

            <div class="modal-actions">
                <a href="/nduttstore/admin/buku.php" class="btn-cancel">Batal</a>
                <button type="submit" name="tambah" class="btn-primary">Simpan Buku</button>
            </div>

        </form>

    </div>

</div>

</body>
</html>