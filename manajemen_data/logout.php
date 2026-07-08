<?php
session_start();
session_unset();     // hapus semua variabel session
session_destroy();   // hancurkan session
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Logout</title>
  <meta http-equiv="refresh" content="3;url=login.php"> 
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="logout-box">
    <h2>Anda berhasil logout ✅</h2>
    <p>Anda akan diarahkan ke halaman login dalam 3 detik...</p>
    <p><a href="login.php">Klik di sini jika tidak otomatis</a></p>
  </div>
</body>
</html>
