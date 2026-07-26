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

$currentPage = basename($_SERVER['PHP_SELF']); // ambil nama file aktif
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
    <?php if ($_SESSION['hak_akses'] === 'user' && $currentPage === 'index.php'): ?>

      <!-- Tombol UBAH PASSWORD -->
      <a href="#" 
         class="btn btn-outline-warning btn-sm px-3 py-2 shadow-sm me-2" 
         style="border-radius: 0.75rem; min-width: 140px;"
         data-bs-toggle="modal" data-bs-target="#ubahPasswordModal">
         🔑 Ubah Password
      </a>

      <!-- Tombol PENGAJUAN CUTI -->
      <a href="../manajemen_data/kepegawaian/pengajuan_cuti_user.php" 
         class="btn btn-outline-primary btn-sm px-3 py-2 shadow-sm me-2" 
         style="border-radius: 0.75rem; min-width: 120px;">
         📄 Pengajuan Cuti
      </a>  

      <!-- Tombol PROFIL PEGAWAI -->
      <a href="../manajemen_data/kepegawaian/cetak_data_pegawai.php" 
         target="_blank"
         class="btn btn-outline-primary btn-sm px-3 py-2 shadow-sm me-2" 
         style="border-radius: 0.75rem; min-width: 120px;">
         👤 Profil Pegawai
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

<!-- Modal Ubah Password -->
<div class="modal fade" id="ubahPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <!-- Tambahkan bg-dark + text-light untuk modal gelap -->
    <div class="modal-content bg-dark text-light">
      <form method="post" action="ubah_password.php">
        <div class="modal-header border-secondary">
          <h5 class="modal-title">🔑 Ubah Password</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="old_password" class="form-label">🔒 Password Lama</label>
            <input type="password" name="passworde" id="old_password" 
                   class="form-control bg-secondary text-white" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">🆕 Password Baru</label>
            <input type="password" name="new_password" id="new_password" 
                   class="form-control bg-secondary text-white" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">✅ Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" id="confirm_password" 
                   class="form-control bg-secondary text-white" required>
          </div>
        </div>
        <div class="modal-footer border-secondary">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>
