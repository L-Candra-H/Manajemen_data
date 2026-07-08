<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include __DIR__ . '/conf/conf.php';

$q = mysqli_query($conn, "SELECT * FROM setting LIMIT 1");
if (!$q) {
    die("Query gagal: " . mysqli_error($conn));
}
$setting = mysqli_fetch_assoc($q);
if (!$setting) {
    die("Data setting tidak ditemukan");
}

$nama_instansi = $setting['nama_instansi'];
$alamat        = $setting['alamat_instansi'];
$kabupaten     = $setting['kabupaten'];
$propinsi      = $setting['propinsi'];
$kontak        = $setting['kontak'];
$email         = $setting['email'];

$wallpaper = null;
if (!empty($setting['wallpaper'])) {
    $wallpaper = 'data:image/jpeg;base64,' . base64_encode($setting['wallpaper']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Manajemen Data</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body <?php if($wallpaper){ ?>style="background-image:url('<?= $wallpaper ?>');"<?php } ?>>
  <div class="login-box">
    <form method="post" action="cek_login.php">
      <input type="text" name="usere" placeholder="Username" required>
      <input type="password" name="passworde" placeholder="Password" required>
      <button type="submit" name="BtnLogin">Login</button>
      <p class="login-info">
        <span class="highlight">Silahkan login menggunakan
        username dan password SIMRS Khanza</span>
      </p>
    </form>
  </div>

  <div class="instansi-info">
    <p><?= htmlspecialchars($nama_instansi) ?></p>
    <p><?= htmlspecialchars($alamat) ?> - <?= htmlspecialchars($kabupaten) ?> - <?= htmlspecialchars($propinsi) ?></p>
    <p><?= htmlspecialchars($kontak) ?> | <?= htmlspecialchars($email) ?></p>
  </div>

  <!-- Popup error -->
  <?php if(isset($_GET['error'])): ?>
  <div id="popup" class="popup" style="display:flex;">
    <div class="popup-content">
      <p>⚠️ Login gagal! Username atau password salah.</p>
      <button onclick="closePopup()">Tutup</button>
    </div>
  </div>
  <script>
    function closePopup() {
      const popup = document.getElementById('popup');
      popup.classList.add('fade-out'); // mulai animasi fade-out
      setTimeout(() => {
        popup.style.display = 'none';  // sembunyikan setelah animasi selesai
      }, 1000); // sesuai durasi transition di CSS
    }

    // auto-close setelah 5 detik
    setTimeout(closePopup, 5000);
  </script>
  <?php endif; ?>
</body>
</html>
