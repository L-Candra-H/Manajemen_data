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

// query jumlah query per user dengan filter tanggal
$sqlUser = "SELECT usere, COUNT(*) AS jumlah_query FROM trackersql";
if ($start_date && $end_date) {
    $sqlUser .= " WHERE DATE(tanggal) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                  AND '" . $conn->real_escape_string($end_date) . "'";
}
$sqlUser .= " GROUP BY usere ORDER BY jumlah_query DESC";
$resUser = $conn->query($sqlUser);

$userLabels = [];
$userData   = [];
if ($resUser) {
    while ($row = $resUser->fetch_assoc()) {
        $userLabels[] = $row['usere'];
        $userData[]   = $row['jumlah_query'];
    }
}

// query proporsi jenis query (INSERT/UPDATE/DELETE) dengan filter tanggal
$sqlJenis = "SELECT 
                CASE 
                  WHEN sqle LIKE 'INSERT%' THEN 'INSERT'
                  WHEN sqle LIKE 'UPDATE%' THEN 'UPDATE'
                  WHEN sqle LIKE 'DELETE%' THEN 'DELETE'
                  ELSE 'LAINNYA'
                END AS jenis,
                COUNT(*) AS jumlah
             FROM trackersql";
if ($start_date && $end_date) {
    $sqlJenis .= " WHERE DATE(tanggal) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                   AND '" . $conn->real_escape_string($end_date) . "'";
}
$sqlJenis .= " GROUP BY jenis";
$resJenis = $conn->query($sqlJenis);

$jenisLabels = [];
$jenisData   = [];
if ($resJenis) {
    while ($row = $resJenis->fetch_assoc()) {
        $jenisLabels[] = $row['jenis'];
        $jenisData[]   = $row['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan SQL</title>
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
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Laporan Aktivitas SQL</h5>
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
          <a href="laporan_sql.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <!-- Grafik -->
        <?php if (!$start_date || !$end_date): ?>
          <div class="alert alert-info text-center">Silakan pilih rentang tanggal terlebih dahulu untuk menampilkan grafik.</div>
        <?php elseif (empty($userLabels) && empty($jenisLabels)): ?>
          <div class="alert alert-warning text-center">Tidak ada data SQL pada rentang tanggal yang dipilih.</div>
        <?php else: ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <canvas id="sqlUserChart"></canvas>
            </div>
            <div class="col-md-6 mb-3">
              <canvas id="sqlJenisChart"></canvas>
            </div>
          </div>
          <script>
            // Grafik jumlah query per user
            new Chart(document.getElementById('sqlUserChart'), {
              type: 'bar',
              data: {
                labels: <?= json_encode($userLabels) ?>,
                datasets: [{
                  label: 'Jumlah Query per User',
                  data: <?= json_encode($userData) ?>,
                  backgroundColor: 'rgba(255, 99, 132, 0.7)'
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 1,
                plugins: { legend: { display: false } }
              }
            });

            // Grafik proporsi jenis query
            new Chart(document.getElementById('sqlJenisChart'), {
              type: 'pie',
              data: {
                labels: <?= json_encode($jenisLabels) ?>,
                datasets: [{
                  label: 'Jenis Query',
                  data: <?= json_encode($jenisData) ?>,
                  backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                  ]
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2
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
