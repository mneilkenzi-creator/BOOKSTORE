
```php
<?php
require_once __DIR__ . "/../config/koneksi.php";

// Hapus session khusus admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_username']);

// Arahkan kembali ke login admin
header("Location: login.php");
exit;
?>
```
