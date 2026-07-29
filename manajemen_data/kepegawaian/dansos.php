<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Dansos.</div>";
    exit;
}

$conn = bukakoneksi();

// handler insert saja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dansos = $_POST['dana'] ?? null;

    if (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO dansos (dana) VALUES (?)");
        $stmt->bind_param("s", $dansos);
        $stmt->execute();
    }
}

// handler hapus
if (isset($_GET['hapus'])) {
    $dana = $_GET['hapus'];

    $stmt = $conn->prepare("DELETE FROM dansos WHERE dana=?");
    $stmt->bind_param("s", $dana);
    $stmt->execute();

    // redirect agar URL bersih dan data ter-update
    header("Location: dansos.php");
    exit;
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM dansos");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// simpan jumlah dansos untuk ditampilkan
$jmlDansos = $totalData;

// ambil data sesuai halaman
$sql  = "SELECT dana FROM dansos ORDER BY dana LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dana Sosial</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Dana Sosial</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="potongan.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-dansos align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Besarnya Dana Sosial</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['dana']) ?></td>
              <td class="text-center">
                <a href="dansos.php?hapus=<?= urlencode($row['dana']) ?>"  
                   class="btn btn-danger btn-sm" class="text-center"
                   onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlDansos ?>,
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
            </li>

            <!-- Nomor Halaman (batasi 3 sekitar aktif) -->
            <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++):
            ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next »</a>
            </li>
          </ul>
        </nav>

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
          <h5 class="modal-title">Tambah Dana Sosial</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Dana Sosial</label><input type="text" name="dana" class="form-control" required></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
