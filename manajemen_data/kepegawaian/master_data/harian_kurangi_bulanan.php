<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Harian-Bulanan.</div>";
    exit;
}

$conn = bukakoneksi();

// pagination setup
$limit = 6;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM harian_kurangi_bulanan");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// simpan jumlah data untuk ditampilkan
$jmlData = $totalData;

// Ambil data join untuk tampilkan nama, urut ASC by ID
$sql = "SELECT hkb.harian, hkb.bulanan, mth.nama AS nama_harian, mtb.nama AS nama_bulanan
        FROM harian_kurangi_bulanan hkb
        LEFT JOIN master_tunjangan_harian mth ON hkb.harian = mth.id
        LEFT JOIN master_tunjangan_bulanan mtb ON hkb.bulanan = mtb.id
        ORDER BY hkb.harian ASC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// Handler Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'insert') {
    $harian  = $_POST['harian'] ?? null;
    $bulanan = $_POST['bulanan'] ?? null;
    if ($harian !== null && $bulanan !== null) {
        $stmt = $conn->prepare("INSERT INTO harian_kurangi_bulanan (harian, bulanan) VALUES (?, ?)");
        $stmt->bind_param("ii", $harian, $bulanan);
        $stmt->execute();
    }
}

// Handler Hapus
if (isset($_GET['hapus'])) {
    $idHarian  = $_GET['hapus_harian'] ?? null;
    $idBulanan = $_GET['hapus_bulanan'] ?? null;
    if ($idHarian && $idBulanan) {
        $stmt = $conn->prepare("DELETE FROM harian_kurangi_bulanan WHERE harian=? AND bulanan=?");
        $stmt->bind_param("ii", $idHarian, $idBulanan);
        $stmt->execute();
        header("Location: harian_kurangi_bulanan.php");
        exit;
    }
}

// Ambil data join untuk tampilkan nama
$sql = "SELECT hkb.harian, hkb.bulanan, mth.nama AS nama_harian, mtb.nama AS nama_bulanan
        FROM harian_kurangi_bulanan hkb
        LEFT JOIN master_tunjangan_harian mth ON hkb.harian = mth.id
        LEFT JOIN master_tunjangan_bulanan mtb ON hkb.bulanan = mtb.id
        ORDER BY hkb.harian";
$result = mysqli_query($conn, $sql);

// Ambil data dropdown
$harianList  = mysqli_query($conn, "SELECT id, nama FROM master_tunjangan_harian ORDER BY nama");
$bulananList = mysqli_query($conn, "SELECT id, nama FROM master_tunjangan_bulanan ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Harian-Bulanan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Harian - Bulanan</h5>
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
              <th>Tunjangan Harian</th>
              <th>Tunjangan Bulanan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['nama_harian']) ?></td>
              <td><?= htmlspecialchars($row['nama_bulanan']) ?></td>
              <td class="text-center">
                <a href="harian_kurangi_bulanan.php?hapus=1&hapus_harian=<?= $row['harian'] ?>&hapus_bulanan=<?= $row['bulanan'] ?>"
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
          <h5 class="modal-title">Tambah Harian - Bulanan</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tunjangan Harian</label>
            <select name="harian" class="form-select" required>
              <option value="">-- Pilih Harian --</option>
              <?php while($h = mysqli_fetch_assoc($harianList)): ?>
                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nama']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tunjangan Bulanan</label>
            <select name="bulanan" class="form-select" required>
              <option value="">-- Pilih Bulanan --</option>
              <?php while($b = mysqli_fetch_assoc($bulananList)): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
              <?php endwhile; ?>
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
