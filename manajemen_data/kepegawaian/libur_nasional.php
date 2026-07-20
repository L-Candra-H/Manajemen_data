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

// ambil tahun & bulan dari URL, fallback ke sekarang
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date("Y");
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date("n");

// pagination setup
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 5;
$offset = ($page - 1) * $limit;

// total rows sesuai bulan & tahun
$totalRows  = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS cnt FROM set_hari_libur 
     WHERE YEAR(tanggal)='$tahun' AND MONTH(tanggal)='$bulan'"
))['cnt'];
$totalPages = ceil($totalRows / $limit);

// ambil data dengan limit
$result = mysqli_query(
    $conn,
    "SELECT * FROM set_hari_libur 
     WHERE YEAR(tanggal)='$tahun' AND MONTH(tanggal)='$bulan'
     ORDER BY tanggal ASC LIMIT $offset, $limit"
);

// simpan data
if (isset($_POST['simpan'])) {
    $tanggal = $_POST['tanggal'];
    $ktg     = trim($_POST['ktg']);

    // cek apakah tanggal libur sudah ada
    $cek = mysqli_query($conn, "SELECT 1 FROM set_hari_libur WHERE tanggal='$tanggal'");
    if (mysqli_num_rows($cek) > 0) {
        // trigger modal popup warning
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                  var myModal = new bootstrap.Modal(document.getElementById('warningModal'));
                  myModal.show();
                });
              </script>";
    } else {
        mysqli_query($conn, "INSERT INTO set_hari_libur(tanggal, ktg) VALUES('$tanggal','$ktg')");
        header("Location: libur_nasional.php?tahun=$tahun&bulan=$bulan");
        exit;
    }
}

// hapus data
if (isset($_GET['hapus_tanggal'])) {
    $tanggal = $_GET['hapus_tanggal'];
    mysqli_query($conn, "DELETE FROM set_hari_libur WHERE tanggal='$tanggal'");
    header("Location: libur_nasional.php?tahun=$tahun&bulan=$bulan");
    exit;
}

// array bulan
$bulanArr = [
    1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",
    5=>"Mei",6=>"Juni",7=>"Juli",8=>"Agustus",
    9=>"September",10=>"Oktober",11=>"November",12=>"Desember"
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Libur Nasional</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
        Libur Nasional - <?= $bulanArr[$bulan] ?> <?= $tahun ?>
      </h5>
      <div class="d-flex gap-2">
        <a href="set_tahun.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>
    </div>

    <div class="card-body">
      <!-- Form Input -->
      <form method="post" class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">Tgl. Libur Nasional</label>
          <?php
            // hitung tanggal awal & akhir bulan
            $minDate = sprintf("%04d-%02d-01", $tahun, $bulan);
            $maxDate = sprintf("%04d-%02d-%02d", $tahun, $bulan, cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun));
          ?>
          <input type="date" name="tanggal" class="form-control text-center"
                 min="<?= $minDate ?>" max="<?= $maxDate ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Keterangan</label>
          <input type="text" name="ktg" class="form-control" placeholder="Keterangan Libur" required>
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-center">
          <button type="submit" name="simpan" class="btn btn-success me-2">Simpan</button>
          <a href="libur_nasional.php?tahun=<?= $tahun ?>&bulan=<?= $bulan ?>" class="btn btn-secondary">Batal</a>
        </div>
      </form>

      <!-- Tabel Data -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-setting align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Tgl. Libur</th>
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= $row['tanggal'] ?></td>
                <td><?= $row['ktg'] ?></td>
                <td class="text-center">
                  <a href="libur_nasional.php?hapus_tanggal=<?= $row['tanggal'] ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>" 
                     class="btn btn-danger btn-sm" 
                     onclick="return confirm('Hapus data ini?')">Hapus</a>
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
            <a class="page-link" href="?tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
          </li>
          <?php
            $start = max(1, $page - 1);
            $end   = min($totalPages, $page + 1);
            for ($i = $start; $i <= $end; $i++):
          ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>

      <!-- Modal Warning -->
      <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-warning">
           <div class="modal-header bg-warning">
              <h5 class="modal-title" id="warningLabel">⚠️ Peringatan</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
              Tanggal libur nasional yang dipilih sudah ada sebelumnya!
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
