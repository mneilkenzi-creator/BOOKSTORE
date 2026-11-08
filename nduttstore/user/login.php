<?php
require_once __DIR__ . "/../config/koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $query = mysqli_query($koneksi, "
        SELECT * FROM users
        WHERE email = '$email'
        LIMIT 1
    ");

    if ($query && mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Password yang kamu masukkan salah!";
        }

    } else {
        $error = "Email belum terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - NduttStore</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg, #eeeaff, #dce9ff, #fce7f3);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 25px;
}

.container {
    width: 100%;
    max-width: 1050px;
    min-height: 620px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: white;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(58, 45, 110, 0.16);
}

/* BAGIAN KIRI */

.left {
    background: linear-gradient(145deg, #5136c9, #7960ed, #a18bff);
    color: white;
    padding: 55px 45px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.left::before {
    content: "";
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.10);
    top: -100px;
    right: -100px;
}

.left::after {
    content: "";
    position: absolute;
    width: 230px;
    height: 230px;
    border-radius: 50%;
    background: rgba(255,255,255,0.09);
    bottom: -80px;
    left: -70px;
}

.brand {
    font-size: 27px;
    font-weight: 800;
    letter-spacing: 1px;
    position: relative;
    z-index: 1;
}

.brand span {
    color: #ffe28a;
}

.welcome {
    position: relative;
    z-index: 1;
}

.welcome h1 {
    font-size: 36px;
    line-height: 1.35;
    margin-bottom: 18px;
}

.welcome p {
    font-size: 14px;
    line-height: 1.9;
    color: #eeeaff;
    max-width: 380px;
}

.book-illustration {
    text-align: center;
    font-size: 105px;
    margin: 25px 0;
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 15px 12px rgba(0,0,0,0.18));
}

.left-footer {
    font-size: 12px;
    color: #e8e2ff;
    position: relative;
    z-index: 1;
}

/* BAGIAN KANAN */

.right {
    padding: 65px 55px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right h2 {
    font-size: 30px;
    color: #29234a;
    font-weight: 800;
    margin-bottom: 8px;
}

.subtitle {
    font-size: 13px;
    color: #88849b;
    margin-bottom: 35px;
}

.error {
    background: #fff0f0;
    border-left: 4px solid #ef4444;
    color: #d12c2c;
    padding: 13px;
    border-radius: 9px;
    font-size: 12px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    font-size: 13px;
    color: #39334f;
    font-weight: 600;
    margin-bottom: 9px;
}

.form-group input {
    width: 100%;
    height: 52px;
    border: 1px solid #e4e1ef;
    border-radius: 12px;
    padding: 0 16px;
    outline: none;
    font-size: 13px;
    color: #333;
    background: #faf9ff;
    transition: 0.3s;
}

.form-group input:focus {
    border-color: #7658e8;
    background: white;
    box-shadow: 0 0 0 4px rgba(118,88,232,0.10);
}

.btn-login {
    width: 100%;
    height: 53px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(120deg, #6545d5, #9277f5);
    color: white;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(101,69,213,0.25);
    transition: 0.3s;
}

.btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(101,69,213,0.35);
}

.register {
    text-align: center;
    margin-top: 27px;
    font-size: 13px;
    color: #88849b;
}

.register a {
    color: #7253dc;
    font-weight: 700;
    text-decoration: none;
}

.register a:hover {
    text-decoration: underline;
}

.back {
    text-align: center;
    margin-top: 22px;
}

.back a {
    color: #77718c;
    font-size: 12px;
    text-decoration: none;
}

.back a:hover {
    color: #7253dc;
}

@media(max-width: 750px) {
    .container {
        grid-template-columns: 1fr;
        max-width: 450px;
    }

    .left {
        padding: 30px;
        min-height: 260px;
    }

    .welcome h1 {
        font-size: 25px;
    }

    .welcome p {
        font-size: 12px;
    }

    .book-illustration {
        display: none;
    }

    .left-footer {
        margin-top: 25px;
    }

    .right {
        padding: 40px 28px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="left">

        <div class="brand">
            Ndutt<span>Store.</span>
        </div>

        <div class="welcome">
            <h1>Temukan Dunia Baru Melalui Buku!</h1>

            <p>
                Selamat datang kembali di NduttStore.
                Masuk ke akunmu dan lanjutkan petualangan
                membaca bersama koleksi buku favoritmu.
            </p>

            <div class="book-illustration">
                📚
            </div>
        </div>

        <div class="left-footer">
            © 2026 NduttStore. Your Favorite Bookstore.
        </div>

    </div>

    <div class="right">

        <h2>Welcome Back!</h2>

        <p class="subtitle">
            Silakan login untuk melanjutkan belanja buku.
        </p>

        <?php if (!empty($error)) : ?>
            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Masukkan email kamu"
                    value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan password kamu"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                LOGIN SEKARANG →
            </button>

        </form>

        <div class="register">
            Belum punya akun?
            <a href="register.php">Daftar Sekarang</a>
        </div>

        <div class="back">
            <a href="../index.php">← Kembali ke Halaman Utama</a>
        </div>

    </div>

</div>

</body>
</html>