<?php
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $koneksi->prepare("
    SELECT *
    FROM pesanan
    WHERE user_id = ?
    ORDER BY id DESC
");

if (!$stmt) {
    die("Query pesanan gagal: " . $koneksi->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

function statusPembayaran($status) {
    $status = strtolower(trim((string)$status));

    if ($status === 'berhasil') {
        return ['Berhasil', 'success'];
    }

    if ($status === 'ditolak') {
        return ['Ditolak', 'danger'];
    }

    if ($status === 'belum dibayar') {
        return ['Belum Dibayar', 'secondary'];
    }

    return ['Menunggu Verifikasi', 'warning'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - NduttStore</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f8f7fc;
            color: #25213a;
        }

        .container {
            width: 88%;
            max-width: 1200px;
            margin: 45px auto;
            min-height: 70vh;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 30px;
            color: #32165f;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #777187;
            font-size: 14px;
        }

        .order-card {
            background: white;
            border-radius: 17px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 5px 22px rgba(40, 20, 70, .06);
            border: 1px solid #eeeaf5;
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            padding-bottom: 18px;
            border-bottom: 1px solid #eeeaf5;
        }

        .order-number {
            font-size: 17px;
            font-weight: 700;
            color: #32165f;
        }

        .order-date {
            font-size: 13px;
            color: #898397;
            margin-top: 5px;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }

        .success {
            background: #e4f8eb;
            color: #168342;
        }

        .warning {
            background: #fff4d9;
            color: #a66b00;
        }

        .danger {
            background: #ffe5e5;
            color: #c62828;
        }

        .secondary {
            background: #eeeaf5;
            color: #625c70;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            padding: 22px 0;
        }

        .info-label {
            font-size: 12px;
            color: #898397;
            margin-bottom: 7px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #302942;
            overflow-wrap: anywhere;
        }

        .payment-box {
            background: #faf8ff;
            border: 1px solid #eee8f8;
            border-radius: 12px;
            padding: 17px;
            margin-bottom: 18px;
        }

        .payment-box h3 {
            font-size: 15px;
            color: #32165f;
            margin-bottom: 12px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 9px;
            font-size: 13px;
        }

        .payment-row:last-child {
            margin-bottom: 0;
        }

        .payment-row span:first-child {
            color: #777187;
        }

        .payment-row strong {
            text-align: right;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: .2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #32165f, #8151d8);
            color: white;
        }

        .btn-primary:hover {
            opacity: .88;
            transform: translateY(-2px);
        }

        .btn-pink {
            background: #ed4ca4;
            color: white;
        }

        .btn-pink:hover {
            background: #d9348c;
        }

        .empty {
            background: white;
            padding: 60px 20px;
            border-radius: 17px;
            text-align: center;
            box-shadow: 0 5px 22px rgba(40, 20, 70, .05);
        }

        .empty h2 {
            color: #32165f;
            margin-bottom: 10px;
        }

        .empty p {
            color: #898397;
            margin-bottom: 20px;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        @media(max-width: 700px) {
            .container {
                width: 93%;
                margin: 28px auto;
            }

            .order-info {
                grid-template-columns: 1fr 1fr;
            }

            .order-card {
                padding: 18px;
            }

            .page-header h1 {
                font-size: 25px;
            }
        }

        @media(max-width: 420px) {
            .order-info {
                grid-template-columns: 1fr;
            }

            .order-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .payment-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .payment-row strong {
                text-align: left;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<div class="container">

    <div class="page-header">
        <h1>Pesanan Saya</h1>
        <p>Pantau pesanan dan status pembayaran buku kamu di NduttStore.</p>
    </div>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($pesanan = $result->fetch_assoc()): ?>

            <?php
                $id_pesanan = (int)$pesanan['id'];

                $nomor = !empty($pesanan['nomor_pesanan'])
                    ? $pesanan['nomor_pesanan']
                    : 'ORD-' . $id_pesanan;

                $tanggal = !empty($pesanan['tanggal_pesanan'])
                    ? date('d-m-Y H:i', strtotime($pesanan['tanggal_pesanan']))
                    : '-';

                $metode = $pesanan['metode_pembayaran'] ?? '-';
                $status_bayar = $pesanan['status_pembayaran'] ?? 'Menunggu Verifikasi';
                $status_order = $pesanan['status'] ?? 'Menunggu';

                $badge = statusPembayaran($status_bayar);
                $status_bayar_lower = strtolower(trim($status_bayar));
                $metode_lower = strtolower(trim($metode));

                $perlu_bukti = (
                    strpos($metode_lower, 'qris') !== false ||
                    strpos($metode_lower, 'bank') !== false ||
                    strpos($metode_lower, 'transfer') !== false
                );

                $boleh_upload = (
                    $perlu_bukti &&
                    in_array($status_bayar_lower, [
                        'menunggu verifikasi',
                        'ditolak'
                    ])
                );
            ?>

            <div class="order-card">

                <div class="order-top">
                    <div>
                        <div class="order-number">
                            Pesanan #<?= htmlspecialchars($nomor); ?>
                        </div>

                        <div class="order-date">
                            <?= htmlspecialchars($tanggal); ?>
                        </div>
                    </div>

                    <span class="status <?= $badge[1]; ?>">
                        <?= htmlspecialchars($badge[0]); ?>
                    </span>
                </div>

                <div class="order-info">

                    <div>
                        <div class="info-label">Nama Penerima</div>
                        <div class="info-value">
                            <?= htmlspecialchars($pesanan['nama_penerima'] ?? '-'); ?>
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Metode Pembayaran</div>
                        <div class="info-value">
                            <?= htmlspecialchars($metode); ?>
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Status Pesanan</div>
                        <div class="info-value">
                            <?= htmlspecialchars($status_order); ?>
                        </div>
                    </div>

                </div>

                <div class="payment-box">
                    <h3>Informasi Pembayaran</h3>

                    <div class="payment-row">
                        <span>Total Pembayaran</span>
                        <strong>
                            <?= rupiah($pesanan['total_harga'] ?? 0); ?>
                        </strong>
                    </div>

                    <div class="payment-row">
                        <span>Status Pembayaran</span>
                        <strong><?= htmlspecialchars($badge[0]); ?></strong>
                    </div>

                    <?php if ($status_bayar_lower === 'ditolak'): ?>
                        <div style="color:#c62828;font-size:13px;margin-top:12px;">
                            Bukti pembayaran ditolak. Silakan unggah ulang bukti pembayaran.
                        </div>
                    <?php elseif ($status_bayar_lower === 'menunggu verifikasi'): ?>
                        <div style="color:#a66b00;font-size:13px;margin-top:12px;">
                            Bukti pembayaran sedang diperiksa oleh admin.
                        </div>
                    <?php elseif ($status_bayar_lower === 'berhasil'): ?>
                        <div style="color:#168342;font-size:13px;margin-top:12px;">
                            Pembayaran sudah berhasil diverifikasi oleh admin.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-actions">

                    <?php if ($boleh_upload): ?>
                        <a
                            href="upload_bukti.php?id=<?= $id_pesanan; ?>"
                            class="btn btn-pink"
                        >
                            <?= $status_bayar_lower === 'ditolak'
                                ? 'Upload Ulang Bukti'
                                : 'Upload Bukti Pembayaran'; ?>
                        </a>
                    <?php endif; ?>

                    <a
                        href="struk.php?id=<?= $id_pesanan; ?>"
                        class="btn btn-primary"
                    >
                        Lihat Struk Pesanan
                    </a>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <div class="empty-icon">📚</div>
            <h2>Belum Ada Pesanan</h2>
            <p>Kamu belum melakukan pemesanan buku.</p>

            <a href="buku.php" class="btn btn-primary">
                Mulai Belanja
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>