<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Set Tambah Jaga.</div>";
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = bukakoneksi();

// proses simpan (insert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $tnj        = $_POST['tnj'] ?? null;
    $pendidikan = $_POST['pendidikan'] ?? null;
    if ($tnj && $pendidikan) {
        $stmt = $conn->prepare("INSERT INTO set_jgtambah (tnj, pendidikan) VALUES (?, ?)");
        $stmt->bind_param("ss", $tnj, $pendidikan);
        $stmt->execute();
    }
    header("Location: set_jgtambah.php");
    exit;
}

// proses hapus
if (isset($_GET['delete_tnj'])) {
    $delTnj = $_GET['delete_tnj'];
    $stmt = $conn->prepare("DELETE FROM set_jgtambah WHERE tnj=?");
    $stmt->bind_param("s", $delTnj);
    $stmt->execute();
    header("Location: set_jgtambah.php");
    exit;
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM set_jgtambah");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// simpan jumlah data untuk ditampilkan
$jmlData   = $totalData;

// ambil data sesuai halaman
$sql  = "SELECT tnj, pendidikan FROM set_jgtambah ORDER BY tnj LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// ambil daftar pendidikan yang sudah dipakai di set_jgtambah
$usedPend = [];
$resUsed = mysqli_query($conn,"SELECT DISTINCT pendidikan FROM set_jgtambah");
if ($resUsed) {
    while($row = mysqli_fetch_assoc($resUsed)){
        $usedPend[] = $row['pendidikan'];
    }
}

// ambil daftar pendidikan untuk dropdown, exclude yang sudah dipakai
$pendidikanList = [];
$resPend = mysqli_query($conn,"SELECT tingkat FROM pendidikan ORDER BY tingkat ASC");
if ($resPend) {
    while($row = mysqli_fetch_assoc($resPend)){
        if (!in_array($row['tingkat'], $usedPend)) {
            $pendidikanList[] = $row['tingkat'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Set Tambah Jaga</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Set Tambah Jaga</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body">
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-master align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Besar Tunjangan</th>
              <th>Pendidikan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['tnj']) ?></td>
              <td><?= htmlspecialchars($row['pendidikan']) ?></td>
              <td class="text-center">
                <a href="set_jgtambah.php?delete_tnj=<?= urlencode($row['tnj']) ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
            </li>

            <!-- Nomor Halaman -->
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

  <?php include __DIR__ . '/../../layout/footer.php'; ?>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Tunjangan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Besar Tunjangan</label>
            <input type="text" name="tnj" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Pendidikan</label>
            <select name="pendidikan" class="form-select" required>
              <option value="">-- Pilih Pendidikan --</option>
              <?php foreach($pendidikanList as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
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
