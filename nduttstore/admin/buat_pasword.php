<?php

$password = "admin123";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Berhasil Dibuat</h2>";
echo "<p>Password: <b>admin123</b></p>";
echo "<p>Hash:</p>";
echo "<textarea style='width:100%;height:100px;'>$hash</textarea>";