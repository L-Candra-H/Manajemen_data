<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Pendidikan.</div>";
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);



$conn = bukakoneksi();

// handler insert/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tingkat  = $_POST['tingkat'] ?? null;
    $indek    = (isset($_POST['indek']) && is_numeric($_POST['indek'])) ? (int)$_POST['indek'] : 0;
    $gapok1   = (isset($_POST['gapok1']) && is_numeric($_POST['gapok1'])) ? (int)$_POST['gapok1'] : 0;
    $kenaikan = (isset($_POST['kenaikan']) && is_numeric($_POST['kenaikan'])) ? (int)$_POST['kenaikan'] : 0;
    $maksimal = (isset($_POST['maksimal']) && is_numeric($_POST['maksimal'])) ? (int)$_POST['maksimal'] : 0;

    if (isset($_POST['mode']) && $_POST['mode'] === 'update') {
        $stmt = $conn->prepare("UPDATE pendidikan SET indek=?, gapok1=?, kenaikan=?, maksimal=? WHERE tingkat=?");
        $stmt->bind_param("iiiis", $indek, $gapok1, $kenaikan, $maksimal, $tingkat);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO pendidikan (tingkat, indek, gapok1, kenaikan, maksimal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiii", $tingkat, $indek, $gapok1, $kenaikan, $maksimal);
        $stmt->execute();
    }
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendidikan");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// ambil data sesuai halaman
$sql  = "SELECT tingkat, indek, gapok1, kenaikan, maksimal 
         FROM pendidikan 
         ORDER BY indek 
         LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pendidikan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Pendidikan</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-master align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Tingkat</th>
              <th>Indeks</th>
              <th>Gapok Awal</th>
              <th>Kenaikan</th>
              <th>Maksimal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['tingkat']) ?></td>
              <td><?= htmlspecialchars($row['indek']) ?></td>
              <td><?= htmlspecialchars($row['gapok1']) ?></td>
              <td><?= htmlspecialchars($row['kenaikan']) ?></td>
              <td><?= htmlspecialchars($row['maksimal']) ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEdit"
                        data-tingkat="<?= htmlspecialchars($row['tingkat']) ?>"
                        data-indek="<?= htmlspecialchars($row['indek']) ?>"
                        data-gapok1="<?= htmlspecialchars($row['gapok1']) ?>"
                        data-kenaikan="<?= htmlspecialchars($row['kenaikan']) ?>"
                        data-maksimal="<?= htmlspecialchars($row['maksimal']) ?>">
                  ✏️ Edit
                </button>
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

  <?php include __DIR__ . '/../../layout/footer.php'; ?>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Pendidikan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Tingkat</label><input type="text" name="tingkat" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Indeks</label><input type="number" min="0" name="indek" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Gapok Awal</label><input type="number" min="0" name="gapok1" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Kenaikan</label><input type="number" min="0" name="kenaikan" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Maksimal</label><input type="number" min="0" name="maksimal" class="form-control" required></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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
          <h5 class="modal-title">Edit Pendidikan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tingkat</label>
            <input type="text" name="tingkat" id="editTingkat"
                   class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Indeks</label>
            <input type="number" min="0" name="indek" id="editIndek"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Gapok Awal</label>
            <input type="number" min="0" name="gapok1" id="editGapok1"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Kenaikan</label>
            <input type="number" min="0" name="kenaikan" id="editKenaikan"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Maksimal</label>
            <input type="number" min="0" name="maksimal" id="editMaksimal"
                   class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('editTingkat').value  = button.getAttribute('data-tingkat');
      document.getElementById('editIndek').value    = button.getAttribute('data-indek');
      document.getElementById('editGapok1').value   = button.getAttribute('data-gapok1');
      document.getElementById('editKenaikan').value = button.getAttribute('data-kenaikan');
      document.getElementById('editMaksimal').value = button.getAttribute('data-maksimal');
    });
  </script>
</body>
</html>
