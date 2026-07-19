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

$conn = bukakoneksi();

$nik = $_GET['nik'] ?? '';
// ambil data pegawai
$sqlPegawai = "SELECT nik, nama, id FROM pegawai WHERE nik = '$nik'";
$resPegawai = mysqli_query($conn, $sqlPegawai);
$pegawai = mysqli_fetch_assoc($resPegawai);

// proses insert ambil dankes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanggal'])) {
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $ktg       = mysqli_real_escape_string($conn, $_POST['ktg']);
    $dankes    = mysqli_real_escape_string($conn, $_POST['dankes']);
    $idPegawai = $pegawai['id'];

    $sqlInsert = "INSERT INTO ambil_dankes (id, tanggal, ktg, dankes) 
                  VALUES ('$idPegawai', '$tanggal', '$ktg', '$dankes')";
    mysqli_query($conn, $sqlInsert);
}

// pagination riwayat
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ambil_dankes WHERE id='{$pegawai['id']}'");
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = ceil($totalRows / $limit);

// ambil riwayat ambil_dankes
$sqlRiwayat = "SELECT tanggal, ktg, dankes, id FROM ambil_dankes 
               WHERE id = '{$pegawai['id']}' 
               ORDER BY tanggal DESC
               LIMIT $limit OFFSET $offset";
$resRiwayat = mysqli_query($conn, $sqlRiwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ambil Dankes</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase text-center flex-grow-1">Riwayat Ambil Dana Kesehatan - Atas Nama : <?= htmlspecialchars($pegawai['nik'] ?? '') ?> - <?= htmlspecialchars($pegawai['nama'] ?? '') ?></h5>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal">➕ Tambah</button>
            <a href="index_pegawai.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
        </div>
        </div>

        <!-- BODY: tabel utama -->
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-ambil-dankes align-middle">
              <thead class="table-dark text-center">
                <tr>
                  <th>Tanggal</th>
                  <th>Keterangan</th>
                  <th>Dankes Diambil</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if(mysqli_num_rows($resRiwayat) == 0): ?>
                  <tr><td colspan="4" class="text-muted">Belum ada data</td></tr>
                <?php else: ?>
                  <?php while($row = mysqli_fetch_assoc($resRiwayat)): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['tanggal']) ?></td>
                      <td><?= htmlspecialchars($row['ktg']) ?></td>
                      <td><?= "Rp. " . number_format($row['dankes'], 0, ',', '.') ?></td>
                      <td>
                        <a href="hapus_dankes.php?id=<?= $row['id'] ?>&nik=<?= $nik ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages >= 1): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <!-- Tombol Prev -->
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?nik=<?= $nik ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
              </li>

              <!-- Nomor Halaman (hanya 3: aktif ±1) -->
              <?php
               $start = max(1, $page - 1);
                $end   = min($totalPages, $page + 1);
                for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                 <a class="page-link" href="?nik=<?= $nik ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <!-- Tombol Next -->
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?nik=<?= $nik ?>&page=<?= min($totalPages, $page + 1) ?>">Next »</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="tambahModalLabel">Tambah Ambil Dankes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Tanggal Ambil</label>
              <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Keterangan</label>
              <input type="text" name="ktg" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Dankes Diambil</label>
              <div class="input-group">
                <span class="input-group-text">Rp.</span>
                <input type="number" name="dankes" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Simpan</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
