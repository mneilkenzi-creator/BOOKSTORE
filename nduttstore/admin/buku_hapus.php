
<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: buku.php?pesan=id_tidak_valid");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT gambar FROM buku WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$buku = mysqli_fetch_assoc($result);

if (!$buku) {
    header("Location: buku.php?pesan=tidak_ditemukan");
    exit;
}

$hapus = mysqli_prepare($conn, "DELETE FROM buku WHERE id = ?");
mysqli_stmt_bind_param($hapus, "i", $id);

if (mysqli_stmt_execute($hapus)) {
    if (!empty($buku['gambar'])) {
        $file = "../assets/img/" . basename($buku['gambar']);
        if (is_file($file)) {
            unlink($file);
        }
    }

    header("Location: buku.php?pesan=hapus");
    exit;
}

header("Location: buku.php?pesan=gagal_hapus");
exit;
?>
```