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
$result = mysqli_query($conn, "SELECT * FROM k3rs_lokasi_kejadian ORDER BY kode_lokasi ASC LIMIT $start, $limit");

// hitung total data
$qTotal   = mysqli_query($conn, "SELECT COUNT(*) AS total FROM k3rs_lokasi_kejadian");
$dataTotal= mysqli_fetch_assoc($qTotal);
$total    = $dataTotal['total'];
$totalPages = ceil($total / $limit);

// === PROSES SIMPAN / UPDATE / HAPUS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
        $kode   = $_POST['kode_lokasi'];
        $lokasi = $_POST['lokasi_kejadian'];

        $stmt = $conn->prepare("INSERT INTO k3rs_lokasi_kejadian (kode_lokasi, lokasi_kejadian) VALUES (?, ?)");
        $stmt->bind_param("ss", $kode, $lokasi);
        $stmt->execute();
    }

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'update') {
        $kode   = $_POST['kode_lokasi'];
        $lokasi = $_POST['lokasi_kejadian'];

        $stmt = $conn->prepare("UPDATE k3rs_lokasi_kejadian SET lokasi_kejadian=? WHERE kode_lokasi=?");
        $stmt->bind_param("ss", $lokasi, $kode);
        $stmt->execute();
    }
}

if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];

    $stmt = $conn->prepare("DELETE FROM k3rs_lokasi_kejadian WHERE kode_lokasi=?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
}

// === AMBIL DATA ===
$result   = mysqli_query($conn, "SELECT * FROM k3rs_lokasi_kejadian ORDER BY kode_lokasi ASC");
$jmlData  = mysqli_num_rows($result);

// generate kode otomatis PKxxx
$qKode        = mysqli_query($conn, "SELECT MAX(kode_lokasi) AS kodeTerbesar FROM k3rs_lokasi_kejadian");
$dataKode     = mysqli_fetch_assoc($qKode);
$kodeTerbesar = $dataKode['kodeTerbesar'];
$urutan       = (int) substr($kodeTerbesar, 2, 3);
$urutan++;
$kodeBaru     = "LK" . sprintf("%03s", $urutan);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lokasi Kejadian K3</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="k3.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Lokasi Kejadian K3</h5>
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
              <th>Kode</th>
              <th>Lokasi Kejadian</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= htmlspecialchars($row['kode_lokasi']) ?></td>
                <td><?= htmlspecialchars($row['lokasi_kejadian']) ?></td>
                <td class="text-center">
                  <!-- Tombol Edit -->
                  <button class="btn btn-warning btn-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEdit"
                          data-kode="<?= $row['kode_lokasi'] ?>"
                          data-lokasi="<?= $row['lokasi_kejadian'] ?>">
                    ✏️ Edit
                  </button>
                  <!-- Tombol Hapus -->
                  <a href="?hapus=<?= urlencode($row['kode_lokasi']) ?>"
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
            <h5 class="modal-title">➕ Tambah Lokasi Kejadian</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Kode</label>
              <input type="text" name="kode_lokasi" class="form-control" value="<?= $kodeBaru ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Lokasi Kejadian</label>
              <input type="text" name="lokasi_kejadian" class="form-control" required>
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
            <h5 class="modal-title">✏️ Edit Lokasi Kejadian</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Kode</label>
              <input type="text" name="kode_lokasi" id="editKode" class="form-control bg-danger" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Lokasi Kejadian</label>
              <input type="text" name="lokasi_kejadian" id="editlokasi" class="form-control" required>
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
      var lokasi  = button.getAttribute('data-lokasi');
      modalEdit.querySelector('#editKode').value  = kode;
      modalEdit.querySelector('#editlokasi').value = lokasi;
    });
  </script>
</body>
</html>
