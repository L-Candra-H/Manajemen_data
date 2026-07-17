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
<header class="header d-flex align-items-center justify-content-between px-3 py-2 shadow-sm" 
        style="border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:15px;">
  <!-- Logo + Instansi -->
  <div class="d-flex align-items-center">
    <div style="flex:0 0 100px; text-align:center;">
      <img src="<?= $logo ?>" alt="Logo" style="max-height:80px; max-width:100px;">
    </div>
    <div style="flex:1; text-align:left; margin-left:20px;">
      <h1 class="h5 mb-1"><?= htmlspecialchars($nama_instansi) ?></h1>
      <p class="mb-0 small">
        <?= htmlspecialchars($alamat) ?> - <?= htmlspecialchars($kabupaten) ?> - <?= htmlspecialchars($propinsi) ?>
      </p>
      <p class="mb-0 small">
        <?= htmlspecialchars($kontak) ?> | <?= htmlspecialchars($email) ?>
      </p>
    </div>
  </div>

  <!-- Tombol kanan -->
  <div class="d-flex align-items-center">  
    <?php if ($_SESSION['hak_akses'] === 'user'): ?>
      <!-- Tombol PROFIL PEGAWAI -->
      <a href="javascript:void(0)" 
         class="btn btn-outline-primary btn-sm px-3 py-2 shadow-sm me-2" 
         style="border-radius: 0.75rem; min-width: 92px;"
         onclick="openProfilPegawai();">
         📄 Profil Pegawai
      </a>  
    <?php endif; ?>  

    <!-- Tombol Logout -->
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
  </div>
</header>

<script>
function openProfilPegawai() {
  // buka halaman cetak di tab baru
  var w = window.open("../manajemen_data/kepegawaian/cetak_data_pegawai.php", "_blank");
  // otomatis panggil print setelah halaman selesai dimuat
  w.onload = function() {
    w.print();
  };
}
</script>
