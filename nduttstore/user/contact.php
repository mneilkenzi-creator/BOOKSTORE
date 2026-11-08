```php
<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$pesan_sukses = "";
$pesan_error = "";

$query_user = mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id = $user_id LIMIT 1"
);

$data_user = $query_user ? mysqli_fetch_assoc($query_user) : null;

$nama_default = $data_user['nama'] ?? "";
$email_default = $data_user['email'] ?? "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $isi_pesan = trim($_POST['pesan'] ?? '');

    if ($nama === '' || $email === '' || $subjek === '' || $isi_pesan === '') {
        $pesan_error = "Semua kolom wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Format email tidak valid.";

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO pesan (user_id, subjek, isi_pesan)
             VALUES (?, ?, ?)"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "iss",
                $user_id,
                $subjek,
                $isi_pesan
            );

            if (mysqli_stmt_execute($stmt)) {
                $pesan_sukses = "Pesan berhasil dikirim! Terima kasih sudah menghubungi NduttStore.";
                $_POST = [];
            } else {
                $pesan_error = "Pesan gagal dikirim. Silakan coba lagi.";
            }

            mysqli_stmt_close($stmt);

        } else {
            $pesan_error = "Terjadi kesalahan database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NduttStore</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        body {
            background: #f8f7fc;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #25213a;
        }

        .contact-hero {
            background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
            color: white;
            text-align: center;
            padding: 75px 20px;
        }

        .contact-hero h1 {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .contact-hero p {
            font-size: 16px;
            line-height: 1.7;
        }

        .contact-container {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 25px;
        }

        .contact-info,
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(50,22,95,.07);
        }

        .contact-info h2,
        .contact-form h2 {
            color: #32165f;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: flex-start;
        }

        .info-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            background: #f1e9ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .info-item h3 {
            font-size: 16px;
            color: #7041d3;
            margin-bottom: 5px;
        }

        .info-item p {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }

        .contact-form label {
            display: block;
            font-weight: 600;
            margin: 15px 0 7px;
            color: #32165f;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e5dff0;
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #7041d3;
            box-shadow: 0 0 0 3px rgba(112,65,211,.1);
        }

        .contact-form textarea {
            min-height: 140px;
            resize: vertical;
        }

        .btn-send {
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 11px;
            margin-top: 20px;
            background: linear-gradient(110deg, #7041d3, #ed4ca4);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-send:hover {
            opacity: .9;
        }

        .alert {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .success {
            background: #e9f9ef;
            color: #18743a;
        }

        .error {
            background: #fff0f0;
            color: #b42318;
        }

        .contact-footer {
            background: #321451;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 55px;
        }

        @media(max-width: 750px) {
            .contact-container {
                grid-template-columns: 1fr;
            }

            .contact-hero h1 {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<section class="contact-hero">
    <h1>Hubungi Kami</h1>
    <p>
        Ada pertanyaan, kritik, atau saran?
        Kami siap menerima pesan dari kamu.
    </p>
</section>

<main class="contact-container">

    <section class="contact-info">
        <h2>Informasi Kontak</h2>

        <div class="info-item">
            <div class="info-icon">📚</div>
            <div>
                <h3>Nama Toko</h3>
                <p>NduttStore</p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon">📧</div>
            <div>
                <h3>Email</h3>
                <p>nduttstore@gmail.com</p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon">🕒</div>
            <div>
                <h3>Jam Pelayanan</h3>
                <p>Senin - Sabtu<br>08.00 - 17.00 WIB</p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon">💬</div>
            <div>
                <h3>Layanan Pelanggan</h3>
                <p>Kirim pertanyaan atau saran melalui formulir di samping.</p>
            </div>
        </div>
    </section>

    <section class="contact-form">
        <h2>Kirim Pesan</h2>

        <?php if ($pesan_sukses !== ''): ?>
            <div class="alert success">
                <?= htmlspecialchars($pesan_sukses); ?>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error !== ''): ?>
            <div class="alert error">
                <?= htmlspecialchars($pesan_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label>Nama Lengkap</label>
            <input
                type="text"
                name="nama"
                value="<?= htmlspecialchars($_POST['nama'] ?? $nama_default); ?>"
                placeholder="Masukkan nama kamu"
                required
            >

            <label>Email</label>
            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars($_POST['email'] ?? $email_default); ?>"
                placeholder="Masukkan email kamu"
                required
            >

            <label>Subjek</label>
            <input
                type="text"
                name="subjek"
                value="<?= htmlspecialchars($_POST['subjek'] ?? ''); ?>"
                placeholder="Contoh: Pertanyaan pesanan"
                required
            >

            <label>Pesan</label>
            <textarea
                name="pesan"
                placeholder="Tuliskan pesan kamu di sini..."
                required
            ><?= htmlspecialchars($_POST['pesan'] ?? ''); ?></textarea>

            <button type="submit" class="btn-send">
                Kirim Pesan →
            </button>

        </form>
    </section>

</main>

<footer class="contact-footer">
    <h3>NduttStore.</h3>
    <p>Terima kasih telah berbelanja di NduttStore.</p>
    <p>&copy; <?= date('Y'); ?> NduttStore. All Rights Reserved.</p>
</footer>

</body>
</html>