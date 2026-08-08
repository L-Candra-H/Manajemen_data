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

// === PAGINATION ===
$limit = 5; // jumlah data per halaman
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// ambil data dengan limit
$result = mysqli_query($conn, "SELECT * FROM k3rs_peristiwa ORDER BY kode_cidera ASC LIMIT $start, $limit");

// hitung total data
$qTotal   = mysqli_query($conn, "SELECT COUNT(*) AS total FROM k3rs_peristiwa");
$dataTotal= mysqli_fetch_assoc($qTotal);
$total    = $dataTotal['total'];
$totalPages = ceil($total / $limit);

// ambil tahun dari filter
$tahun = $_GET['tahun'] ?? '';
$page  = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$data = [];
$total_rows = 0;

if ($tahun != '') {
    // hitung total baris untuk pagination (jumlah jenis luka)
    $countSql = "SELECT COUNT(*) AS jml FROM k3rs_jenis_luka";
    $total_rows = $conn->query($countSql)->fetch_assoc()['jml'];

    // query data jenis luka per bulan (LEFT JOIN agar semua jenis tampil)
    $sql = "SELECT c.jenis_luka,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=1 THEN 1 ELSE 0 END),0) AS Jan,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=2 THEN 1 ELSE 0 END),0) AS Feb,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=3 THEN 1 ELSE 0 END),0) AS Mar,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=4 THEN 1 ELSE 0 END),0) AS Apr,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=5 THEN 1 ELSE 0 END),0) AS Mei,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=6 THEN 1 ELSE 0 END),0) AS Jun,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=7 THEN 1 ELSE 0 END),0) AS Jul,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=8 THEN 1 ELSE 0 END),0) AS Agu,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=9 THEN 1 ELSE 0 END),0) AS Sep,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=10 THEN 1 ELSE 0 END),0) AS Okt,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=11 THEN 1 ELSE 0 END),0) AS Nov,
                   COALESCE(SUM(CASE WHEN YEAR(p.tgl_insiden)=? AND MONTH(p.tgl_insiden)=12 THEN 1 ELSE 0 END),0) AS Des
            FROM k3rs_jenis_luka c
            LEFT JOIN k3rs_peristiwa p ON p.kode_luka = c.kode_luka
            GROUP BY c.jenis_luka
            ORDER BY c.jenis_luka ASC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssii",
        $tahun,$tahun,$tahun,$tahun,$tahun,$tahun,
        $tahun,$tahun,$tahun,$tahun,$tahun,$tahun,
        $limit,$offset
    );
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$total_pages = ceil(max($total_rows,1) / $limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Jenis Luka K3 Per Tahun</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Jenis Luka K3 Per Tahun</h5>
          <div class="d-flex gap-2">
            <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- BODY -->
        <div class="card-body p-3">
          <!-- Filter Tahun -->
          <form method="get" class="row g-2 align-items-center mb-3">
            <div class="col-auto">
              <label for="tahun" class="me-2">Tahun:</label>
              <select name="tahun" id="tahun" class="form-select w-auto d-inline">
                <option value="">-- pilih --</option>
                <?php for($th=date("Y"); $th>=1980; $th--): ?>
                  <option value="<?= $th ?>" <?= ($tahun==$th)?'selected':'' ?>><?= $th ?></option>
                <?php endfor; ?>
              </select>
              <button type="submit" class="btn btn-success ms-2">Filter</button>
            </div>
          </form>

          <!-- Tabel -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-master_k3 align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>No.</th>
                  <th>Jenis Luka</th>
                  <th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>Mei</th><th>Jun</th>
                  <th>Jul</th><th>Agu</th><th>Sep</th><th>Okt</th><th>Nov</th><th>Des</th>
                 <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($data)): ?>
                  <tr><td colspan="15" class="text-center">Silakan pilih tahun untuk menampilkan data</td></tr>
                <?php else: ?>
                  <?php $no=$offset+1; $totals = array_fill(0,12,0); ?>
                  <?php foreach($data as $row): 
                    $rowTotal = $row['Jan']+$row['Feb']+$row['Mar']+$row['Apr']+$row['Mei']+$row['Jun']+
                                $row['Jul']+$row['Agu']+$row['Sep']+$row['Okt']+$row['Nov']+$row['Des'];
                    $totals[0]+=$row['Jan']; $totals[1]+=$row['Feb']; $totals[2]+=$row['Mar'];
                    $totals[3]+=$row['Apr']; $totals[4]+=$row['Mei']; $totals[5]+=$row['Jun'];
                    $totals[6]+=$row['Jul']; $totals[7]+=$row['Agu']; $totals[8]+=$row['Sep'];
                    $totals[9]+=$row['Okt']; $totals[10]+=$row['Nov']; $totals[11]+=$row['Des'];
                  ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['jenis_luka']) ?></td>
                      <td><?= $row['Jan'] ?></td><td><?= $row['Feb'] ?></td><td><?= $row['Mar'] ?></td>
                      <td><?= $row['Apr'] ?></td><td><?= $row['Mei'] ?></td><td><?= $row['Jun'] ?></td>
                      <td><?= $row['Jul'] ?></td><td><?= $row['Agu'] ?></td><td><?= $row['Sep'] ?></td>
                      <td><?= $row['Okt'] ?></td><td><?= $row['Nov'] ?></td><td><?= $row['Des'] ?></td>
                      <td><?= $rowTotal ?></td>
                    </tr>
                <?php endforeach; ?>
                  <tr class="fw-bold table-secondary">
                    <td colspan="2" class="text-center">Jumlah</td>
                    <?php foreach($totals as $t): ?><td><?= $t ?></td><?php endforeach; ?>
                    <td><?= array_sum($totals) ?></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <!-- Prev -->
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= ($page > 1) ? $page-1 : 1 ?>">Prev</a>
              </li>

              <!-- Nomor Halaman -->
              <?php for ($i=1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <!-- Next -->
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= ($page < $totalPages) ? $page+1 : $totalPages ?>">Next</a>
              </li>
            </ul>
          </nav>

        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
