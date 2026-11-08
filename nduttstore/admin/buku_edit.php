<?php
// Jangan panggil session_start() di sini.
// Jika koneksi.php sudah menjalankan session_start(), pemanggilan kedua
// akan menghasilkan Notice.

require_once __DIR__ . '/../config/koneksi.php';

// Samakan nama variabel koneksi yang mungkin dipakai di koneksi.php.
if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    if (isset($conn) && $conn instanceof mysqli) {
        $koneksi = $conn;
    } elseif (isset($mysqli) && $mysqli instanceof mysqli) {
        $koneksi = $mysqli;
    } elseif (isset($conn_db) && $conn_db instanceof mysqli) {
        $koneksi = $conn_db;
    } elseif (isset($connect) && $connect instanceof mysqli) {
        $koneksi = $connect;
    }
}

// Cek koneksi
if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    die(
        "Koneksi database belum ditemukan. Buka config/koneksi.php dan " .
        "pastikan variabel koneksinya bernama $koneksi atau $conn."
    );
}

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek ID buku
if (!isset($_GET['id'])) {
    header("Location: buku.php");
    exit;
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data buku
$query = mysqli_query($koneksi, "SELECT * FROM buku WHERE id='$id'");
$buku = mysqli_fetch_assoc($query);

if (!$buku) {
    die("Data buku tidak ditemukan!");
}

// Ambil kategori
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori");

// Proses update
if (isset($_POST['update'])) {

    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $penulis = mysqli_real_escape_string($koneksi, $_POST['penulis']);
    $penerbit = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $tahun = mysqli_real_escape_string($koneksi, $_POST['tahun']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);

    $gambar = $buku['gambar'];

    // Upload gambar jika ada gambar baru
    if (!empty($_FILES['gambar']['name'])) {
        $nama_gambar = time() . '_' . basename($_FILES['gambar']['name']);
        $target = "../assets/img/" . $nama_gambar;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
            $gambar = $nama_gambar;
        } else {
            echo "<script>alert('Upload gambar gagal!');</script>";
        }
    }

    $update = mysqli_query($koneksi, "
        UPDATE buku SET
        judul='$judul',
        penulis='$penulis',
        penerbit='$penerbit',
        tahun='$tahun',
        harga='$harga',
        stok='$stok',
        id_kategori='$id_kategori',
        gambar='$gambar'
        WHERE id='$id'
    ");

    if ($update) {
        echo "<script>
            alert('Buku berhasil diperbarui!');
            window.location='buku.php';
        </script>";
        exit;
    } else {
        echo "Gagal memperbarui buku: " . mysqli_error($koneksi);
    }
}
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
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background: #f3f4f8;
    padding: 30px;
}

.container {
    max-width: 750px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

h2 {
    color: #5427a8;
    margin-bottom: 10px;
}

p {
    color: #777;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 17px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
}

input:focus, select:focus {
    border-color: #7541d8;
}

img {
    width: 100px;
    height: 130px;
    object-fit: cover;
    margin-top: 10px;
    border-radius: 8px;
}

button, .back {
    display: inline-block;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-weight: bold;
}

button {
    background: #6c3bc5;
}

button:hover {
    background: #5125a5;
}

.back {
    background: #777;
    margin-left: 8px;
}
</style>
</head>

<body>

<div class="container">

    <h2>✏️ Edit Data Buku</h2>
    <p>Perbarui informasi buku di NduttStore.</p>

    <form method="POST" enctype="multipart/form-data">

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
            <input type="number" name="tahun"
            value="<?= htmlspecialchars($buku['tahun']) ?>" required>
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
            <label>Kategori Buku</label>
            <select name="id_kategori" required>
                <?php while ($kat = mysqli_fetch_assoc($kategori)) : ?>
                    <option value="<?= $kat['id'] ?>"
                    <?= ($kat['id'] == $buku['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Gambar Buku</label>
            <input type="file" name="gambar" accept="image/*">

            <?php if (!empty($buku['gambar'])) : ?>
                <br>
                <img src="../assets/img/<?= htmlspecialchars($buku['gambar']) ?>">
            <?php endif; ?>
        </div>

        <button type="submit" name="update">💾 Simpan Perubahan</button>
        <a href="buku.php" class="back">Kembali</a>

    </form>

</div>

</body>
</html>