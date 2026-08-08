<?php
error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

session_start();
include __DIR__ . '/conf/conf.php';

// Ambil data setting instansi
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

$wallpaper = !empty($setting['wallpaper']) 
    ? 'data:image/jpeg;base64,' . base64_encode($setting['wallpaper']) 
    : null;
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
          <option value="">-- Pilih --</option>
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

  <!-- Info Instansi -->
  <div class="instansi-info">
    <p><?= htmlspecialchars($nama_instansi) ?></p>
    <p><?= htmlspecialchars($alamat) ?> - <?= htmlspecialchars($kabupaten) ?> - <?= htmlspecialchars($propinsi) ?></p>
    <p><?= htmlspecialchars($kontak) ?> | <?= htmlspecialchars($email) ?></p>
  </div>

  <!-- Popup error hanya untuk login gagal -->
  <?php if(isset($_GET['error']) && ($_GET['error']==1 || $_GET['error']==2)): ?>
    <div class="popup" style="display:flex;">
      <div class="popup-content" style="background:#ffe0e0; color:#2c3e50;">
        <?php if($_GET['error']==1): ?>
          <p>⚠️ Login gagal! Username atau password salah.</p>
        <?php elseif($_GET['error']==2): ?>
          <p>⚠️ Verifikasi tanggal lahir tidak sesuai.</p>
        <?php endif; ?>
        <button onclick="this.closest('.popup').style.display='none'" style="background:#c0392b; color:white;">Tutup</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Popup info sukses -->
  <?php if(isset($_GET['info']) && $_GET['info']==='PasswordBerhasilDiubahSilakanLogin'): ?>
    <div class="popup" style="display:flex;">
      <div class="popup-content" style="background:#e0ffe0; color:#2c3e50;">
        <p>✅ Password berhasil diubah. Silakan login kembali dengan password baru.</p>
        <button onclick="this.closest('.popup').style.display='none'">Tutup</button>
      </div>
    </div>
  <?php endif; ?>

  <script>
    // auto-close semua popup setelah 5 detik
    document.querySelectorAll('.popup').forEach(p => {
      setTimeout(() => { p.style.display = 'none'; }, 5000);
    });

    // Script untuk autoformat tanggal lahir & sembunyikan captcha jika admin
    const roleSelect = document.getElementById('role');
    const captchaBox = document.getElementById('captchaBox');
    const tglInput   = document.getElementById('tgl_lahir');

    roleSelect.addEventListener('change', function() {
      if (this.value === 'admin') {
        captchaBox.style.display = 'none';
        tglInput.value = '';
      } else {
        captchaBox.style.display = 'block';
      }
    });

    tglInput.addEventListener('input', function(e) {
      let val = e.target.value.replace(/\D/g, '');
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
