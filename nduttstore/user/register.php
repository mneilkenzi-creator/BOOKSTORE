<?php
session_start();

require_once __DIR__ . "/../config/koneksi.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $nama = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['nama'] ?? '')
    );

    $email = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if (
        empty($nama) ||
        empty($email) ||
        empty($password) ||
        empty($konfirmasi_password)
    ) {

        $error = "Semua kolom wajib diisi!";

    } elseif ($password !== $konfirmasi_password) {

        $error = "Konfirmasi password tidak cocok!";

    } else {

        $cek_email = mysqli_query(
            $koneksi,
            "SELECT * FROM users WHERE email='$email' LIMIT 1"
        );

        if (mysqli_num_rows($cek_email) > 0) {

            $error = "Email sudah terdaftar!";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $password_hash = mysqli_real_escape_string(
                $koneksi,
                $password_hash
            );

            $query = mysqli_query(
                $koneksi,
                "INSERT INTO users (nama, email, password)
                 VALUES ('$nama', '$email', '$password_hash')"
            );

            if ($query) {

                $success = "Registrasi berhasil! Silakan login.";

            } else {

                $error = "Registrasi gagal: " . mysqli_error($koneksi);

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - NduttStore</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
            background: linear-gradient(
                135deg,
                #6c3cff,
                #a855f7,
                #ec4899
            );
        }

        .register-container {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            font-size: 35px;
            margin-bottom: 10px;
        }

        h2 {
            text-align: center;
            color: #6c3cff;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            outline: none;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(
                135deg,
                #6c3cff,
                #ec4899
            );
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108,60,255,0.3);
        }

        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="register-container">

    <div class="logo">📚</div>

    <h2>Daftar NduttStore</h2>

    <p class="subtitle">
        Buat akun dan mulai belanja buku favoritmu!
    </p>

    <?php if (!empty($error)) : ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)) : ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="login.php">Login sekarang</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input
                type="text"
                name="nama"
                placeholder="Masukkan nama lengkap"
                required
            >
        </div>

        <div class="form-group">
            <label>Email</label>
            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                required
            >
        </div>

        <div class="form-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Buat password"
                required
            >
        </div>

        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input
                type="password"
                name="konfirmasi_password"
                placeholder="Ulangi password"
                required
            >
        </div>

        <button
            type="submit"
            name="register"
            class="btn-register"
        >
            Daftar Sekarang
        </button>

    </form>

    <div class="login-link">
        Sudah punya akun?
        <a href="login.php">Login di sini</a>
    </div>

</div>

</body>
</html>