<?php
session_start();
include __DIR__ . '/conf/conf.php';
include __DIR__ . '/conf/auth.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

// Flag modul
$modulK3 = (!empty($_SESSION["jenis_cidera_k3rs"]) || !empty($_SESSION["penyebab_k3rs"]) || !empty($_SESSION["jenis_luka_k3rs"]) 
           || !empty($_SESSION["lokasi_kejadian_k3rs"]) || !empty($_SESSION["dampak_cidera_k3rs"]) || !empty($_SESSION["jenis_pekerjaan_k3rs"])
           || !empty($_SESSION["bagian_tubuh_k3rs"]) || !empty($_SESSION["peristiwa_k3rs"]) || !empty($_SESSION["jenis_cidera_k3rstahun"]) 
           || !empty($_SESSION["penyebab_k3rstahun"]) || !empty($_SESSION["jenis_luka_k3rstahun"]) || !empty($_SESSION["lokasi_kejadian_k3rstahun"]) 
           || !empty($_SESSION["dampak_cidera_k3rstahun"]) || !empty($_SESSION["jenis_pekerjaan_k3rstahun"]) || !empty($_SESSION["bagian_tubuh_k3rstahun"]) 
           || $_SESSION["hak_akses"]==="administrator");

$modulAudit = (!empty($_SESSION["audit_kepatuhan_apd"]) || !empty($_SESSION["audit_cuci_tangan_medis"])
           || !empty($_SESSION["audit_pembuangan_limbah"]) || !empty($_SESSION["audit_pembuangan_benda_tajam"]) || !empty($_SESSION["audit_penanganan_darah"])
           || !empty($_SESSION["audit_pengelolaan_linen_kotor"]) || !empty($_SESSION["audit_penempatan_pasien"]) || !empty($_SESSION["audit_kamar_jenazah"])
           || !empty($_SESSION["audit_bundle_iadp"]) || !empty($_SESSION["audit_bundle_ido"]) || !empty($_SESSION["audit_fasilitas_kebersihan_tangan"])
           || !empty($_SESSION["audit_fasilitas_apdp"]) || !empty($_SESSION["audit_pembuangan_limbah_cair_infeksius"]) || !empty($_SESSION["audit_sterilisasi_alat"])
           || !empty($_SESSION["audit_bundle_isk"]) || !empty($_SESSION["audit_bundle_plabsi"]) || !empty($_SESSION["audit_bundle_vap"]) 
           || $_SESSION["hak_akses"]==="administrator");

$modulSKP = (!empty($_SESSION["skp_kategori_penilaian"]) || !empty($_SESSION["skp_kriteria_penilaian"]) 
           || !empty($_SESSION["skp_penilaian"]) || !empty($_SESSION["skp_rekapitulasi_penilaian"]) 
           || $_SESSION["hak_akses"]==="administrator");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Penilaian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/index.css">
  <link rel="stylesheet" href="layout/header.css">
