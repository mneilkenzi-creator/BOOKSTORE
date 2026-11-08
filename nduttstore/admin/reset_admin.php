<?php

include "../config/koneksi.php";

$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

mysqli_query($conn, "DELETE FROM admin WHERE username='admin'");

$sql = "INSERT INTO admin (nama, username, password)
        VALUES ('Administrator', 'admin', '$hash')";

if (mysqli_query($conn, $sql)) {
    echo "<h1>ADMIN BERHASIL DIBUAT</h1>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br><br>";
    echo "Sekarang buka login admin.";
} else {
    echo "ERROR: " . mysqli_error($conn);
}