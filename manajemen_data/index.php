<?php
session_start();
include __DIR__ . '/conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Manajemen Data</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/index.css">
  <link rel="stylesheet" href="layout/header.css">
</head>
<body>
  <?php include __DIR__ . '/layout/header.php'; ?>

  <main class="main-content container-fluid mt-4">
    <div class="row justify-content-center">

      <!-- Modul Master Data Pegawai -->
      <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"]) || !empty($_SESSION["master_berkas_pegawai"]) || $_SESSION["hak_akses"]==="administrator"): ?>
        <div class="col-md-6 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Master Data Pegawai</h5>
              <p class="text-muted mb-0">Referensi & Master Data</p>

              <?php if (!empty($_SESSION["pegawai_admin"])): ?>
                <a href="kepegawaian/master_data/bidang.php" class="btn btn-primary btn-sm mt-3">📋 Bidang</a>
                <a href="kepegawaian/master_data/pendidikan.php" class="btn btn-primary btn-sm mt-3">📋 Pendidikan</a>
                <a href="kepegawaian/master_data/stts_kerja.php" class="btn btn-primary btn-sm mt-3">📋 Status Kerja</a>
                <a href="kepegawaian/master_data/stts_wp.php" class="btn btn-primary btn-sm mt-3">📋 Status Wajib Pajak</a>
                <a href="kepegawaian/master_data/jnj_jabatan.php" class="btn btn-primary btn-sm mt-3">📋 Jenjang Jabatan</a>
                <a href="kepegawaian/master_data/kelompok_jabatan.php" class="btn btn-primary btn-sm mt-3">📋 Kelompok Jabatan</a>
                <a href="kepegawaian/master_data/resiko_kerja.php" class="btn btn-primary btn-sm mt-3">📋 Resiko Kerja</a>
                <a href="kepegawaian/master_data/emergency_index.php" class="btn btn-primary btn-sm mt-3">📋 Emergency Index</a>
                <a href="kepegawaian/master_data/pencapaian_kinerja.php" class="btn btn-primary btn-sm mt-3">📋 Pencapaian Kinerja</a>
                <a href="kepegawaian/master_data/evaluasi_kinerja.php" class="btn btn-primary btn-sm mt-3">📋 Evaluasi Kinerja</a>
                <a href="kepegawaian/master_data/departemen.php" class="btn btn-primary btn-sm mt-3">📋 Departemen</a>
                <a href="kepegawaian/master_data/bank.php" class="btn btn-primary btn-sm mt-3">📋 Bank</a>
              <?php endif; ?>

              <!-- Koperasi, Jamsostek, BPJS -->
              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/master_data/koperasi.php" class="btn btn-primary btn-sm mt-3">📋 Koperasi</a>
                <a href="kepegawaian/master_data/jamsostek.php" class="btn btn-primary btn-sm mt-3">📋 BPJS Ketenagakerjaan</a>
                <a href="kepegawaian/master_data/bpjs.php" class="btn btn-primary btn-sm mt-3">📋 BPJS Kesehatan</a>
              <?php endif; ?>

              <!-- Jabatan & Spesialis khusus admin -->
              <?php if ($_SESSION["hak_akses"]==="administrator"): ?>
                <a href="kepegawaian/master_data/jabatan.php" class="btn btn-primary btn-sm mt-3">📋 Jabatan</a>
                <a href="kepegawaian/master_data/spesialis.php" class="btn btn-primary btn-sm mt-3">📋 Spesialis</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["master_berkas_pegawai"])): ?>
                <a href="kepegawaian/master_data/master_berkas_pegawai.php" class="btn btn-primary btn-sm mt-3">📋 Master Berkas Pegawai</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Modul Manajemen Data Pegawai -->
      <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"]) || !empty($_SESSION["petugas"]) || !empty($_SESSION["dokter"])): ?>
        <div class="col-md-6 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Manajemen Data Pegawai</h5>
              <p class="text-muted mb-0">Pengelolaan Data Pegawai</p>

              <?php if (!empty($_SESSION["pegawai_admin"])): ?>
                <a href="kepegawaian/pegawai.php" class="btn btn-primary btn-sm mt-3">📋 Data Pegawai</a>
                <a href="kepegawaian/index_pegawai.php" class="btn btn-primary btn-sm mt-3">📋 Index Pegawai</a>
                <a href="kepegawaian/riwayat_evaluasi.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Evaluasi</a>
                <a href="kepegawaian/riwayat_pencapaian.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Pencapaian</a>
                <?php endif; ?>

              <?php if (!empty($_SESSION["petugas"])): ?>
                <a href="kepegawaian/petugas.php" class="btn btn-primary btn-sm mt-3">📋 Petugas</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["dokter"])): ?>
                <a href="kepegawaian/dokter.php" class="btn btn-primary btn-sm mt-3">📋 Dokter</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/keanggotaan.php" class="btn btn-primary btn-sm mt-3">📋 Keanggotaan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["berkas_kepegawaian"])): ?>
                <a href="kepegawaian/berkas_kepegawaian.php" class="btn btn-primary btn-sm mt-3">📋 Berkas Kepegawaian</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_jabatan"])): ?>
                <a href="kepegawaian/riwayat_jabatan.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Jabatan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_pendidikan"])): ?>
                <a href="kepegawaian/riwayat_pendidikan.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Pendidikan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_naik_gaji"])): ?>
                <a href="kepegawaian/riwayat_naik_gaji.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Naik Gaji</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["kegiatan_ilmiah"])): ?>
                <a href="kepegawaian/riwayat_seminar.php" class="btn btn-primary btn-sm mt-3">📋 Kegiatan Ilmiah & Pelatihan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_penghargaan"])): ?>
                <a href="kepegawaian/riwayat_penghargaan.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Penghargaan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_penelitian"])): ?>
                <a href="kepegawaian/riwayat_penelitian.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Penelitian</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_surat_peringatan"])): ?>
                <a href="kepegawaian/riwayat_surat_peringatan.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Surat Peringatan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pengajuan_cuti"])): ?>
                <a href="kepegawaian/pengajuan_cuti.php" class="btn btn-primary btn-sm mt-3">📋 Pengajuan Cuti</a>
              <?php endif; ?>


            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <?php include __DIR__ . '/layout/footer.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/index.js"></script>
</body>
</html>
