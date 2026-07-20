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
      <!-- Pilihan Role -->
      <div class="form-group mb-4 text-center">
        <label for="role" style="display:block; font-weight:bold; margin-bottom:8px;">
          🔑 Login sebagai
        </label>
        <select name="role" id="role" class="form-control"
                style="width:50%; margin:0 auto; font-size:1.05em; padding:8px;" required>
          <option value="user">User</option>
          <option value="admin">Administrator</option>
        </select>
      </div>

      <!-- Username -->
      <div class="form-group mb-3">
        <label for="usere">👤 Username</label>
        <input type="text" name="usere" id="usere" class="form-control" placeholder="Masukkan Username" required>
      </div>

      <!-- Password -->
      <div class="form-group mb-3">
        <label for="passworde">🔒 Password</label>
        <input type="password" name="passworde" id="passworde" class="form-control" placeholder="Masukkan Password" required>
      </div>

      <!-- Captcha Tanggal Lahir (hanya untuk user) -->
      <div class="form-group mb-3" id="captchaBox">
        <label for="tgl_lahir">📅 Verifikasi Tanggal Lahir</label>
        <input type="text" name="tgl_lahir" id="tgl_lahir" class="form-control" 
               placeholder="dd-mm-yyyy" maxlength="10">
        <small class="text-muted">Format wajib: dd-mm-yyyy</small>
      </div>

      <button type="submit" name="BtnLogin" class="btn btn-primary w-100">Login</button>

      <p class="login-info mt-3 text-center">
        <span class="highlight">Silahkan login menggunakan username dan password SIMRS Khanza</span>
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
        <?php if($_GET['error']==1): ?>
          <p>⚠️ Login gagal! Username atau password salah.</p>
        <?php elseif($_GET['error']==2): ?>
          <p>⚠️ Verifikasi tanggal lahir tidak sesuai.</p>
        <?php endif; ?>
        <button onclick="closePopup()">Tutup</button>
      </div>
    </div>
    <script>
      function closePopup() {
        const popup = document.getElementById('popup');
        popup.classList.add('fade-out');
        setTimeout(() => { popup.style.display = 'none'; }, 1000);
      }
      setTimeout(closePopup, 5000);
    </script>
  <?php endif; ?>

  <!-- Script untuk autoformat tanggal lahir & sembunyikan captcha jika admin -->
  <script>
    const roleSelect = document.getElementById('role');
    const captchaBox = document.getElementById('captchaBox');
    const tglInput   = document.getElementById('tgl_lahir');

    // sembunyikan captcha jika pilih admin
    roleSelect.addEventListener('change', function() {
      if (this.value === 'admin') {
        captchaBox.style.display = 'none';
        tglInput.value = ''; // kosongkan
      } else {
        captchaBox.style.display = 'block';
      }
    });

    // autoformat tanggal lahir dd-mm-yyyy
    tglInput.addEventListener('input', function(e) {
      let val = e.target.value.replace(/\D/g, ''); // hanya angka
      if (val.length >= 8) {
        let dd = val.substring(0,2);
        let mm = val.substring(2,4);
        let yyyy = val.substring(4,8);
        e.target.value = dd + '-' + mm + '-' + yyyy;
      }
    });
  </script>
</body>
</html>
