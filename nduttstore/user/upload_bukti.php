```php
<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$pesanan_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pesanan_id <= 0) {
    header("Location: pesanan.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM pesanan WHERE id = ? AND user_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $pesanan_id, $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: pesanan.php");
    exit;
}

$pesan_error = "";
$pesan_sukses = "";

$metode = strtoupper(trim($pesanan['metode_pembayaran'] ?? ''));
$status_pembayaran = $pesanan['status_pembayaran'] ?? 'Menunggu Verifikasi';

if (isset($_GET['sukses'])) {
    $pesan_sukses = "Bukti pembayaran berhasil diunggah. Silakan tunggu verifikasi admin.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($status_pembayaran === 'Berhasil') {
        $pesan_error = "Pembayaran pesanan ini sudah berhasil diverifikasi.";
    } elseif (!in_array($metode, ['QRIS', 'BANK', 'TRANSFER BANK'], true)) {
        $pesan_error = "Pesanan ini tidak menggunakan metode pembayaran QRIS atau transfer bank.";
    } elseif (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $pesan_error = "Pilih file bukti pembayaran terlebih dahulu.";
    } else {

        $file = $_FILES['bukti'];

        if ($file['size'] > 2 * 1024 * 1024) {
            $pesan_error = "Ukuran file maksimal 2 MB.";
        } else {

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $format_diizinkan = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($format_diizinkan[$mime])) {
                $pesan_error = "Format file harus JPG, PNG, atau WEBP.";
            } else {

                $folder = __DIR__ . "/../assets/bukti_pembayaran/";

                if (!is_dir($folder)) {
                    mkdir($folder, 0755, true);
                }

                $nama_file = "bukti_" . bin2hex(random_bytes(16)) . "." . $format_diizinkan[$mime];
                $tujuan_file = $folder . $nama_file;

                if (move_uploaded_file($file['tmp_name'], $tujuan_file)) {

                    $path_database = "bukti_pembayaran/" . $nama_file;

                    $update = mysqli_prepare(
                        $koneksi,
                        "UPDATE pesanan
                         SET bukti_pembayaran = ?,
                             status_pembayaran = 'Menunggu Verifikasi',
                             tanggal_verifikasi = NULL
                         WHERE id = ? AND user_id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $update,
                        "sii",
                        $path_database,
                        $pesanan_id,
                        $user_id
                    );

                    if (mysqli_stmt_execute($update)) {
                        mysqli_stmt_close($update);

                        header("Location: upload_bukti.php?id=" . $pesanan_id . "&sukses=1");
                        exit;
                    } else {
                        mysqli_stmt_close($update);
                        $pesan_error = "Gagal menyimpan data bukti ke database.";
                    }

                } else {
                    $pesan_error = "File gagal diunggah. Periksa folder penyimpanan.";
                }
            }
        }
    }
}

$nomor_pesanan = !empty($pesanan['nomor_pesanan'])
    ? $pesanan['nomor_pesanan']
    : "ORD-" . $pesanan['id'];

$total_harga = (float) ($pesanan['total_harga'] ?? 0);

$file_lama = $pesanan['bukti_pembayaran'] ?? '';
$lokasi_bukti = __DIR__ . "/../assets/" . ltrim($file_lama, "/");
$bukti_tersedia = !empty($file_lama) && file_exists($lokasi_bukti);

$boleh_upload = (
    $status_pembayaran !== 'Berhasil'
    && in_array($metode, ['QRIS', 'BANK', 'TRANSFER BANK'], true)
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Pembayaran - NduttStore</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8f7fc;
            color: #25213a;
        }

        .container {
            width: 92%;
            max-width: 850px;
            margin: 40px auto;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(40, 20, 70, 0.07);
            margin-bottom: 22px;
        }

        h2 {
            color: #32165f;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #777;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .detail-item {
            padding: 15px;
            background: #faf8ff;
            border-radius: 12px;
        }

        .detail-item small {
            display: block;
            color: #888;
            margin-bottom: 7px;
        }

        .detail-item strong {
            color: #32165f;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 20px;
            background: #fff2d8;
            color: #996300;
            font-size: 13px;
            font-weight: bold;
        }

        .status.berhasil {
            background: #dcfce7;
            color: #15803d;
        }

        .status.ditolak {
            background: #fee2e2;
            color: #b91c1c;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .upload-area {
            border: 2px dashed #d8c8ef;
            border-radius: 14px;
            padding: 25px;
            text-align: center;
            background: #fcfaff;
            margin: 18px 0;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border: 1px solid #ddd;
            border-radius: 9px;
            background: white;
        }

        .preview {
            max-width: 100%;
            max-height: 350px;
            object-fit: contain;
            display: block;
            margin: 15px auto;
            border-radius: 12px;
        }

        .btn {
            display: inline-block;
            border: none;
            padding: 13px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #32165f, #8151d8);
            color: white;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-back {
            background: #eee8f7;
            color: #32165f;
            margin-left: 7px;
        }

        .note {
            color: #777;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 12px;
        }

        @media (max-width: 600px) {
            .detail {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
            }

            .btn {
                display: block;
                text-align: center;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>

<?php
if (file_exists(__DIR__ . "/navbar.php")) {
    include __DIR__ . "/navbar.php";
}
?>

<div class="container">

    <div class="card">
        <h2>Upload Bukti Pembayaran</h2>
        <p class="subtitle">
            Unggah bukti pembayaran pesanan kamu agar dapat diperiksa oleh admin NduttStore.
        </p>

        <?php if ($pesan_sukses): ?>
            <div class="alert success">
                <?= htmlspecialchars($pesan_sukses) ?>
            </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
            <div class="alert error">
                <?= htmlspecialchars($pesan_error) ?>
            </div>
        <?php endif; ?>

        <div class="detail">

            <div class="detail-item">
                <small>Nomor Pesanan</small>
                <strong><?= htmlspecialchars($nomor_pesanan) ?></strong>
            </div>

            <div class="detail-item">
                <small>Total Pembayaran</small>
                <strong>Rp <?= number_format($total_harga, 0, ',', '.') ?></strong>
            </div>

            <div class="detail-item">
                <small>Metode Pembayaran</small>
                <strong><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></strong>
            </div>

            <div class="detail-item">
                <small>Status Pembayaran</small>
                <span class="status <?= $status_pembayaran === 'Berhasil' ? 'berhasil' : ($status_pembayaran === 'Ditolak' ? 'ditolak' : '') ?>">
                    <?= htmlspecialchars($status_pembayaran) ?>
                </span>
            </div>

        </div>
    </div>

    <div class="card">

        <?php if ($bukti_tersedia): ?>

            <h2>Bukti Pembayaran Saat Ini</h2>
            <img
                class="preview"
                src="../assets/<?= htmlspecialchars(ltrim($file_lama, '/')) ?>"
                alt="Bukti pembayaran"
            >

        <?php endif; ?>

        <?php if ($boleh_upload): ?>

            <h2>
                <?= $bukti_tersedia ? 'Ganti Bukti Pembayaran' : 'Pilih Bukti Pembayaran' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data">

                <div class="upload-area">
                    <p>Pilih foto atau gambar bukti transfer kamu</p>

                    <input
                        type="file"
                        name="bukti"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >

                    <p class="note">
                        Format JPG, PNG, atau WEBP. Maksimal ukuran file 2 MB.
                    </p>
                </div>

                <button type="submit" class="btn">
                    Upload Bukti Pembayaran
                </button>

                <a href="pesanan.php" class="btn btn-back">
                    Kembali ke Pesanan
                </a>

            </form>

        <?php elseif ($status_pembayaran === 'Berhasil'): ?>

            <div class="alert success">
                Pembayaran pesanan ini sudah berhasil diverifikasi oleh admin.
            </div>

            <a href="pesanan.php" class="btn">Kembali ke Pesanan</a>

        <?php else: ?>

            <div class="alert error">
                Upload bukti hanya tersedia untuk pembayaran QRIS atau transfer bank.
            </div>

            <a href="pesanan.php" class="btn">Kembali ke Pesanan</a>

        <?php endif; ?>

    </div>

</div>

</body>
</html>