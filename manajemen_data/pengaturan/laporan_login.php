<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

// cek hak akses khusus administrator
if ($_SESSION["hak_akses"] !== "administrator") {
    echo "<div class='alert alert-danger'>Akses ditolak. Hanya administrator yang bisa membuka menu User.</div>";
    exit;
}

$conn = bukakoneksi();

// ambil filter tanggal dari GET
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// query login per pegawai dengan filter tanggal
$sql = "SELECT nip, COUNT(*) AS jumlah_login 
        FROM tracker";
if ($start_date && $end_date) {
    $sql .= " WHERE tgl_login BETWEEN '" . $conn->real_escape_string($start_date) . "' 
              AND '" . $conn->real_escape_string($end_date) . "'";
}
$sql .= " GROUP BY nip ORDER BY jumlah_login DESC";

$res = $conn->query($sql);

$labels = [];
$data   = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['nip'];
        $data[]   = $row['jumlah_login'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4 pb-5">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Laporan Aktivitas Login</h5>
        <div class="d-flex gap-2">
          <a href="../index_administrator.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>
      
      <div class="card-body p-3">

        <!-- Filter Rentang Tanggal -->
        <form method="get" class="d-flex align-items-center flex-nowrap gap-2 mb-3">
          <label class="form-label mb-0">Range Tanggal :</label>
          <input type="date" name="start_date" class="form-control form-control-sm" style="width:160px;" value="<?= htmlspecialchars($start_date) ?>">
          <span class="fw-bold">s/d</span>
          <input type="date" name="end_date" class="form-control form-control-sm" style="width:160px;" value="<?= htmlspecialchars($end_date) ?>">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          <a href="laporan_login.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <!-- Grafik -->
        <?php if (!$start_date || !$end_date): ?>
          <div class="alert alert-info text-center">Silakan pilih rentang tanggal terlebih dahulu untuk menampilkan grafik.</div>
        <?php elseif (empty($labels)): ?>
          <div class="alert alert-warning text-center">Tidak ada data login pada rentang tanggal yang dipilih.</div>
        <?php else: ?>
          <canvas id="loginChart"></canvas>
          <script>
            const ctxLogin = document.getElementById('loginChart');
            new Chart(ctxLogin, {
              type: 'bar',
              data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                  label: 'Jumlah Login',
                  data: <?= json_encode($data) ?>,
                  backgroundColor: 'rgba(54, 162, 235, 0.7)'
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2, // grafik lebih ramping
                plugins: {
                  legend: { display: false }
                }
              }
            });
          </script>
        <?php endif; ?>

      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
