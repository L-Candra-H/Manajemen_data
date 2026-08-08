<?php
session_start();
include __DIR__ . '/conf/conf.php';
include __DIR__ . '/conf/auth.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Utama</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/index.css">
  <link rel="stylesheet" href="layout/header.css">
</head>
<body>
<?php include __DIR__ . '/layout/header.php'; ?>

<main class="main-content container-fluid mt-3"> 
  <div class="row justify-content-center">

    <!-- Kotak Kepegawaian -->
    <div class="col-md-6 mb-3">
      <div class="card shadow h-100 border-primary">
        <div class="card-body">
          <h5 class="card-title fw-bold text-center text-primary">📋 KEPEGAWAIAN</h5>

          <p class="small fw-bold mb-1 text-dark">🗂 MASTER DATA PEGAWAI :</p>
          <p class="small text-muted bg-light p-2 rounded">
            Master Berkas Pegawai, Bidang, Pendidikan, Status Kerja, Status WP, Jenjang Jabatan, 
            Kelompok Jabatan, Resiko Kerja, Emergency Index, Pencapaian Kinerja, Evaluasi Kinerja, 
            Departemen, Bank, Jam Jaga Departemen, Jabatan, Koperasi, BPJS Ketenagakerjaan, 
            BPJS Kesehatan, Tunjangan Harian, Tunjangan Bulanan, Harian - Bulanan, Spesialis, 
            Set Jaga Malam, Set Tambah Jaga, Set Tunjangan Hadir, Set Lembur HB, Set Lembur HR
          </p>

          <p class="small fw-bold mb-1 text-dark">👥 MANAJEMEN DATA PEGAWAI :</p>
          <p class="small text-muted bg-light p-2 rounded">
            Petugas, Dokter, Pengajuan Cuti, Tahun & Bulan, Insentif, Index Insentif, Bagian Akte, 
            Pendapatan Tuslah, Pendapatan Warung, Data Pegawai, Riwayat Evaluasi, Riwayat Pencapaian, 
            Lembur Pegawai, Keanggotaan, Potongan Gaji, Tunjangan, Jasa Lain, Daftar Kasift, Koperasi, 
            Berkas Kepegawaian, Riwayat Jabatan, Riwayat Pendidikan, Riwayat Naik Gaji, 
            Kegiatan Ilmiah & Pelatihan, Riwayat Penghargaan, Riwayat Surat Peringatan, Riwayat Penelitian
          </p>

          <div class="text-center">
            <a href="index.php" class="btn btn-primary btn-sm mt-3 px-4">📋 Buka Kepegawaian</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Kotak Penilaian -->
    <div class="col-md-6 mb-3">
      <div class="card shadow h-100 border-success">
        <div class="card-body">
          <h5 class="card-title fw-bold text-center text-success">📊 PENILAIAN</h5>

          <p class="small fw-bold mb-1 text-dark">🛡 KESELAMATAN DAN KESEHATAN KERJA (K3) :</p>
          <p class="small text-muted bg-light p-2 rounded">
            Jenis Cidera K3, Penyebab Kecelakaan K3, Jenis Luka K3, Lokasi Kejadian K3, Dampak Cidera K3, 
            Jenis Pekerjaan K3, Bagian Tubuh K3, Peristiwa K3, 
            Jenis Cidera K3 Per Tahun, Penyebab Kecelakaan K3 Per Tahun, Jenis Luka K3 Per Tahun, 
            Lokasi Kejadian K3 Per Tahun, Dampak Cidera K3 Per Tahun, Jenis Pekerjaan K3 Per Tahun, Bagian Tubuh K3 Per Tahun
          </p>

          <p class="small fw-bold mb-1 text-dark">📑 AUDIT :</p>
          <p class="small text-muted bg-light p-2 rounded">
            Audit Kepatuhan APD, Audit Cuci Tangan Medis, Ruang/Unit Audit Kepatuhan, Audit Pembuangan Limbah, 
            Audit Pembuangan Benda Tajam & Jarum, Audit Penanganan Darah, Audit Pengelolaan Linen Kotor, 
            Audit Penempatan Pasien, Audit Kamar Jenazah, Audit Bundle IADP, Audit Bundle IDO, 
            Audit Fasilitas Kebersihan Tangan, Audit Fasilitas APD, Audit Pembuangan Limbah Cair Infeksius, 
            Audit Sterilisasi Alat, Audit Bundle ISK, Audit Bundle PLABSI, Audit Bundle VAP
          </p>

          <p class="small fw-bold mb-1 text-dark">🎯 SASARAN KESELAMATAN PASIEN (SKP) :</p>
          <p class="small text-muted bg-light p-2 rounded">
            Kategori Pengkajian SKP, Kriteria Pengkajian SKP, Pengkajian SKP Petugas/Dokter, Rekapitulasi Pengkajian SKP
          </p>

          <div class="text-center">
            <a href="index_penilaian.php" class="btn btn-success btn-sm mt-3 px-4">📊 Buka Penilaian</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/layout/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
