
<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";

$kategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $judul = trim($_POST['judul'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $penerbit = trim($_POST['penerbit'] ?? '');
    $tahun = (int)($_POST['tahun_terbit'] ?? 0);
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $gambar = '';

    if ($judul === '' || $penulis === '' || $kategori_id <= 0 || $harga < 0 || $stok < 0) {
        $error = "Lengkapi judul, penulis, kategori, harga, dan stok dengan benar.";
    } else {
        $cek = mysqli_prepare($conn, "SELECT id FROM kategori WHERE id = ?");
        mysqli_stmt_bind_param($cek, "i", $kategori_id);
        mysqli_stmt_execute($cek);
        $kategori_valid = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($kategori_valid) === 0) {
            $error = "Kategori tidak ditemukan.";
        } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload cover gagal.";
            } else {
                $tmp = $_FILES['gambar']['tmp_name'];
                $mime = mime_content_type($tmp);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowed[$mime])) {
                    $error = "Cover harus berupa JPG, PNG, atau WEBP.";
                } else {
                    $folder = "../assets/img/";

                    if (!is_dir($folder)) {
                        mkdir($folder, 0755, true);
                    }

                    $gambar = bin2hex(random_bytes(8)) . "." . $allowed[$mime];

                    if (!move_uploaded_file($tmp, $folder . $gambar)) {
                        $error = "Gagal menyimpan file cover.";
                    }
                }
            }
        }

        if ($error === '') {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO buku
                (kategori_id, judul, penulis, penerbit, tahun_terbit, harga, stok, deskripsi, gambar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "isssidiss",
                $kategori_id,
                $judul,
                $penulis,
                $penerbit,
                $tahun,
                $harga,
                $stok,
                $deskripsi,
                $gambar
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: buku.php?pesan=tambah");
                exit;
            } else {
                if ($gambar !== '' && is_file("../assets/img/" . $gambar)) {
                    unlink("../assets/img/" . $gambar);
                }
                $error = "Buku gagal ditambahkan. Periksa struktur tabel database.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Buku | NduttStore</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f5f7fb;color:#1e293b}
header{background:#111827;color:white;padding:20px 6%;display:flex;justify-content:space-between;align-items:center}
header a{color:#ddd;text-decoration:none;font-size:13px}
main{max-width:900px;margin:35px auto;padding:0 18px}
.card{background:white;border:1px solid #e7eaf1;border-radius:20px;padding:30px;box-shadow:0 10px 30px #17203308}
h1{margin:0 0 8px;font-size:27px}
.sub{color:#8993a5;font-size:13px;margin-bottom:25px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.field{margin-bottom:17px}
label{display:block;font-size:12px;font-weight:700;margin-bottom:8px}
input,select,textarea{width:100%;padding:12px 13px;border:1px solid #dfe4ed;border-radius:10px;font:inherit;font-size:13px;outline:none;background:white}
input:focus,select:focus,textarea:focus{border-color:#8063f5;box-shadow:0 0 0 3px #8063f51a}
textarea{resize:vertical;min-height:110px}
.full{grid-column:1/-1}
.btn{display:inline-block;border:0;border-radius:10px;padding:12px 18px;font-weight:700;font-size:13px;text-decoration:none;cursor:pointer}
.primary{background:#6d4aff;color:white}
.secondary{background:#eef0f6;color:#374151}
.error{background:#fee2e2;color:#b91c1c;padding:13px;border-radius:10px;margin-bottom:18px;font-size:13px}
.actions{display:flex;gap:10px;margin-top:10px}
.note{color:#8993a5;font-size:11px;margin-top:7px}
@media(max-width:600px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.card{padding:20px}header{padding:18px}}
</style>
</head>
<body>
<header>
    <strong>📚 NduttStore Admin</strong>
    <a href="buku.php">← Kembali ke Data Buku</a>
</header>
<main>
<div class="card">
    <h1>Tambah Buku Baru ✨</h1>
    <p class="sub">Masukkan informasi buku yang akan ditampilkan di toko.</p>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="grid">
            <div class="field">
                <label>Judul Buku *</label>
                <input type="text" name="judul" required value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Penulis *</label>
                <input type="text" name="penulis" required value="<?= htmlspecialchars($_POST['penulis'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Kategori *</label>
                <select name="kategori_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                    <option value="<?= (int)$k['id'] ?>" <?= (($_POST['kategori_id'] ?? '') == $k['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field">
                <label>Penerbit</label>
                <input type="text" name="penerbit" value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Tahun Terbit</label>
                <input type="number" name="tahun_terbit" min="0" max="2100" value="<?= htmlspecialchars($_POST['tahun_terbit'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Harga (Rp) *</label>
                <input type="number" name="harga" min="0" step="1" required value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Stok Buku *</label>
                <input type="number" name="stok" min="0" required value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
            </div>
            <div class="field">
                <label>Cover Buku</label>
                <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <div class="note">Format JPG, PNG, atau WEBP.</div>
            </div>
            <div class="field full">
                <label>Deskripsi Buku</label>
                <textarea name="deskripsi"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="actions">
            <button type="submit" class="btn primary">＋ Simpan Buku</button>
            <a href="buku.php" class="btn secondary">Batal</a>
        </div>
    </form>
</div>
</main>
</body>
</html>