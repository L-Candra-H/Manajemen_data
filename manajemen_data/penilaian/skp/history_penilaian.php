<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

$conn = bukakoneksi();

// === Mapping Sasaran ===
$sasaranMap = [
  '1'=>'1. Mengidentifikasi Pasien Dengan Benar',
  '2'=>'2. Meningkatkan Komunikasi Yang Efektif',
  '3'=>'3. Meningkatkan Keamanan Obat-Obatan Yang Harus Diwaspadai',
  '4'=>'4. Memastikan Lokasi Pembedahan Yang Benar, Prosedur Yang Benar, Pembedahan Pada Pasien Yang Benar',
  '5'=>'5. Mengurangi Risiko Infeksi Akibat Perawatan Kesehatan',
  '6'=>'6. Mengurangi Risiko Cidera Pasien Akibat Terjatuh'
];

// === Ambil daftar pegawai untuk dropdown ===
$pegawaiList = $conn->query("SELECT nik, nama FROM pegawai ORDER BY nik ASC");

// === Filter handling ===
$where = [];
$filterActive = false;

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date'])." 00:00:00";
    $end   = $conn->real_escape_string($_GET['end_date'])." 23:59:59";
    $where[] = "p.tanggal BETWEEN '$start' AND '$end'";
    $filterActive = true;
} elseif (!empty($_GET['start_date'])) {
    $start = $conn->real_escape_string($_GET['start_date'])." 00:00:00";
    $where[] = "p.tanggal >= '$start'";
    $filterActive = true;
} elseif (!empty($_GET['end_date'])) {
    $end = $conn->real_escape_string($_GET['end_date'])." 23:59:59";
    $where[] = "p.tanggal <= '$end'";
    $filterActive = true;
}

if (!empty($_GET['nik_nama'])) {
    $search = $conn->real_escape_string($_GET['nik_nama']);
    $where[] = "p.nik_dinilai = '$search'";
    $filterActive = true;
}

$sql = "SELECT p.nomor_penilaian,
               p.nik_dinilai,
               p.nik_penilai,
               p.tanggal,
               p.keterangan,
               p.status,
               dinilai.nama AS nama_dinilai,
               penilai.nama AS nama_penilai
        FROM skp_penilaian p
        LEFT JOIN pegawai dinilai ON p.nik_dinilai = dinilai.nik
        LEFT JOIN pegawai penilai ON p.nik_penilai = penilai.nik";

if ($filterActive) {
    $sql .= " WHERE " . implode(" AND ", $where);
} else {
    // jika filter tidak aktif, paksa kosong
    $sql .= " WHERE 1=0";
}

$sql .= " ORDER BY p.tanggal DESC";
$qPenilaian = $conn->query($sql);

// Ambil semua detail sekaligus
$sqlDetail = "SELECT d.nomor_penilaian,
                     d.kode_kriteria,
                     k.nama_kriteria,
                     d.skala_penilaian,
                     kat.nama_kategori,
                     kat.sasaran
              FROM skp_detail_penilaian d
              LEFT JOIN skp_kriteria_penilaian k ON d.kode_kriteria = k.kode_kriteria
              LEFT JOIN skp_kategori_penilaian kat ON k.kode_kategori = kat.kode_kategori
              ORDER BY d.nomor_penilaian, d.kode_kriteria ASC";
$qDetail = $conn->query($sqlDetail);

$detailMap = [];
while($det = $qDetail->fetch_assoc()){
    $detailMap[$det['nomor_penilaian']][] = $det;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>History Pengkajian Petugas/Dokter Dalam Implementasi SKP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="skp.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-start">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">History Pengkajian Petugas/Dokter Dalam Implementasi SKP</h5>
        <div class="d-flex gap-2">
          <a href="penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body">

        <!-- Filter Form (1 baris, 2 filter) -->
        <form method="get" class="d-flex align-items-center mb-3 flex-nowrap" style="gap:12px; overflow-x:auto;">
  <!-- Range Tanggal -->
  <div class="d-flex align-items-center" style="gap:6px;">
    <label for="start_date" class="form-label mb-0">Range Tanggal :</label>
    <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" style="width:110px;" value="<?= $_GET['start_date'] ?? '' ?>">
    <span class="fw-bold">s/d</span>
    <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" style="width:110px;" value="<?= $_GET['end_date'] ?? '' ?>">
  </div>

  <!-- Nama Pegawai -->
  <div class="d-flex align-items-center" style="gap:6px;">
    <label for="nik_nama" class="form-label mb-0">Nama Pegawai :</label>
    <select id="nik_nama" name="nik_nama" class="form-select form-select-sm" style="width:150px;">
      <option value="">-- Pilih Nama --</option>
      <?php while($pg = $pegawaiList->fetch_assoc()): ?>
        <option value="<?= $pg['nik'] ?>" <?= (($_GET['nik_nama'] ?? '') == $pg['nik']) ? 'selected' : '' ?>>
          <?= $pg['nama'] ?> (<?= $pg['nik'] ?>)
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <!-- Tombol -->
  <div class="d-flex align-items-center" style="gap:6px;">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="history_penilaian.php" class="btn btn-outline-secondary btn-sm">Reset</a>
  </div>
</form>

        <!-- Tabel Utama -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-master_skp">
            <thead class="table-dark text-center">
              <tr>
                <th>No. Pengkajian</th>
                <th>Yang Dinilai</th>
                <th>Yang Menilai</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($qPenilaian->num_rows > 0): ?>
                <?php while($row = $qPenilaian->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['nomor_penilaian'] ?></td>
                  <td><?= $row['nik_dinilai']." - ".$row['nama_dinilai'] ?></td>
                  <td><?= $row['nik_penilai']." - ".$row['nama_penilai'] ?></td>
                  <td><?= $row['tanggal'] ?></td>
                  <td><?= $row['keterangan'] ?></td>
                  <td><?= $row['status'] ?></td>
                </tr>
                <tr>
                  <td></td>
                  <td colspan="5">
                    <table class="table table-sm table-bordered mb-0 sub-table">
                      <thead class="table-secondary text-center">
                        <tr>
                          <th>Kode</th>
                          <th>Kriteria</th>
                          <th>Skala</th>
                          <th>Kategori</th>
                          <th>Sasaran</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if(isset($detailMap[$row['nomor_penilaian']])): ?>
                          <?php foreach($detailMap[$row['nomor_penilaian']] as $det): ?>
                            <tr>
                              <td><?= $det['kode_kriteria'] ?></td>
                              <td><?= $det['nama_kriteria'] ?></td>
                              <td><?= $det['skala_penilaian'] ?></td>
                              <td><?= $det['nama_kategori'] ?></td>
                              <td><?= isset($sasaranMap[$det['sasaran']]) ? $sasaranMap[$det['sasaran']] : $det['sasaran'] ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr><td colspan="5" class="text-center">Tidak ada rincian</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center">Belum ada data. Silakan gunakan filter.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
