<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$keyword = trim($_GET['cari'] ?? '');
$kategori_id = (int)($_GET['kategori'] ?? 0);

// AMBIL SEMUA KATEGORI
$query_kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

if (!$query_kategori) {
    die("Gagal mengambil kategori: " . mysqli_error($koneksi));
}

// SIAPKAN FILTER BUKU
$where = [];

if ($keyword !== '') {
    $cari = mysqli_real_escape_string($koneksi, $keyword);
    $where[] = "b.judul LIKE '%$cari%'";
}

if ($kategori_id > 0) {
    $where[] = "b.kategori_id = $kategori_id";
}

$sql = "
    SELECT b.*, k.nama_kategori
    FROM buku b
    LEFT JOIN kategori k ON b.kategori_id = k.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY b.id DESC";

$query_buku = mysqli_query($koneksi, $sql);

if (!$query_buku) {
    die("Gagal mengambil buku: " . mysqli_error($koneksi));
}

// HITUNG JUMLAH BUKU
$total_buku = mysqli_num_rows($query_buku);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Daftar Buku - NduttStore</title>

<link rel="stylesheet" href="../assets/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    background: #f8f7fc;
    color: #25213a;
    font-family: "Segoe UI", Arial, sans-serif;
}

.buku-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 25px;
}

.buku-header {
    background: linear-gradient(110deg, #321451, #7041d3, #ed4ca4);
    color: white;
    padding: 38px;
    border-radius: 20px;
    margin-bottom: 28px;
}

.buku-header h1 {
    font-size: 30px;
    margin-bottom: 10px;
}

.buku-header p {
    opacity: .9;
    line-height: 1.6;
}

/* PENCARIAN */

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 28px;
}

.search-form input {
    flex: 1;
    padding: 14px 17px;
    border: 1px solid #e3ddeb;
    border-radius: 11px;
    outline: none;
    font-size: 15px;
    background: white;
}

.search-form input:focus {
    border-color: #7041d3;
}

.search-form button {
    border: none;
    padding: 0 25px;
    border-radius: 11px;
    background: linear-gradient(110deg, #7041d3, #ed4ca4);
    color: white;
    font-weight: 700;
    cursor: pointer;
}

/* KATEGORI */

.kategori-section {
    margin-bottom: 30px;
}

.kategori-section h2 {
    font-size: 21px;
    color: #321451;
    margin-bottom: 15px;
}

.kategori-list {
    display: flex;
    flex-wrap: wrap;
    gap: 11px;
}

.kategori-link {
    display: inline-block;
    padding: 11px 19px;
    background: white;
    border: 1px solid #e8dff1;
    border-radius: 30px;
    color: #625c70;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: .25s;
}

.kategori-link:hover {
    background: #f3eafa;
    color: #7041d3;
    border-color: #7041d3;
    transform: translateY(-2px);
}

.kategori-link.active {
    background: linear-gradient(110deg, #7041d3, #ed4ca4);
    color: white;
    border-color: transparent;
}

.kategori-link.semua {
    font-weight: 800;
}

/* JUDUL HASIL */

.hasil-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.hasil-header h2 {
    color: #321451;
    font-size: 22px;
}

.hasil-header span {
    color: #888;
    font-size: 14px;
}

/* GRID BUKU */

.buku-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 22px;
}

.buku-item {
    background: white;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(40, 20, 70, .07);
    transition: .25s;
}

.buku-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(40, 20, 70, .12);
}

.gambar-wrapper {
    width: 100%;
    height: 245px;
    background: #f0eaf7;
    overflow: hidden;
}

.buku-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.buku-detail {
    padding: 18px;
}

.label-kategori {
    display: inline-block;
    background: #f3eafa;
    color: #7041d3;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 12px;
}

.buku-detail h3 {
    color: #321451;
    font-size: 17px;
    margin-bottom: 10px;
    min-height: 42px;
    line-height: 1.4;
}

.buku-detail .harga {
    color: #ed4ca4;
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 15px;
}

