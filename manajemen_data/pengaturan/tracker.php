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
$activeTab  = $_GET['tab'] ?? 'login'; // default tab login

// pagination
$limit = 5;
$pageLogin = isset($_GET['pageLogin']) ? (int)$_GET['pageLogin'] : 1;
$pageSql   = isset($_GET['pageSql']) ? (int)$_GET['pageSql'] : 1;
$offsetLogin = ($pageLogin - 1) * $limit;
$offsetSql   = ($pageSql - 1) * $limit;

// query tracker (login)
$sqlTrackerBase = "FROM tracker";
if ($start_date && $end_date) {
    $sqlTrackerBase .= " WHERE tgl_login BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                         AND '" . $conn->real_escape_string($end_date) . "'";
}
$sqlTracker = "SELECT nip, tgl_login, jam_login $sqlTrackerBase ORDER BY tgl_login DESC, jam_login DESC LIMIT $limit OFFSET $offsetLogin";
$resTracker = ($start_date && $end_date) ? $conn->query($sqlTracker) : null;
$totalLogin = ($start_date && $end_date) ? $conn->query("SELECT COUNT(*) AS cnt $sqlTrackerBase")->fetch_assoc()['cnt'] : 0;
$totalPagesLogin = ($totalLogin > 0) ? ceil($totalLogin / $limit) : 0;

// query trackersql (SQL)
$sqlTrackersqlBase = "FROM trackersql";
if ($start_date && $end_date) {
    $sqlTrackersqlBase .= " WHERE DATE(tanggal) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                            AND '" . $conn->real_escape_string($end_date) . "'";
}
$sqlTrackersql = "SELECT tanggal, usere, sqle $sqlTrackersqlBase ORDER BY tanggal DESC LIMIT $limit OFFSET $offsetSql";
$resTrackersql = ($start_date && $end_date) ? $conn->query($sqlTrackersql) : null;
$totalSql = ($start_date && $end_date) ? $conn->query("SELECT COUNT(*) AS cnt $sqlTrackersqlBase")->fetch_assoc()['cnt'] : 0;
$totalPagesSql = ($totalSql > 0) ? ceil($totalSql / $limit) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tracker Aktivitas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Tracker Aktivitas Sistem</h5>
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
          <a href="tracker.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs" id="trackerTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Penelusuran LOGIN</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'sql' ? 'active' : '' ?>" id="sql-tab" data-bs-toggle="tab" data-bs-target="#sql" type="button" role="tab">Penelusuran SQL</button>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="trackerTabsContent">
          <!-- LOGIN -->
          <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
            <div class="table-wrapper">
              <table class="table table-striped table-bordered table-master align-middle">
                <thead class="table-dark text-center">
                  <tr>
                    <th>NIP</th>
                    <th>Nama Pegawai</th>
                    <th>Tanggal Login</th>
                    <th>Jam Login</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$start_date || !$end_date): ?>
                    <tr><td colspan="4" class="text-center">Silakan pilih rentang tanggal terlebih dahulu</td></tr>
                  <?php elseif ($resTracker && $resTracker->num_rows > 0): ?>
                    <?php while ($row = $resTracker->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['nip']) ?></td>
                        <td><?= htmlspecialchars($row['nip']) ?></td>
                        <td><?= htmlspecialchars($row['tgl_login']) ?></td>
                        <td><?= htmlspecialchars($row['jam_login']) ?></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr><td colspan="4" class="text-center">Tidak ada data login</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <!-- Pagination Login -->
            <?php if ($start_date && $end_date && $totalPagesLogin >= 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $pageLogin <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageLogin=<?= max(1, $pageLogin - 1) ?>&tab=login">Prev</a>
                </li>
                <?php
                  $start = max(1, $pageLogin - 1);
                  $end   = min($totalPagesLogin, $pageLogin + 1);
                  for ($i = $start; $i <= $end; $i++):
                ?>
                  <li class="page-item <?= $i == $pageLogin ? 'active' : '' ?>">
                    <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageLogin=<?= $i ?>&tab=login"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $pageLogin >= $totalPagesLogin ? 'disabled' : '' ?>">
                  <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageLogin=<?= min($totalPagesLogin, $pageLogin + 1) ?>&tab=login">Next</a>
                </li>
              </ul>
            </nav>
            <?php endif; ?>
          </div>

          <!-- SQL -->
          <div class="tab-pane fade <?= $activeTab === 'sql' ? 'show active' : '' ?>" id="sql" role="tabpanel" aria-labelledby="sql-tab">
            <div class="table-wrapper">
              <table class="table table-striped table-bordered table-master align-middle">
                <thead class="table-dark text-center">
                  <tr>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>SQL</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$start_date || !$end_date): ?>
                    <tr><td colspan="3" class="text-center">Silakan pilih rentang tanggal terlebih dahulu</td></tr>
                  <?php elseif ($resTrackersql && $resTrackersql->num_rows > 0): ?>
                    <?php while ($row = $resTrackersql->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['usere']) ?></td>
                        <td><pre class="mb-0"><?= htmlspecialchars($row['sqle']) ?></pre></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr><td colspan="3" class="text-center">Tidak ada data SQL</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <!-- Pagination SQL -->
            <?php if ($start_date && $end_date && $totalPagesSql >= 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $pageSql <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageSql=<?= max(1, $pageSql - 1) ?>&tab=sql">Prev</a>
                </li>
                <?php
                  $start = max(1, $pageSql - 1);
                  $end   = min($totalPagesSql, $pageSql + 1);
                  for ($i = $start; $i <= $end; $i++):
                ?>
                  <li class="page-item <?= $i == $pageSql ? 'active' : '' ?>">
                    <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageSql=<?= $i ?>&tab=sql"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $pageSql >= $totalPagesSql ? 'disabled' : '' ?>">
                  <a class="page-link" href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&pageSql=<?= min($totalPagesSql, $pageSql + 1) ?>&tab=sql">Next</a>
                </li>
              </ul>
            </nav>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
