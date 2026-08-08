<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

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

// handler insert/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun = null;
    $bulan = null;

    if (isset($_POST['tahun_bulan'])) {
        list($tahun, $bulan) = explode('-', $_POST['tahun_bulan']);
    } else {
        $tahun = $_POST['tahun'] ?? null;
        $bulan = $_POST['bulan'] ?? null;
    }

    $pendapatan = $_POST['pendapatan'] ?? null;
    $persen     = $_POST['persen'] ?? null;

    if ($_POST['mode'] === 'update') {
        $stmt = $conn->prepare("UPDATE set_insentif SET pendapatan=?, persen=? WHERE tahun=? AND bulan=?");
        $stmt->bind_param("iiii", $pendapatan, $persen, $tahun, $bulan);
        $stmt->execute();

    } elseif ($_POST['mode'] === 'insert') {
        // cek apakah kombinasi tahun-bulan sudah ada
        $cek = $conn->prepare("SELECT COUNT(*) FROM set_insentif WHERE tahun=? AND bulan=?");
        $cek->bind_param("ii", $tahun, $bulan);
        $cek->execute();
        $cek->bind_result($ada);
        $cek->fetch();
        $cek->close();

        if ($ada > 0) {
            echo "<script>alert('Data tahun-bulan ini sudah ada!');</script>";
        } else {
            $total = $pendapatan * $persen / 100;
            $stmt = $conn->prepare("INSERT INTO set_insentif (tahun, bulan, pendapatan, persen, total_insentif) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiii", $tahun, $bulan, $pendapatan, $persen, $total);
            $stmt->execute();
        }

    } elseif ($_POST['mode'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM set_insentif WHERE tahun=? AND bulan=?");
        $stmt->bind_param("ii", $tahun, $bulan);
        $stmt->execute();
    }
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM set_insentif");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// ambil data sesuai halaman
$sql = "SELECT tahun, bulan, pendapatan, persen, total_insentif
        FROM set_insentif
        ORDER BY tahun DESC, bulan DESC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// daftar tahun untuk dropdown tambah
$listTahun = $conn->query("
    SELECT t.tahun, t.bulan
    FROM set_tahun t
    WHERE NOT EXISTS (
        SELECT 1 FROM set_insentif s
        WHERE s.tahun = t.tahun AND s.bulan = t.bulan
    )
    ORDER BY t.tahun DESC, t.bulan DESC
");

$bulanArr = [
    1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
    5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
    9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pendapatan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="main-content container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Pendapatan</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-setting align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Tahun</th>
                <th>Bulan</th>
                <th>Pendapatan</th>
                <th>Prosentase</th>
                <th>Total Insentif</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= htmlspecialchars($row['tahun']) ?></td>
                <td><?= $bulanArr[(int)$row['bulan']] ?></td>
                <td><?= "Rp. " . number_format($row['pendapatan'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($row['persen']) ?>%</td>
                <td><?= "Rp. " . number_format($row['total_insentif'], 0, ',', '.') ?></td>
                <td class="text-center">
                  <button class="btn btn-warning btn-sm"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEdit"
                          data-tahun="<?= htmlspecialchars($row['tahun']) ?>"
                          data-bulan="<?= htmlspecialchars($row['bulan']) ?>"
                          data-bulantext="<?= $bulanArr[(int)$row['bulan']] ?>"
                          data-pendapatan="<?= htmlspecialchars($row['pendapatan']) ?>"
                          data-persen="<?= htmlspecialchars($row['persen']) ?>">
                    ✏️ Edit
                  </button>
                  <a href="detail_insentif.php?tahun=<?= $row['tahun'] ?>&bulan=<?= $row['bulan'] ?>" class="btn btn-info btn-sm me-2">💰 Detail Pembagian</a>
                  <form action="" method="post" style="display:inline">
                    <input type="hidden" name="mode" value="delete">
                    <input type="hidden" name="tahun" value="<?= htmlspecialchars($row['tahun']) ?>">
                    <input type="hidden" name="bulan" value="<?= htmlspecialchars($row['bulan']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

          <!-- Pagination -->
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
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next >></a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Pendapatan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tahun - Bulan</label>
            <select name="tahun_bulan" class="form-select" required>
              <option value="">-- Pilih Tahun + Bulan --</option>
              <?php while($t = $listTahun->fetch_assoc()): ?>
                <option value="<?= $t['tahun'].'-'.$t['bulan'] ?>">
                  <?= $t['tahun'].' - '.$bulanArr[(int)$t['bulan']] ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Pendapatan</label>
            <input type="number" name="pendapatan" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Prosentase Insentif (%)</label>
            <input type="number" name="persen" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="update">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Edit Pendapatan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tahun</label>
            <input type="text" name="tahun" id="editTahun" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Bulan</label>
            <input type="hidden" name="bulan" id="editBulanHidden">
            <input type="text" id="editBulanText" class="form-control bg-danger text-white" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Pendapatan</label>
            <input type="number" name="pendapatan" id="editPendapatan" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Prosentase Insentif (%)</label>
            <input type="number" name="persen" id="editPersen" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('editTahun').value      = button.getAttribute('data-tahun');
      document.getElementById('editBulanHidden').value = button.getAttribute('data-bulan');     // angka
      document.getElementById('editBulanText').value   = button.getAttribute('data-bulantext'); // teks
      document.getElementById('editPendapatan').value = button.getAttribute('data-pendapatan');
      document.getElementById('editPersen').value     = button.getAttribute('data-persen');
    });
  </script>

</body>
</html>
