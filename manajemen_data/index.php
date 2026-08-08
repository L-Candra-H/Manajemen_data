<?php
session_start();
include __DIR__ . '/conf/conf.php';
include __DIR__ . '/conf/auth.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

// Flag modul A & B
$modulA = (!empty($_SESSION["pegawai_admin"]) 
           || !empty($_SESSION["master_berkas_pegawai"]) 
           || $_SESSION["hak_akses"]==="administrator");

$modulB = (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"]) || !empty($_SESSION["petugas"]) 
           || !empty($_SESSION["dokter"]) || !empty($_SESSION["berkas_kepegawaian"]) || !empty($_SESSION["riwayat_jabatan"]) 
           || !empty($_SESSION["riwayat_pendidikan"]) || !empty($_SESSION["riwayat_naik_gaji"]) || !empty($_SESSION["kegiatan_ilmiah"]) 
           || !empty($_SESSION["riwayat_penghargaan"]) || !empty($_SESSION["riwayat_penelitian"]) || !empty($_SESSION["riwayat_surat_peringatan"]) 
           || !empty($_SESSION["pengajuan_cuti"]));

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

  <main class="main-content container-fluid mt-2 menu-index">  
    <div class="row justify-content-center">

      <!-- Modul Master Data Pegawai -->
      <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["master_berkas_pegawai"]) || $_SESSION["hak_akses"]==="administrator"): ?>
        <div class="col-md-6 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Master Data Pegawai</h5>
              <p class="text-muted mb-0 small">Referensi & Master Data</p>

              <?php if (!empty($_SESSION["master_berkas_pegawai"])): ?>
                <a href="kepegawaian/master_data/master_berkas_pegawai.php" class="btn btn-primary btn-sm mt-3">📋 Master Berkas Pegawai</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"])): ?>
                <a href="kepegawaian/master_data/bidang.php" class="btn btn-outline-success btn-sm mt-3">📋 Bidang</a>
                <a href="kepegawaian/master_data/pendidikan.php" class="btn btn-outline-success btn-sm mt-3">📋 Pendidikan</a>
                <a href="kepegawaian/master_data/stts_kerja.php" class="btn btn-outline-success btn-sm mt-3">📋 Status Kerja</a>
                <a href="kepegawaian/master_data/stts_wp.php" class="btn btn-outline-success btn-sm mt-3">📋 Status Wajib Pajak</a>
                <a href="kepegawaian/master_data/jnj_jabatan.php" class="btn btn-outline-success btn-sm mt-3">📋 Jenjang Jabatan</a>
                <a href="kepegawaian/master_data/kelompok_jabatan.php" class="btn btn-outline-success btn-sm mt-3">📋 Kelompok Jabatan</a>
                <a href="kepegawaian/master_data/resiko_kerja.php" class="btn btn-outline-success btn-sm mt-3">📋 Resiko Kerja</a>
                <a href="kepegawaian/master_data/emergency_index.php" class="btn btn-outline-success btn-sm mt-3">📋 Emergency Index</a>
                <a href="kepegawaian/master_data/pencapaian_kinerja.php" class="btn btn-outline-success btn-sm mt-3">📋 Pencapaian Kinerja</a>
                <a href="kepegawaian/master_data/evaluasi_kinerja.php" class="btn btn-outline-success btn-sm mt-3">📋 Evaluasi Kinerja</a>
                <a href="kepegawaian/master_data/departemen.php" class="btn btn-outline-success btn-sm mt-3">📋 Departemen</a>
                <a href="kepegawaian/master_data/bank.php" class="btn btn-outline-success btn-sm mt-3">📋 Bank</a>
                <a href="kepegawaian/master_data/jam_jaga.php" class="btn btn-outline-success btn-sm mt-3">📋 Jam Jaga Departemen</a>
                <?php endif; ?>

              <!-- Jabatan & Spesialis khusus admin -->
              <?php if ($_SESSION["hak_akses"]==="administrator"): ?>
                <a href="kepegawaian/master_data/koperasi.php" class="btn btn-outline-dark btn-sm mt-3">📋 Stts Koperasi</a>
                <a href="kepegawaian/master_data/jamsostek.php" class="btn btn-outline-dark btn-sm mt-3">📋 Stts BPJS Ketenagakerjaan</a>
                <a href="kepegawaian/master_data/bpjs.php" class="btn btn-outline-dark btn-sm mt-3">📋 Stts BPJS Kesehatan</a>
                <a href="kepegawaian/master_data/master_tunjangan_harian.php" class="btn btn-outline-dark btn-sm mt-3">📋 Tunjangan Harian</a>
                <a href="kepegawaian/master_data/master_tunjangan_bulanan.php" class="btn btn-outline-dark btn-sm mt-3">📋 Tunjangan Bulanan</a>
                <a href="kepegawaian/master_data/harian_kurangi_bulanan.php" class="btn btn-outline-dark btn-sm mt-3">📋 Harian - Bulanan</a>
                <a href="kepegawaian/master_data/jabatan.php" class="btn btn-outline-dark btn-sm mt-3">📋 Jabatan</a>
                <a href="kepegawaian/master_data/spesialis.php" class="btn btn-outline-dark btn-sm mt-3">📋 Spesialis</a>
                <a href="kepegawaian/master_data/set_jgmlm.php" class="btn btn-outline-dark btn-sm mt-3">📋 Set Jaga Malam</a>
                <a href="kepegawaian/master_data/set_jgtambah.php" class="btn btn-outline-dark btn-sm mt-3">📋 Set Tambah Jaga</a>
                <a href="kepegawaian/master_data/set_hadir.php" class="btn btn-outline-dark btn-sm mt-3">📋 Set Tunjangan Hadir</a>
                <a href="kepegawaian/master_data/set_lemburhb.php" class="btn btn-outline-dark btn-sm mt-3">📋 Set Lembur HB</a>
                <a href="kepegawaian/master_data/set_lemburhr.php" class="btn btn-outline-dark btn-sm mt-3">📋 Set Lembur HR</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Modul Manajemen Data Pegawai -->
      <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"]) || !empty($_SESSION["petugas"]) || !empty($_SESSION["dokter"])
              || !empty($_SESSION["berkas_kepegawaian"]) || !empty($_SESSION["riwayat_jabatan"]) || !empty($_SESSION["riwayat_pendidikan"])
              || !empty($_SESSION["riwayat_naik_gaji"]) || !empty($_SESSION["kegiatan_ilmiah"]) || !empty($_SESSION["riwayat_penghargaan"])
              || !empty($_SESSION["riwayat_penelitian"]) || !empty($_SESSION["riwayat_surat_peringatan"]) || !empty($_SESSION["pengajuan_cuti"])): ?>
              
        <div class="col-md-6 mb-3">
          <div class="card shadow h-100 text-center">
            <div class="card-body">
              <h5 class="card-title fw-bold">Manajemen Data Pegawai</h5>
              <p class="text-muted mb-0 small">Pengelolaan Data Pegawai</p>

              <?php if (!empty($_SESSION["petugas"])): ?>
                <a href="kepegawaian/petugas.php" class="btn btn-primary btn-sm mt-3">📋 Petugas</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["dokter"])): ?>
                <a href="kepegawaian/dokter.php" class="btn btn-primary btn-sm mt-3">📋 Dokter</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pengajuan_cuti"])): ?>
                <a href="kepegawaian/pengajuan_cuti.php" class="btn btn-primary btn-sm mt-3">📋 Pengajuan Cuti</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/set_tahun.php" class="btn btn-outline-primary btn-sm mt-3">📋 Tahun & Bulan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/insentif.php" class="btn btn-outline-primary btn-sm mt-3">📋 Insentif</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/pendapatan_akte.php" class="btn btn-outline-primary btn-sm mt-3">📋 Pendapatan Akte</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/pendapatan_resume.php" class="btn btn-outline-primary btn-sm mt-3">📋 Pendapatan Resume</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/pendapatan_tuslah.php" class="btn btn-outline-primary btn-sm mt-3">📋 Pendapatan Tuslah</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/pendapatan_warung.php" class="btn btn-outline-primary btn-sm mt-3">📋 Pendapatan Warung</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"])): ?>
                <a href="kepegawaian/pegawai.php" class="btn btn-outline-success btn-sm mt-3">📋 Data Pegawai</a>
                <a href="kepegawaian/riwayat_evaluasi.php" class="btn btn-outline-success btn-sm mt-3">📋 Riwayat Evaluasi</a>
                <a href="kepegawaian/riwayat_pencapaian.php" class="btn btn-outline-success btn-sm mt-3">📋 Riwayat Pencapaian</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["pegawai_admin"]) || !empty($_SESSION["pegawai_user"])): ?>
                <a href="kepegawaian/lembur_pegawai.php" class="btn btn-outline-primary btn-sm mt-3">📋 Lembur Pegawai</a>
                <a href="kepegawaian/keanggotaan.php" class="btn btn-outline-primary btn-sm mt-3">📋 Keanggotaan</a>
                <a href="kepegawaian/potongan.php" class="btn btn-outline-primary btn-sm mt-3">📋 Potongan Gaji</a>
                <a href="kepegawaian/tunjangan.php" class="btn btn-outline-primary btn-sm mt-3">📋 Tunjangan</a>
                <a href="kepegawaian/jasa_lain.php" class="btn btn-outline-primary btn-sm mt-3">📋 Jasa Lain</a>
                <a href="kepegawaian/kasift.php" class="btn btn-outline-primary btn-sm mt-3">📋 Kasift</a>
                <a href="kepegawaian/peminjaman_koperasi.php" class="btn btn-outline-primary btn-sm mt-3">📋 Koperasi</a>
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

              <?php if (!empty($_SESSION["riwayat_surat_peringatan"])): ?>
                <a href="kepegawaian/riwayat_surat_peringatan.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Surat Peringatan</a>
              <?php endif; ?>

              <?php if (!empty($_SESSION["riwayat_penelitian"])): ?>
                <a href="kepegawaian/riwayat_penelitian.php" class="btn btn-primary btn-sm mt-3">📋 Riwayat Penelitian</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

  </main>

  <!-- Card fallback -->
  <?php if (!$modulA && !$modulB): ?>
    <div class="col-12 mb-5"> <!-- tambahkan mb-5 -->
      <div class="card shadow mt-4 no-access-card">
        <div class="card-body text-center">
          <!-- Ikon ilustrasi ukuran besar -->
          <img src="https://copilot.microsoft.com/th/id/BCO.284a7b6c-bdbd-4b6f-ae95-895b2fdc344e.png" 
               alt="Ikon akses ditolak" 
               class="mb-3" 
               style="max-width:250px;">

          <!-- Judul + pesan -->
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="assets/index.js"></script>
</body>
</html>