</head>
<body>
  <?php include __DIR__ . '/layout/header.php'; ?>

  <main class="main-content container-fluid mt-2 menu-penilaian"> 
    <div class="row justify-content-center">

      <!-- Modul K3 -->
      <?php if ($modulK3): ?>
        <div class="col-md-4 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Keselamatan & Kesehatan Kerja (K3)</h5>
              <p class="text-muted mb-0 small">Pengelolaan Data Keselamatan & Kesehatan Kerja (K3)</p>
              <?php if (!empty($_SESSION["jenis_cidera_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_cidera_k3.php" class="btn btn-primary btn-sm mt-3">Jenis Cidera K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["penyebab_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/penyebab_kecelakaan_k3.php" class="btn btn-primary btn-sm mt-3">Penyebab Kecelakaan K3</a>
               <?php endif; ?>

              <?php if (!empty($_SESSION["jenis_luka_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_luka_k3.php" class="btn btn-primary btn-sm mt-3">Jenis Luka K3</a>
               <?php endif; ?>

              <?php if (!empty($_SESSION["lokasi_kejadian_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/lokasi_kejadian_k3.php" class="btn btn-primary btn-sm mt-3">Lokasi Kejadian K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["dampak_cidera_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/dampak_cidera_k3.php" class="btn btn-primary btn-sm mt-3">Dampak Cidera K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["jenis_pekerjaan_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_pekerjaan_k3.php" class="btn btn-primary btn-sm mt-3">Jenis Pekerjaan K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["bagian_tubuh_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/bagian_tubuh_k3.php" class="btn btn-primary btn-sm mt-3">Bagian Tubuh K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["peristiwa_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/peristiwa_k3.php" class="btn btn-primary btn-sm mt-3">Peristiwa K3</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["jenis_cidera_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_cidera_k3_tahun.php" class="btn btn-primary btn-sm mt-3">Jenis Cidera K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["penyebab_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/penyebab_kecelakaan_tahun.php" class="btn btn-primary btn-sm mt-3">Penyebab Kecelakaan K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["jenis_luka_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_luka_tahun.php" class="btn btn-primary btn-sm mt-3">Jenis Luka K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["lokasi_kejadian_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/lokasi_kejadian_tahun.php" class="btn btn-primary btn-sm mt-3">Lokasi Kejadian K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["dampak_cidera_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/dampak_cidera_tahun.php" class="btn btn-primary btn-sm mt-3">Dampak Cidera K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["jenis_pekerjaan_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/jenis_pekerjaan_tahun.php" class="btn btn-primary btn-sm mt-3">Jenis Pekerjaan K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["bagian_tubuh_k3rstahun"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/bagian_tubuh_tahun.php" class="btn btn-primary btn-sm mt-3">Bagian Tubuh K3 Per Tahun</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["peristiwa_k3rs"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/k3/grafik_k3.php" class="btn btn-outline-primary btn-sm mt-3">Grafik Keselamatan & Kesehatan Kerja (K3)</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Modul Audit -->
      <?php if ($modulAudit): ?>
        <div class="col-md-4 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Audit</h5>
              <p class="text-muted mb-0 small">Pengelolaan Data Audit</p>
              <?php if (!empty($_SESSION["audit_kepatuhan_apd"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_kepatuhan.php" class="btn btn-primary btn-sm mt-3">Audit Kepatuhan APD</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_cuci_tangan_medis"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_cuci_tangan.php" class="btn btn-primary btn-sm mt-3">Audit Cuci Tangan Medis</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["ruang_audit_kepatuhan"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/ruang_audit_kepatuhan.php" class="btn btn-primary btn-sm mt-3">Ruang/Unit Audit Kepatuhan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_pembuangan_limbah"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_pembuangan_limbah.php" class="btn btn-primary btn-sm mt-3">Audit Pembuangan Limbah</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_pembuangan_benda_tajam"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_pembuangan_benda_tajam.php" class="btn btn-primary btn-sm mt-3">Audit Pembuangan Benda Tajam & Jarum</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_penanganan_darah"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_penanganan_darah.php" class="btn btn-primary btn-sm mt-3">Audit Penanganan Darah</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_pengelolaan_linen_kotor"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_pengelolaan_linen_kotor.php" class="btn btn-primary btn-sm mt-3">Audit Pengelolaan Linen Kotor</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_penempatan_pasien"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_penempatan_pasien.php" class="btn btn-primary btn-sm mt-3">Audit Penempatan Pasien</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_kamar_jenazah"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_kamar_jenazah.php" class="btn btn-primary btn-sm mt-3">Audit Kamar Jenazah</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_bundle_iadp"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_bundle_iadp.php" class="btn btn-primary btn-sm mt-3">Audit Bundle IADP</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_bundle_ido"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_bundle_ido.php" class="btn btn-primary btn-sm mt-3">Audit Bundle IDO</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_fasilitas_kebersihan_tangan"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_fasilitas_kebersihan_tangan.php" class="btn btn-primary btn-sm mt-3">Audit Fasilitas Kebersihan Tangan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_fasilitas_apd"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_fasilitas_apd.php" class="btn btn-primary btn-sm mt-3">Audit Fasilitas APD</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_pembuangan_limbah_cair_infeksius"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_pembuangan_limbah_cair_infeksius.php" class="btn btn-primary btn-sm mt-3">Audit Pembuangan Limbah Cair Infeksius</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_sterilisasi_alat"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_sterilisasi_alat.php" class="btn btn-primary btn-sm mt-3">Audit Sterilisasi Alat</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_bundle_isk"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_bundle_isk.php" class="btn btn-primary btn-sm mt-3">Audit Bundle ISK</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_bundle_plabsi"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_bundle_plabsi.php" class="btn btn-primary btn-sm mt-3">Audit Bundle PLABSI</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["audit_bundle_vap"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/audit/audit_bundle_vap.php" class="btn btn-primary btn-sm mt-3">Audit Bundle VAP</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Modul SKP -->
      <?php if ($modulSKP): ?>
        <div class="col-md-4 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Sasaran Keselamatan Pasien (SKP)</h5>
              <p class="text-muted mb-0 small">Pengelolaan Data Sasaran Keselamatan Pasien (SK)</p>
              <?php if (!empty($_SESSION["skp_kategori_penilaian"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/skp/kategori_penilaian.php" class="btn btn-primary btn-sm mt-3">Kategori Pengkajian SKP</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["skp_kriteria_penilaian"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/skp/kriteria_penilaian.php" class="btn btn-primary btn-sm mt-3">Kriteria Pengkajian SKP</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["skp_penilaian"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/skp/penilaian.php" class="btn btn-primary btn-sm mt-3">Pengkajian SKP Petugas/Dokter</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["skp_rekapitulasi_penilaian"]) || $_SESSION["hak_akses"]==="administrator"): ?>
                <a href="penilaian/skp/rekapitulasi_penilaian.php" class="btn btn-primary btn-sm mt-3">Rekapitulasi Pengkajian SKP</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Card fallback -->
  <?php if (!$modulK3 && !$modulAudit && !$modulSKP): ?>
    <div class="col-12 mb-5">
      <div class="card shadow mt-4 no-access-card">
        <div class="card-body text-center">
          <!-- Ikon STOP berbentuk lingkaran merah dengan teks putih -->
          <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120" class="mb-3">
            <circle cx="60" cy="60" r="55" fill="#cc0000" stroke="#990000" stroke-width="5"/>
            <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="32" font-family="Arial, sans-serif" fill="#ffffff" font-weight="bold">
              STOP
            </text>
          </svg>
          <h5 class="card-title fw-bold">⚠️ Tidak ada menu tersedia</h5>
          <p class="card-text" style="color:#cfcfcf;">
            Akun Anda belum memiliki akses ke menu apa pun.<br>
            Silakan hubungi administrator untuk pengaturan hak akses.
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>
  
  <?php include __DIR__ . '/layout/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/index.js"></script>
</body>
</html>
