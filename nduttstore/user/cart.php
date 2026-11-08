<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Menerima ID buku dari form POST maupun URL GET
$id_buku = 0;

if (isset($_POST['id_buku'])) {
    $id_buku = (int) $_POST['id_buku'];
} elseif (isset($_POST['id'])) {
    $id_buku = (int) $_POST['id'];
} elseif (isset($_GET['id_buku'])) {
    $id_buku = (int) $_GET['id_buku'];
} elseif (isset($_GET['id'])) {
    $id_buku = (int) $_GET['id'];
}

$jumlah = isset($_POST['jumlah'])
    ? (int) $_POST['jumlah']
    : (int) ($_GET['jumlah'] ?? 1);

if ($id_buku <= 0 || $jumlah <= 0) {
    echo "<script>
        alert('ID buku atau jumlah buku tidak valid. Silakan pilih buku kembali.');
        window.location.href='buku.php';
    </script>";
    exit;
}

// Periksa apakah buku tersedia
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id, stok FROM buku WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_buku);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$buku = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$buku) {
    echo "<script>
        alert('Buku tidak ditemukan.');
        window.location.href='buku.php';
    </script>";
    exit;
}

$stok = (int) $buku['stok'];

if ($jumlah > $stok) {
    echo "<script>
        alert('Jumlah buku melebihi stok yang tersedia.');
        window.location.href='detail_buku.php?id=$id_buku';
    </script>";
    exit;
}

// Periksa apakah buku sudah ada di keranjang
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id, jumlah FROM keranjang
     WHERE user_id = ? AND buku_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $user_id, $id_buku);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$keranjang = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ($keranjang) {

    $jumlah_baru = (int) $keranjang['jumlah'] + $jumlah;

    if ($jumlah_baru > $stok) {
        echo "<script>
            alert('Jumlah di keranjang melebihi stok yang tersedia.');
            window.location.href='keranjang.php';
        </script>";
        exit;
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE keranjang SET jumlah = ? WHERE id = ? AND user_id = ?"
    );

    $id_keranjang = (int) $keranjang['id'];

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $jumlah_baru,
        $id_keranjang,
        $user_id
    );

} else {

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO keranjang (user_id, buku_id, jumlah)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $user_id,
        $id_buku,
        $jumlah
    );
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: keranjang.php");
    exit;
}

$error = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);

die("Gagal menambahkan buku ke keranjang: " . htmlspecialchars($error));
?>