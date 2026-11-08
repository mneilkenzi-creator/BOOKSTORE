<?php

include "../config/koneksi.php";

$nama = "Administrator";
$username = "admin";
$password_asli = "admin123";
$password = password_hash($password_asli, PASSWORD_DEFAULT);

/* Hapus akun admin lama kalau ada */
mysqli_query($conn, "DELETE FROM admin WHERE username='admin'");

/* Buat akun admin baru */
$query = mysqli_query(
    $conn,
    "INSERT INTO admin (nama, username, password)
     VALUES ('$nama', '$username', '$password')"
);

if ($query) {

    echo "
    <div style='
        font-family:Arial;
        max-width:500px;
        margin:100px auto;
        padding:30px;
        border-radius:20px;
        background:#f0fff5;
        border:1px solid #b8f0cc;
        text-align:center;
    '>

        <h2 style='color:#20a45a;'>
            ✓ Admin Berhasil Dibuat
        </h2>

        <p>
            Username: <b>admin</b>
        </p>

        <p>
            Password: <b>admin123</b>
        </p>

        <br>

        <a href='login.php'
           style='
           display:inline-block;
           padding:12px 20px;
           background:#6c63ff;
           color:white;
           text-decoration:none;
           border-radius:10px;
           '>
           Login Admin
        </a>

        <p style='margin-top:20px;color:#777;font-size:13px;'>
            Setelah berhasil login, hapus file buat_admin.php.
        </p>

    </div>
    ";

} else {

    echo "Gagal membuat admin: " . mysqli_error($conn);

}

?>