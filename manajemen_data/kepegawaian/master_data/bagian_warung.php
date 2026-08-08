<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(0);
ini_set('display_errors', 0);

$role = $_POST['role'] ?? '';
$usere = $_POST['usere'] ?? '';
$passworde = $_POST['passworde'] ?? '';

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Bagian Warung.</div>";
    exit;
}

$conn = bukakoneksi();

// handler insert/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode   = $_POST['id'] ?? null;
    $persen = $_POST['persen'] ?? null;

    if (isset($_POST['mode']) && $_POST['mode'] === 'update') {
        $stmt = $conn->prepare("UPDATE pembagian_warung SET persen=? WHERE id=?");
        $stmt->bind_param("ss", $persen, $kode);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO pembagian_warung (id, persen) VALUES (?, ?)");
        $stmt->bind_param("ss", $kode, $persen);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM pembagian_warung WHERE id=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
    }
}

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pembagian_warung");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// ambil data sesuai halaman
$sql  = "SELECT i.id, d.nik, d.nama, i.persen
         FROM pembagian_warung i
         JOIN pegawai d ON i.id = d.id
         ORDER BY i.id
         LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// daftar pegawai yang belum punya pembagian_warung (untuk dropdown tambah)
$listDep = $conn->query("
    SELECT d.id, d.nik, d.nama
    FROM pegawai d
    LEFT JOIN pembagian_warung i ON d.id = i.id
    WHERE i.id IS NULL
      AND d.stts_aktif = 'AKTIF'
    ORDER BY d.nik ASC
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembagian Warung</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Pembagian Warung</h5>
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
              <th>NIK</th>
              <th>Nama</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['nik']) ?></td>
              <td><?= htmlspecialchars($row['nama']) ?></td>
              <td class="text-center">
                <form action="" method="post" style="display:inline">
                  <input type="hidden" name="mode" value="delete">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</button>
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
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
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
          <h5 class="modal-title">Tambah Pembagian Warung</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIK</label>
            <select name="id" id="nikSelect" class="form-select" required>
              <option value="">-- Pilih Pegawai --</option>
              <?php while($d = $listDep->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>" data-nama="<?= $d['nama'] ?>">
                  <?= $d['nik'] ?> - <?= $d['nama'] ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Pegawai</label>
            <input type="text" id="namaPegawai" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <!-- Hidden field persen -->
          <input type="hidden" name="persen" value="100">

          <script>
            document.getElementById('nikSelect').addEventListener('change', function() {
              var nama = this.options[this.selectedIndex].getAttribute('data-nama');
              document.getElementById('namaPegawai').value = nama || '';
            });
          </script>

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
          <h5 class="modal-title">Edit Pembagian Warung</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIK</label>
            <input type="text" id="editNik" class="form-control bg-danger text-white fw-bold" readonly>
            <input type="hidden" name="id" id="editKode">
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Pegawai</label>
            <input type="text" id="editNama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Porsi Warung (%)</label>
            <input type="number" name="persen" id="editPersen" class="form-control" required>
          </div>

          <script>
            var modalEdit = document.getElementById('modalEdit');
            modalEdit.addEventListener('show.bs.modal', function (event) {
              var button = event.relatedTarget;
              document.getElementById('editKode').value   = button.getAttribute('data-kode'); // id
              document.getElementById('editNik').value    = button.getAttribute('data-nik');  // nik
              document.getElementById('editNama').value   = button.getAttribute('data-nama');
              document.getElementById('editPersen').value = button.getAttribute('data-persen');
            });
          </script>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
