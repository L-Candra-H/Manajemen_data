<?php
$conn = bukakoneksi();
$qSetting = mysqli_query($conn, "SELECT * FROM setting LIMIT 1");
$setting = mysqli_fetch_assoc($qSetting);
mysqli_close($conn);

$logo          = 'data:image/png;base64,' . base64_encode($setting['logo']);
$nama_instansi = $setting['nama_instansi'];
$alamat        = $setting['alamat_instansi'];
$kabupaten     = $setting['kabupaten'];
$propinsi      = $setting['propinsi'];
$kontak        = $setting['kontak'];
$email         = $setting['email'];
?>
<header class="header d-flex align-items-center justify-content-between px-3 py-2 shadow-sm">
  <div class="d-flex align-items-center">
    <img src="<?= $logo ?>" alt="Logo" class="logo me-3">
    <div class="instansi">
      <h1 class="h5 mb-1"><?= htmlspecialchars($nama_instansi) ?></h1>
      <p class="mb-0 small">
        <?= htmlspecialchars($alamat) ?> - <?= htmlspecialchars($kabupaten) ?> - <?= htmlspecialchars($propinsi) ?>
      </p>
      <p class="mb-0 small">
        <?= htmlspecialchars($kontak) ?> | <?= htmlspecialchars($email) ?>
      </p>
    </div>
  </div>
  <!-- Tombol Logout di kanan -->
  <?php
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $parts = explode('/', trim($scriptName, '/'));
    $basePath = '/logout.php';
    if (count($parts) >= 2) {
        $basePath = '/' . $parts[0] . '/' . $parts[1] . '/logout.php';
    }
  ?>
  <a href="<?= $basePath ?>" 
     class="btn btn-outline-danger btn-sm px-3 py-2 shadow-sm" 
     style="border-radius: 0.75rem; min-width: 92px;">
     🚪 Logout
  </a>
</header>
