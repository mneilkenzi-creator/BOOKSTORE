
<?php
require_once __DIR__ . "/../config/koneksi.php";

$error = "";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['username'])
    );

    $password = $_POST['password'];

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM admin WHERE username='$username' LIMIT 1"
    );

    if (!$query) {
        die("SQL ERROR: " . mysqli_error($koneksi));
    }

    if (mysqli_num_rows($query) == 0) {

        $error = "Username admin tidak ditemukan!";

    } else {

        $admin = mysqli_fetch_assoc($query);

        $password_benar = false;

        // Cek password hash
        if (password_verify($password, $admin['password'])) {

            $password_benar = true;

        // Cek password plaintext lama
        } elseif ($password === $admin['password']) {

            $password_benar = true;

            // Ubah password plaintext menjadi hash
            $hash_baru = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            mysqli_query(
                $koneksi,
                "UPDATE admin SET password='$hash_baru'
                 WHERE id=" . (int)$admin['id']
            );
        }

        if ($password_benar) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];

            $_SESSION['admin_nama'] =
                $admin['nama'] ?? $admin['username'];

            $_SESSION['admin_username'] = $admin['username'];

            $_SESSION['admin_login'] = true;

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Password admin salah!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login Admin - NduttStore</title>

<style>

* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    min-height: 100vh;

    display: flex;
    justify-content: center;
    align-items: center;

    background:
        radial-gradient(circle at top left, #ff4ecd, transparent 35%),
        radial-gradient(circle at bottom right, #6c63ff, transparent 35%),
        linear-gradient(135deg, #17153b, #29255e);
}

.box {
    width: 400px;
    max-width: 90%;

    background: rgba(255,255,255,.97);

    padding: 40px;

    border-radius: 25px;

    box-shadow:
        0 25px 70px rgba(0,0,0,.4);
}

.logo {
    width: 70px;
    height: 70px;

    margin: 0 auto 15px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 20px;

    background: linear-gradient(
        135deg,
        #6c63ff,
        #ff4ecd
    );

    color: white;

    font-size: 30px;
    font-weight: bold;

    box-shadow: 0 10px 25px rgba(108,99,255,.35);
}

h1 {
    text-align: center;
    color: #29255e;
    margin: 0;
}

.subtitle {
    text-align: center;
    color: #888;
    margin-top: 8px;
    margin-bottom: 30px;
}

label {
    display: block;

    margin-bottom: 8px;

    font-weight: bold;

    color: #333;
}

input {
    width: 100%;

    padding: 14px;

    margin-bottom: 20px;

    border: 2px solid #eee;

    border-radius: 12px;

    outline: none;

    font-size: 15px;

    transition: .3s;
}

input:focus {
    border-color: #6c63ff;

    box-shadow:
        0 0 0 4px rgba(108,99,255,.1);
}

button {
    width: 100%;

    padding: 15px;

    border: none;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #6c63ff,
            #ff4ecd
        );

    color: white;

    font-weight: bold;

    font-size: 15px;

    cursor: pointer;

    transition: .3s;
}

button:hover {
    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(108,99,255,.35);
}

.error {
    background: #ffe8ec;

    color: #d6334c;

    padding: 13px;

    border-radius: 10px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}

.info {
    margin-top: 20px;

    padding: 14px;

    background: #f3f1ff;

    border-radius: 12px;

    text-align: center;

    font-size: 13px;

    color: #555;
}

.info b {
    color: #6c63ff;
}

</style>

</head>

<body>

<div class="box">

    <div class="logo">
        N
    </div>

    <h1>NduttStore</h1>

    <div class="subtitle">
        Login Administrator
    </div>

    <?php if ($error != "") { ?>

        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            placeholder="Masukkan username"
            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >

        <button type="submit" name="login">
            LOGIN ADMIN
        </button>

    </form>

    <div class="info">

        Akun Admin<br>

        Username:
        <b>admin</b>

        <br>

        Password:
        <b>admin123</b>

    </div>

</div>

</body>
</html>
```
