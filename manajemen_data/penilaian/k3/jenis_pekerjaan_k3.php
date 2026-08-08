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

// === PAGINATION ===
$limit = 5; // jumlah data per halaman
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// ambil data dengan limit
$result = mysqli_query($conn, "SELECT * FROM k3rs_jenis_pekerjaan ORDER BY kode_pekerjaan ASC LIMIT $start, $limit");

// hitung total data
$qTotal   = mysqli_query($conn, "SELECT COUNT(*) AS total FROM k3rs_jenis_pekerjaan");
$dataTotal= mysqli_fetch_assoc($qTotal);
$total    = $dataTotal['total'];
$totalPages = ceil($total / $limit);

// === PROSES SIMPAN / UPDATE / HAPUS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
        $kode      = $_POST['kode_pekerjaan'];
        $pekerjaan = $_POST['jenis_pekerjaan'];

        $stmt = $conn->prepare("INSERT INTO k3rs_jenis_pekerjaan (kode_pekerjaan, jenis_pekerjaan) VALUES (?, ?)");
        $stmt->bind_param("ss", $kode, $pekerjaan);
        $stmt->execute();
    }

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'update') {
        $kode      = $_POST['kode_pekerjaan'];
        $pekerjaan = $_POST['jenis_pekerjaan'];

        $stmt = $conn->prepare("UPDATE k3rs_jenis_pekerjaan SET jenis_pekerjaan=? WHERE kode_pekerjaan=?");
        $stmt->bind_param("ss", $pekerjaan, $kode);
        $stmt->execute();
    }
}

if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];

    $stmt = $conn->prepare("DELETE FROM k3rs_jenis_pekerjaan WHERE kode_pekerjaan=?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
}

// === AMBIL DATA ===
$result   = mysqli_query($conn, "SELECT * FROM k3rs_jenis_pekerjaan ORDER BY kode_pekerjaan ASC");
$jmlData  = mysqli_num_rows($result);

// generate kode otomatis PKxxx
$qKode        = mysqli_query($conn, "SELECT MAX(kode_pekerjaan) AS kodeTerbesar FROM k3rs_jenis_pekerjaan");
$dataKode     = mysqli_fetch_assoc($qKode);
$kodeTerbesar = $dataKode['kodeTerbesar'];
$urutan       = (int) substr($kodeTerbesar, 2, 3);
$urutan++;
$kodeBaru     = "PK" . sprintf("%03s", $urutan);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Jenis Pekerjaan K3</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Jenis Pekerjaan K3</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <!-- Tabel -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-master_k3 align-middle">
            <thead class="table-dark text-center">
            <tr>
              <th>Kode Jenis</th>
              <th>Jenis Pekerjaan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= htmlspecialchars($row['kode_pekerjaan']) ?></td>
                <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                <td class="text-center">
                  <!-- Tombol Edit -->
                  <button class="btn btn-warning btn-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEdit"
                          data-kode="<?= $row['kode_pekerjaan'] ?>"
                          data-pekerjaan="<?= $row['jenis_pekerjaan'] ?>">
                    ✏️ Edit
                  </button>
                  <!-- Tombol Hapus -->
                  <a href="?hapus=<?= urlencode($row['kode_pekerjaan']) ?>"
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
        Jumlah Data : <?= $jmlData ?>
      </div>

      <!-- Pagination -->
      <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
          <!-- Prev -->
          <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= ($page > 1) ? $page-1 : 1 ?>">Prev</a>
          </li>

          <!-- Nomor Halaman -->
          <?php for ($i=1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <!-- Next -->
          <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= ($page < $totalPages) ? $page+1 : $totalPages ?>">Next</a>
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
            <h5 class="modal-title">➕ Tambah Jenis Pekerjaan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Kode</label>
              <input type="text" name="kode_pekerjaan" class="form-control" value="<?= $kodeBaru ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Jenis Pekerjaan</label>
              <input type="text" name="jenis_pekerjaan" class="form-control" required>
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
            <h5 class="modal-title">✏️ Edit Jenis Pekerjaan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Kode</label>
              <input type="text" name="kode_pekerjaan" id="editKode" class="form-control bg-danger" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Jenis Pekerjaan</label>
              <input type="text" name="jenis_pekerjaan" id="editpekerjaan" class="form-control" required>
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

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Isi data ke modal edit
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var kode   = button.getAttribute('data-kode');
      var pekerjaan  = button.getAttribute('data-pekerjaan');
      modalEdit.querySelector('#editKode').value  = kode;
      modalEdit.querySelector('#editpekerjaan').value = pekerjaan;
    });
  </script>
</body>
</html>
