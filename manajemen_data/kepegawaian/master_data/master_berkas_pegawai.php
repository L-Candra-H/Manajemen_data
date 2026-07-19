<?php
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

if(!isset($_SESSION['user_login'])) {
    header("Location: ../../login.php");
    exit;
}

// tampilkan error agar mudah debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// cek hak akses
if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Master Berkas Pegawai.</div>";
    exit;
}

// Ambil no_urut terakhir per kategori
$lastUrutData = [];
$res = mysqli_query($conn, "SELECT kategori, MAX(no_urut) AS max_urut, MAX(kode) AS kode_terakhir 
                            FROM master_berkas_pegawai 
                            GROUP BY kategori");
while($row = mysqli_fetch_assoc($res)) {
    $lastUrutData[] = $row;
}

$conn = bukakoneksi();

// handler insert/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode       = $_POST['kode'] ?? null;
    $kategori   = $_POST['kategori'] ?? null;
    $nama_berkas= $_POST['nama_berkas'] ?? null;
    $no_urut    = (isset($_POST['no_urut']) && is_numeric($_POST['no_urut'])) ? (int)$_POST['no_urut'] : 0;

    if (isset($_POST['mode']) && $_POST['mode'] === 'update') {
        $stmt = $conn->prepare("UPDATE master_berkas_pegawai SET kategori=?, nama_berkas=?, no_urut=? WHERE kode=?");
        $stmt->bind_param("ssis", $kategori, $nama_berkas, $no_urut, $kode);
        $stmt->execute();
    } elseif (isset($_POST['mode']) && $_POST['mode'] === 'insert') {
        $stmt = $conn->prepare("INSERT INTO master_berkas_pegawai (kode, kategori, nama_berkas, no_urut) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $kode, $kategori, $nama_berkas, $no_urut);
        $stmt->execute();
    }
}

// pagination setup
$limit = 7;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM master_berkas_pegawai");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = $totalRow['total'];
$totalPages  = ceil($totalData / $limit);

// ambil data sesuai halaman
$sql  = "SELECT kode, kategori, nama_berkas, no_urut 
         FROM master_berkas_pegawai 
         ORDER BY kategori, no_urut 
         LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// filter kategori
$filter = $_GET['filter'] ?? '';

// hitung total
if ($filter === '') {
    // default kosong
    $countSql = "SELECT COUNT(*) AS total FROM master_berkas_pegawai WHERE 1=0";
} elseif ($filter === 'ALL') {
    $countSql = "SELECT COUNT(*) AS total FROM master_berkas_pegawai";
} else {
    $countSql = "SELECT COUNT(*) AS total FROM master_berkas_pegawai WHERE kategori='" . mysqli_real_escape_string($conn, $filter) . "'";
}
$countResult = mysqli_query($conn, $countSql);
$totalRows   = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages  = $totalRows > 0 ? ceil($totalRows/$limit) : 1;

// ambil data sesuai filter
if ($filter === '') {
    $sql = "SELECT kode,kategori,nama_berkas,no_urut FROM master_berkas_pegawai WHERE 1=0";
} elseif ($filter === 'ALL') {
    $sql = "SELECT kode,kategori,nama_berkas,no_urut FROM master_berkas_pegawai ORDER BY kategori,no_urut LIMIT $limit OFFSET $offset";
} else {
    $sql = "SELECT kode,kategori,nama_berkas,no_urut FROM master_berkas_pegawai WHERE kategori='" . mysqli_real_escape_string($conn,$filter) . "' ORDER BY no_urut LIMIT $limit OFFSET $offset";
}
$result = mysqli_query($conn,$sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Master Berkas Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="master.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Master Berkas Pegawai</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>
      <div class="card-body p-3">
        <form method="get" class="mb-3">
          <label for="filter" class="form-label">Filter Kategori :</label>
          <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Kategori --</option>
            <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <option value="Tenaga klinis Dokter Umum" <?= $filter==='Tenaga klinis Dokter Umum'?'selected':'' ?>>Tenaga klinis Dokter Umum</option>
            <option value="Tenaga klinis Dokter Spesialis" <?= $filter==='Tenaga klinis Dokter Spesialis'?'selected':'' ?>>Tenaga klinis Dokter Spesialis</option>
            <option value="Tenaga klinis Perawat dan Bidan" <?= $filter==='Tenaga klinis Perawat dan Bidan'?'selected':'' ?>>Tenaga klinis Perawat dan Bidan</option>
            <option value="Tenaga klinis Profesi Lain" <?= $filter==='Tenaga klinis Profesi Lain'?'selected':'' ?>>Tenaga klinis Profesi Lain</option>
            <option value="Tenaga Non Klinis" <?= $filter==='Tenaga Non Klinis'?'selected':'' ?>>Tenaga Non Klinis</option>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-master_berkas align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Kode</th>
              <th>Kategori</th>
              <th>Nama Berkas</th>
              <th>No. Urut</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if(mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">Silakan pilih kategori untuk menampilkan data</td></tr>
          <?php else: while($row=mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['kode']) ?></td>
              <td><?= htmlspecialchars($row['kategori']) ?></td>
              <td><?= htmlspecialchars($row['nama_berkas']) ?></td>
              <td><?= htmlspecialchars($row['no_urut']) ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEdit"
                        data-kode="<?= htmlspecialchars($row['kode']) ?>"
                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
                        data-nama="<?= htmlspecialchars($row['nama_berkas']) ?>"
                        data-urut="<?= htmlspecialchars($row['no_urut']) ?>">
                  ✏ Edit
                </button>
              </td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>

        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&filter=<?= urlencode($filter) ?>">« Prev</a>
            </li>

            <!-- Nomor Halaman (batasi 3 sekitar aktif) -->
            <?php
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++):
            ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&filter=<?= urlencode($filter) ?>">Next »</a>
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
          <h5 class="modal-title">Tambah Berkas Pegawai</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Kode</label>
            <input type="text" name="kode" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <option value="Tenaga klinis Dokter Umum">Tenaga klinis Dokter Umum</option>
              <option value="Tenaga klinis Dokter Spesialis">Tenaga klinis Dokter Spesialis</option>
              <option value="Tenaga klinis Perawat dan Bidan">Tenaga klinis Perawat dan Bidan</option>
              <option value="Tenaga klinis Profesi Lain">Tenaga klinis Profesi Lain</option>
              <option value="Tenaga Non Klinis">Tenaga Non Klinis</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Berkas</label>
            <input type="text" name="nama_berkas" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">No. Urut</label>
            <input type="number" name="no_urut" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Info Urutan Terakhir</label>
            <div class="alert alert-info p-2">
              <ul id="infoUrutList" class="mb-0">
                <?php foreach($lastUrutData as $info): ?>
                  <li>
                    <strong><?= htmlspecialchars($info['kategori']) ?></strong> 
                    → Kode terakhir: <span class="text-danger"><?= htmlspecialchars($info['kode_terakhir']) ?></span>, 
                    No. Urut terakhir: <span class="text-primary"><?= htmlspecialchars($info['max_urut']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
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
          <h5 class="modal-title">Edit Berkas Pegawai</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Kode</label>
            <input type="text" name="kode" id="editKode" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3"><label class="form-label">Kategori</label>
            <select name="kategori" id="editKategori" class="form-select" required>
              <option value="Tenaga klinis Dokter Umum">Tenaga klinis Dokter Umum</option>
              <option value="Tenaga klinis Dokter Spesialis">Tenaga klinis Dokter Spesialis</option>
              <option value="Tenaga klinis Perawat dan Bidan">Tenaga klinis Perawat dan Bidan</option>
              <option value="Tenaga klinis Profesi Lain">Tenaga klinis Profesi Lain</option>
              <option value="Tenaga Non Klinis">Tenaga Non Klinis</option>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Nama Berkas</label>
            <input type="text" name="nama_berkas" id="editNama" class="form-control" required>
          </div>
          <div class="mb-3"><label class="form-label">No. Urut</label>
            <input type="number" name="no_urut" id="editUrut" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Script Bootstrap & Modal -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    var modalEdit = document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('editKode').value     = button.getAttribute('data-kode');
      document.getElementById('editKategori').value = button.getAttribute('data-kategori');
      document.getElementById('editNama').value     = button.getAttribute('data-nama');
      document.getElementById('editUrut').value     = button.getAttribute('data-urut');
    });
  </script>

</body>
</html>
