<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();

// pagination setup
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6; // jumlah baris per halaman
$offset = ($page - 1) * $limit;

// total rows
$totalRows   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM set_tahun"))['cnt'];
$totalPages  = ceil($totalRows / $limit);

// simpan data
if (isset($_POST['simpan'])) {
    $tahun = intval($_POST['tahun']);
    $bulan = intval($_POST['bulan']);

    // hitung jumlah hari dalam bulan
    $jmlhr = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

    // hitung jumlah hari libur (Minggu)
    $jmllbr = 0;
    for ($d = 1; $d <= $jmlhr; $d++) {
        $tgl = mktime(0, 0, 0, $bulan, $d, $tahun);
        if (date("w", $tgl) == 0) { // Minggu
            $jmllbr++;
        }
    }

    $normal = $jmlhr - $jmllbr;

    mysqli_query($conn, "INSERT INTO set_tahun(tahun,bulan,jmlhr,jmllbr,normal) 
                         VALUES('$tahun','$bulan','$jmlhr','$jmllbr','$normal')");
    header("Location: set_tahun.php");
    exit;
}

// hapus data
if (isset($_GET['hapus_tahun']) && isset($_GET['hapus_bulan'])) {
    $tahun = intval($_GET['hapus_tahun']);
    $bulan = intval($_GET['hapus_bulan']);
    mysqli_query($conn, "DELETE FROM set_tahun WHERE tahun='$tahun' AND bulan='$bulan'");
    header("Location: set_tahun.php");
    exit;
}

// array bulan
$bulanArr = [
    1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April",
    5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus",
    9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"
];

// ambil data dengan limit + join libur nasional
$sql = "
  SELECT st.*, IFNULL(libur.jml_libur,0) AS liburCount
  FROM set_tahun st
  LEFT JOIN (
    SELECT YEAR(tanggal) AS thn, MONTH(tanggal) AS bln, COUNT(*) AS jml_libur
    FROM set_hari_libur
    GROUP BY thn, bln
  ) libur
  ON st.tahun = libur.thn AND st.bulan = libur.bln
  ORDER BY st.tahun ASC, st.bulan ASC
  LIMIT $offset, $limit
";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tahun & Bulan</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Tahun & Bulan</h5>
      <div class="d-flex gap-2">
        <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>
    </div>

    <div class="card-body">
      <!-- Form Input -->
      <form method="post" class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">Tahun Gaji</label>
          <select name="tahun" class="form-select text-center" required>
            <option value="">-- Pilih Tahun --</option>
            <?php for ($t = date("Y"); $t >= 1960; $t--): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Bulan</label>
          <select name="bulan" class="form-select text-center" required>
            <option value="">-- Pilih Bulan --</option>
            <?php foreach ($bulanArr as $num => $nama): ?>
              <option value="<?= $num ?>"><?= $nama ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-center">
          <button type="submit" name="simpan" class="btn btn-success me-2">Simpan</button>
          <a href="set_tahun.php" class="btn btn-secondary">Batal</a>
        </div>
      </form>

      <!-- Tabel Data -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-setting align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Tahun Gaji</th>
              <th>Bulan Gaji</th>
              <th>Jumlah Hari</th>
              <th>Jumlah Akhad</th>
              <th>Normal Masuk</th>
              <th>Total Masuk</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <?php $totalMasuk = $row['normal'] - $row['liburCount']; ?>
              <tr>
                <td><?= $row['tahun'] ?></td>
                <td><?= $bulanArr[$row['bulan']] ?></td>
                <td><?= $row['jmlhr'] ?></td>
                <td><?= $row['jmllbr'] ?></td>
                <td><?= $row['normal'] ?></td>
                <td><?= $totalMasuk ?></td>
                <td class="text-center">
                  <a href="set_tahun.php?hapus_tahun=<?= $row['tahun'] ?>&hapus_bulan=<?= $row['bulan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data ini?')">Hapus</a>
                  <a href="libur_nasional.php?tahun=<?= $row['tahun'] ?>&bulan=<?= $row['bulan'] ?>" class="btn btn-warning btn-sm">Libur Nasional</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages >= 1): ?>
      <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Prev</a>
          </li>
          <?php
            $start = max(1, $page - 1);
            $end   = min($totalPages, $page + 1);
            for ($i = $start; $i <= $end; $i++):
          ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>

    </div>
  </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
