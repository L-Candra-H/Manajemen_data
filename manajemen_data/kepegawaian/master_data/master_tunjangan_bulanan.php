<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Master Tunjangan Bulanan.</div>";
    exit;
}

$conn = bukakoneksi();

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM master_tunjangan_bulanan");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// simpan jumlah data untuk ditampilkan
$jmlData = $totalData;

// ambil data sesuai halaman
$sql = "SELECT id, nama, tnj FROM master_tunjangan_bulanan ORDER BY nama LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);


// Handler Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'insert') {
    $nama = $_POST['nama'] ?? null;
    $tnj  = $_POST['tnj'] ?? null;

    if ($nama !== null && $tnj !== null) {
        $stmt = $conn->prepare("INSERT INTO master_tunjangan_bulanan (nama, tnj) VALUES (?, ?)");
        $stmt->bind_param("sd", $nama, $tnj);
        $stmt->execute();
    }
}

// Handler Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'edit') {
    $id   = $_POST['id'] ?? null;
    $nama = $_POST['nama'] ?? null;
    $tnj  = $_POST['tnj'] ?? null;

    // cek bukan null, biar 0 tetap lolos
    if ($id !== null && $nama !== null && $tnj !== null) {
        $stmt = $conn->prepare("UPDATE master_tunjangan_bulanan SET nama=?, tnj=? WHERE id=?");
        $stmt->bind_param("sdi", $nama, $tnj, $id);
        $stmt->execute();
    }
}

// Handler Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM master_tunjangan_bulanan WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: master_tunjangan_bulanan.php");
    exit;
}

// Ambil data
$sql = "SELECT id, nama, tnj FROM master_tunjangan_bulanan ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Master Tunjangan Bulanan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Master Tunjangan Bulanan</h5>
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
              <th>Nama Tunjangan</th>
              <th>Besar Tunjangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['nama']) ?></td>
              <td><?= "Rp. " . number_format($row['tnj'], 0, ',', '.') ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm"
                  data-bs-toggle="modal" data-bs-target="#modalEdit"
                  data-id="<?= $row['id'] ?>"
                  data-nama="<?= htmlspecialchars($row['nama']) ?>"
                  data-tnj="<?= $row['tnj'] ?>"
                  onclick="isiEditModal(this)">✏️ Edit</button>
                <a href="master_tunjangan_bulanan.php?hapus=<?= $row['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
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

  <?php include __DIR__ . '/../../layout/footer.php'; ?>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Tunjangan Bulanan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nama Tunjangan</label><input type="text" name="nama" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Besar Tunjangan</label><input type="number" step="0.01" name="tnj" class="form-control" required></div>
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
      <form method="post" class="modal-content">
        <input type="hidden" name="mode" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Edit Tunjangan Bulanan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nama Tunjangan</label><input type="text" name="nama" id="editNama" class="form-control bg-danger text-white fw-bold" readonly></div>
          <div class="mb-3"><label class="form-label">Besar Tunjangan</label><input type="number" step="0.01" name="tnj" id="editTnj" class="form-control" required></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  function isiEditModal(btn) {
    document.getElementById('editId').value   = btn.getAttribute('data-id');
    document.getElementById('editNama').value = btn.getAttribute('data-nama');
    document.getElementById('editTnj').value  = btn.getAttribute('data-tnj');
  }
  </script>
</body>
</html>
