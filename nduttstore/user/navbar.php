<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

$user_id = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : 0;

$nama_user = $_SESSION['nama'] ?? 'Pelanggan';

$total_keranjang = 0;

if ($user_id > 0) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT COALESCE(SUM(jumlah), 0) AS total
        FROM keranjang
        WHERE user_id = ?
    ");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($hasil);

        $total_keranjang = (int)($data['total'] ?? 0);

        mysqli_stmt_close($stmt);
    }
}

$halaman_sekarang = basename($_SERVER['PHP_SELF']);

function menuAktif($halaman, $daftar) {
    return in_array($halaman, $daftar) ? 'active' : '';
}
?>

<style>
* {
    box-sizing: border-box;
}

.ndutt-navbar {
    width: 100%;
    min-height: 78px;
    padding: 0 6%;
    background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: sticky;
    top: 0;
    z-index: 9999;
    box-shadow: 0 4px 18px rgba(50, 22, 95, .18);
    font-family: "Segoe UI", Arial, sans-serif;
}

.ndutt-logo {
    color: white;
    font-size: 27px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: -.8px;
}

.ndutt-logo span {
    color: #ffb5df;
}

.ndutt-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex-wrap: wrap;
}

.ndutt-menu a {
    color: rgba(255,255,255,.9);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    padding: 11px 13px;
    border-radius: 9px;
    transition: .25s;
    white-space: nowrap;
}

.ndutt-menu a:hover,
.ndutt-menu a.active {
    background: rgba(255,255,255,.2);
    color: white;
}

.ndutt-menu a.cart-link {
    background: white;
    color: #7041d3;
    position: relative;
    margin-left: 5px;
}

.ndutt-menu a.cart-link:hover {
    background: #ffe7f5;
    transform: translateY(-2px);
}

.cart-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 21px;
    height: 21px;
    padding: 0 5px;
    margin-left: 4px;
    background: #ed4ca4;
    color: white;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
}

.ndutt-menu a.logout-link {
    background: #321451;
    color: white;
    margin-left: 5px;
}

.ndutt-menu a.logout-link:hover {
    background: #241039;
}

.ndutt-user {
    color: white;
    font-size: 12px;
    white-space: nowrap;
    opacity: .9;
}

.ndutt-mobile-toggle {
    display: none;
    border: none;
    background: rgba(255,255,255,.17);
    color: white;
    font-size: 25px;
    width: 42px;
    height: 42px;
    border-radius: 9px;
    cursor: pointer;
}

@media(max-width: 1000px) {
    .ndutt-navbar {
        padding: 14px 5%;
        flex-wrap: wrap;
    }

    .ndutt-mobile-toggle {
        display: block;
    }

    .ndutt-menu {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        padding: 10px 0;
    }

    .ndutt-menu.show {
        display: flex;
    }

    .ndutt-menu a {
        width: 100%;
        padding: 13px 15px;
    }

    .ndutt-menu a.cart-link,
    .ndutt-menu a.logout-link {
        margin-left: 0;
    }

    .ndutt-user {
        display: none;
    }
}
</style>

<nav class="ndutt-navbar">

    <a href="dashboard.php" class="ndutt-logo">
        Ndu<span>tt</span>Store.
    </a>

    <button
        type="button"
        class="ndutt-mobile-toggle"
        onclick="toggleNduttMenu()"
        aria-label="Buka menu"
    >
        ☰
    </button>

    <div class="ndutt-menu" id="nduttMenu">

        <a
            href="dashboard.php"
            class="<?= menuAktif($halaman_sekarang, ['dashboard.php']) ?>"
        >
            Home
        </a>

        <a
            href="buku.php"
            class="<?= menuAktif($halaman_sekarang, ['buku.php', 'detail_buku.php']) ?>"
        >
            Buku
        </a>

        <a
            href="about.php"
            class="<?= menuAktif($halaman_sekarang, ['about.php']) ?>"
        >
            About Us
        </a>

        <a
            href="contact.php"
            class="<?= menuAktif($halaman_sekarang, ['contact.php']) ?>"
        >
            Contact
        </a>

        <a
            href="pesanan.php"
            class="<?= menuAktif($halaman_sekarang, ['pesanan.php', 'detail_pesanan.php', 'struk.php']) ?>"
        >
            Pesanan
        </a>

        <a
            href="keranjang.php"
            class="cart-link <?= menuAktif($halaman_sekarang, ['keranjang.php']) ?>"
        >
            🛒 Keranjang
            <span class="cart-count">
                <?= $total_keranjang ?>
            </span>
        </a>

        <span class="ndutt-user">
            Halo, <?= htmlspecialchars($nama_user) ?>
        </span>

        <a href="logout.php" class="logout-link">
            Logout
        </a>

    </div>

</nav>

<script>
function toggleNduttMenu() {
    const menu = document.getElementById('nduttMenu');
    menu.classList.toggle('show');
}
</script>