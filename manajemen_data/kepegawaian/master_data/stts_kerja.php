<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Status Kerja.</div>";
    exit;
}

$conn = bukakoneksi();

// handler insert/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stts    = $_POST['stts'] ?? null;
    $ktg     = $_POST['ktg'] ?? null;
    $indek   = (isset($_POST['indek']) && is_numeric($_POST['indek'])) ? (int)$_POST['indek'] : 0;
    $hakcuti = (isset($_POST['hakcuti']) && is_numeric($_POST['hakcuti'])) ? (int)$_POST['hakcuti'] : 0;

    if (isset($_POST['mode']) && $_POST['mode'] === 'update') {
        $stmt = $conn->prepare("UPDATE stts_kerja SET ktg=?, indek=?, hakcuti=? WHERE stts=?");
        $stmt->bind_param("siis", $ktg, $indek, $hakcuti, $stts);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO stts_kerja (stts, ktg, indek, hakcuti) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $stts, $ktg, $indek, $hakcuti);
        $stmt->execute();
    }
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM stts_kerja");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// ambil data sesuai halaman
$sql  = "SELECT stts, ktg, indek, hakcuti 
         FROM stts_kerja 
         ORDER BY stts 
         LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Kerja</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Status Kerja</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body">
        <table class="table table-bordered table-striped">
          <thead class="table-dark text-center">
            <tr>
              <th>Status</th>
              <th>Keterangan</th>
              <th>Indeks</th>
              <th>Hak Cuti</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['stts']) ?></td>
              <td><?= htmlspecialchars($row['ktg']) ?></td>
              <td><?= htmlspecialchars($row['indek']) ?></td>
              <td><?= htmlspecialchars($row['hakcuti']) ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEdit"
                        data-stts="<?= htmlspecialchars($row['stts']) ?>"
                        data-ktg="<?= htmlspecialchars($row['ktg']) ?>"
                        data-indek="<?= htmlspecialchars($row['indek']) ?>"
                        data-hakcuti="<?= htmlspecialchars($row['hakcuti']) ?>">
                  ✏️ Edit
                </button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <nav>
          <ul class="pagination justify-content-center">
            <?php if($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>">« Prev</a></li>
            <?php endif; ?>

            <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $start + 2);
              for($i = $start; $i <= $end; $i++):
            ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>">Next »</a></li>
            <?php endif; ?>
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
          <h5 class="modal-title">Tambah Status Kerja</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Status</label><input type="text" name="stts" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Keterangan</label><input type="text" name="ktg" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Indeks</label><input type="number" min="0" name="indek" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Hak Cuti</label><input type="number" min="0" name="hakcuti" class="form-control" required></div>
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
          <h5 class="modal-title">Edit Status Kerja</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Status</label>
            <input type="text" name="stts" id="editStts"
                   class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3"><label class="form-label">Keterangan</label>
            <input type="text" name="ktg" id="editKtg" class="form-control" required>
          </div>
          <div class="mb-3"><label class="form-label">Indeks</label>
            <input type="number" min="0" name="indek" id="editIndek" class="form-control" required>
          </div>
          <div class="mb-3"><label class="form-label">Hak Cuti</label>
            <input type="number" min="0" name="hakcuti" id="editHakcuti" class="form-control" required>
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
      document.getElementById('editStts').value    = button.getAttribute('data-stts');
      document.getElementById('editKtg').value     = button.getAttribute('data-ktg');
      document.getElementById('editIndek').value   = button.getAttribute('data-indek');
      document.getElementById('editHakcuti').value = button.getAttribute('data-hakcuti');
    });
  </script>
</body>
</html>
