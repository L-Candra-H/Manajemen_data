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

// pagination setup
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === PROSES SIMPAN / UPDATE / HAPUS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
        $kode  = $_POST['id_ruang'];
        $ruang = $_POST['nama_ruang'];

        $stmt = $conn->prepare("INSERT INTO ruang_audit_kepatuhan (id_ruang, nama_ruang) VALUES (?, ?)");
        $stmt->bind_param("ss", $kode, $ruang);
        $stmt->execute();
    }

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'update') {
        $kode  = $_POST['id_ruang'];
        $ruang = $_POST['nama_ruang'];

        $stmt = $conn->prepare("UPDATE ruang_audit_kepatuhan SET nama_ruang=? WHERE id_ruang=?");
        $stmt->bind_param("ss", $ruang, $kode);
        $stmt->execute();
    }

    header("Location: ?page=$page");
    exit;
}

if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM ruang_audit_kepatuhan WHERE id_ruang=?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();

    header("Location: ?page=$page");
    exit;
}

// ambil data dengan limit
$result = mysqli_query($conn, "SELECT * FROM ruang_audit_kepatuhan ORDER BY id_ruang ASC LIMIT $offset, $limit");

// hitung total data
$qTotal   = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ruang_audit_kepatuhan");
$dataTotal= mysqli_fetch_assoc($qTotal);
$total    = $dataTotal['total'];
$totalPages = ceil($total / $limit);

// === AMBIL DATA ===
$jmlData  = mysqli_num_rows($result);

// generate kode otomatis PKxxx
$qKode        = mysqli_query($conn, "SELECT MAX(id_ruang) AS kodeTerbesar FROM ruang_audit_kepatuhan");
$dataKode     = mysqli_fetch_assoc($qKode);
$kodeTerbesar = $dataKode['kodeTerbesar'];
$urutan = (int) substr($kodeTerbesar ?? '', 2, 3);
$urutan++;
$kodeBaru     = "RA" . sprintf("%03s", $urutan);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ruang / Unit Audit Kepatuhan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="audit.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Ruang / Unit Audit Kepatuhan</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <!-- Tabel -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-master_audit align-middle">
            <thead class="table-dark text-center">
            <tr>
              <th>ID Ruang</th>
              <th>Nama Ruang</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= htmlspecialchars($row['id_ruang']) ?></td>
                <td><?= htmlspecialchars($row['nama_ruang']) ?></td>
                <td class="text-center">
                  <!-- Tombol Edit -->
                  <button class="btn btn-warning btn-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEdit"
                          data-kode="<?= $row['id_ruang'] ?>"
                          data-lokasi="<?= $row['nama_ruang'] ?>">
                    ✏️ Edit
                  </button>
                  <!-- Tombol Hapus -->
                  <a href="?hapus=<?= urlencode($row['id_ruang']) ?>"
                     onclick="return confirm('Yakin hapus data ini?')" 
                     class="btn btn-danger btn-sm">
                    🗑️ Hapus
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Info Data -->
      <div class="mt-2 small text-muted">
        Jumlah Data : <?= $total ?>
      </div>

      <!-- Pagination -->
      <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
          </li>
          <?php
            $startPage = max(1, $page - 1);
            $endPage   = min($totalPages, $page + 1);
            for ($i = $startPage; $i <= $endPage; $i++):
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
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="tambah">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">➕ Tambah Ruang / Unit Kepatuhan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">ID Ruang</label>
              <input type="text" name="id_ruang" class="form-control bg-danger text-white" value="<?= $kodeBaru ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Nama Ruang</label>
              <input type="text" name="nama_ruang" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="update">
          <div class="modal-header bg-warning">
            <h5 class="modal-title">✏️ Edit Ruang / Unit Kepatuhan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">ID Ruang</label>
              <input type="text" name="id_ruang" id="editKode" class="form-control bg-danger text-white" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Nama Ruang</label>
              <input type="text" name="nama_ruang" id="editlokasi" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">🔄 Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const kodeSelect = document.getElementById("kodeSelect");
    const lokasiField = document.getElementById("lokasiField");

    kodeSelect.addEventListener("change", function() {
      const selectedOption = kodeSelect.options[kodeSelect.selectedIndex];
      const lokasi = selectedOption.getAttribute("data-lokasi") || "";
      lokasiField.value = lokasi;
    });
  });

    // Isi data ke modal edit
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var kode   = button.getAttribute('data-kode');
      var lokasi  = button.getAttribute('data-lokasi');
      modalEdit.querySelector('#editKode').value  = kode;
      modalEdit.querySelector('#editlokasi').value = lokasi;
    });
  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