.btn-detail {
    display: block;
    text-align: center;
    padding: 11px;
    border-radius: 9px;
    color: white;
    background: linear-gradient(110deg, #7041d3, #ed4ca4);
    text-decoration: none;
    font-weight: 600;
    transition: .2s;
}

.btn-detail:hover {
    opacity: .9;
}

/* BUKU KOSONG */

.buku-kosong {
    background: white;
    padding: 45px 25px;
    border-radius: 15px;
    text-align: center;
    color: #777;
    grid-column: 1 / -1;
}

.buku-kosong h3 {
    color: #321451;
    margin-bottom: 10px;
}

.btn-reset {
    display: inline-block;
    margin-top: 18px;
    background: #7041d3;
    color: white;
    text-decoration: none;
    padding: 11px 20px;
    border-radius: 9px;
}

/* RESPONSIVE */

@media(max-width: 900px) {
    .buku-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media(max-width: 700px) {
    .buku-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .buku-header {
        padding: 27px;
    }

    .buku-header h1 {
        font-size: 25px;
    }
}

@media(max-width: 480px) {
    .buku-container {
        padding: 0 15px;
    }

    .buku-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .gambar-wrapper {
        height: 175px;
    }

    .buku-detail {
        padding: 12px;
    }

    .buku-detail h3 {
        font-size: 14px;
    }

    .buku-detail .harga {
        font-size: 15px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-form button {
        padding: 13px;
    }

    .hasil-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . "/navbar.php"; ?>

<main class="buku-container">

    <section class="buku-header">
        <h1>Koleksi Buku NduttStore 📚</h1>
        <p>
            Temukan buku favoritmu dari berbagai kategori
            dan nikmati pengalaman belanja yang mudah.
        </p>
    </section>

    <!-- FORM PENCARIAN -->
    <form method="GET" class="search-form">

        <input
            type="text"
            name="cari"
            placeholder="Cari judul buku..."
            value="<?= htmlspecialchars($keyword); ?>"
        >

        <?php if ($kategori_id > 0): ?>
            <input
                type="hidden"
                name="kategori"
                value="<?= $kategori_id; ?>"
            >
        <?php endif; ?>

        <button type="submit">Cari Buku 🔍</button>

    </form>

    <!-- FILTER KATEGORI -->
    <section class="kategori-section">

        <h2>Jelajahi Kategori 📖</h2>

        <div class="kategori-list">

            <a
                href="buku.php<?= $keyword !== '' ? '?cari=' . urlencode($keyword) : ''; ?>"
                class="kategori-link semua <?= $kategori_id === 0 ? 'active' : ''; ?>"
            >
                Semua Buku
            </a>

            <?php while ($kategori = mysqli_fetch_assoc($query_kategori)): ?>

                <a
                    href="buku.php?kategori=<?= (int)$kategori['id']; ?><?= $keyword !== '' ? '&cari=' . urlencode($keyword) : ''; ?>"
                    class="kategori-link <?= $kategori_id === (int)$kategori['id'] ? 'active' : ''; ?>"
                >
                    <?= htmlspecialchars($kategori['nama_kategori']); ?>
                </a>

            <?php endwhile; ?>

        </div>

    </section>

    <!-- HASIL BUKU -->
    <div class="hasil-header">

        <h2>
            <?php
            if ($kategori_id > 0) {
                $nama_kategori = mysqli_query(
                    $koneksi,
                    "SELECT nama_kategori FROM kategori WHERE id = $kategori_id LIMIT 1"
                );

                $kategori_terpilih = mysqli_fetch_assoc($nama_kategori);

                echo $kategori_terpilih
                    ? htmlspecialchars($kategori_terpilih['nama_kategori'])
                    : "Koleksi Buku";
            } else {
                echo "Semua Koleksi Buku";
            }
            ?>
        </h2>

        <span>
            <?= $total_buku; ?> buku ditemukan
        </span>

    </div>

    <section class="buku-grid">

        <?php if ($total_buku > 0): ?>

            <?php while ($buku = mysqli_fetch_assoc($query_buku)): ?>

                <?php
                $id_buku = (int)$buku['id'];
                $judul = $buku['judul'] ?? 'Buku tanpa judul';
                $harga = (int)($buku['harga'] ?? 0);
                $gambar = $buku['gambar'] ?? '';
                $nama_kategori = $buku['nama_kategori'] ?? 'Umum';

                $gambar_path = !empty($gambar)
                    ? "../assets/img/" . $gambar
                    : "../assets/img/default-book.png";
                ?>

                <article class="buku-item">

                    <div class="gambar-wrapper">

                        <img
                            src="<?= htmlspecialchars($gambar_path); ?>"
                            alt="<?= htmlspecialchars($judul); ?>"
                            onerror="this.onerror=null; this.src='https://placehold.co/400x500?text=NduttStore';"
                        >

                    </div>

                    <div class="buku-detail">

                        <span class="label-kategori">
                            <?= htmlspecialchars($nama_kategori); ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($judul); ?>
                        </h3>

                        <div class="harga">
                            Rp <?= number_format($harga, 0, ',', '.'); ?>
                        </div>

                        <a
                            href="detail_buku.php?id=<?= $id_buku; ?>"
                            class="btn-detail"
                        >
                            Lihat Detail
                        </a>

                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="buku-kosong">

                <h3>Buku tidak ditemukan 😥</h3>

                <p>
                    Coba gunakan kata kunci atau kategori yang lain.
                </p>

                <a href="buku.php" class="btn-reset">
                    Lihat Semua Buku
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>