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

// proses tambah berkas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $nik        = $_POST['nik'] ?? '';
    $tgl_uploud = $_POST['tgl_uploud'] ?? date('Y-m-d');
    $kode_berkas= $_POST['kode_berkas'] ?? '';
    $filePath   = "";

    if (!empty($_FILES['file_berkas']['name'])) {
        $fileName   = basename($_FILES['file_berkas']['name']);
        $targetDir = __DIR__ . "/../../webapps/penggajian/pages/berkaspegawai/berkas/";
        $targetFile = $targetDir . $fileName;

        // pastikan folder ada
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true)) {
                die("Folder tujuan tidak bisa dibuat. Periksa permission.");
            }
        }

        // pindahkan file
        if (move_uploaded_file($_FILES['file_berkas']['tmp_name'], $targetFile)) {
            // path relatif untuk DB
            $filePath = "pages/berkaspegawai/berkas/" . $fileName;
        } else {
            die("Upload berkas gagal, data tidak disimpan.");
        }
    }

    // hanya insert kalau upload sukses
    if ($filePath !== "") {
        $stmt = $conn->prepare("INSERT INTO berkas_pegawai (nik, tgl_uploud, kode_berkas, berkas) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nik, $tgl_uploud, $kode_berkas, $filePath);
        $stmt->execute();
        $stmt->close();
    }
}

// proses hapus
if (isset($_GET['hapus'])) {
    list($nik, $kode, $tgl) = explode('|', $_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM berkas_pegawai WHERE nik=? AND kode_berkas=? AND tgl_uploud=?");
    $stmt->bind_param("sss", $nik, $kode, $tgl);
    $stmt->execute();
    $stmt->close();
}

// pagination
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

// filter kategori
$filter = $_GET['filter'] ?? '';

// hitung total
if ($filter === '') {
    $countSql = "SELECT COUNT(*) AS total FROM berkas_pegawai WHERE 1=0";
} elseif ($filter === 'ALL') {
    $countSql = "SELECT COUNT(*) AS total FROM berkas_pegawai b INNER JOIN master_berkas_pegawai m ON m.kode=b.kode_berkas";
} else {
    $countSql = "SELECT COUNT(*) AS total 
                 FROM berkas_pegawai b 
                 INNER JOIN master_berkas_pegawai m ON m.kode=b.kode_berkas
                 WHERE m.kategori='" . mysqli_real_escape_string($conn, $filter) . "'";
}
$countResult = mysqli_query($conn, $countSql);
$totalRows   = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages  = $totalRows > 0 ? ceil($totalRows / $limit) : 1;

// dropdown pegawai untuk tambah berkas
$pegawaiArr = [];
$res = mysqli_query($conn, "
    SELECT 
        p.nik,
        p.nama,
        p.jk,
        p.pendidikan,
        p.jbtn,
        p.bidang,
        d.nama AS departemen,       -- ambil nama departemen
        j.nama AS jnj_jabatan       -- ambil nama jenjang jabatan
    FROM pegawai p
    LEFT JOIN departemen d ON p.departemen = d.dep_id
    LEFT JOIN jnj_jabatan j ON p.jnj_jabatan = j.kode
    WHERE p.stts_aktif IN ('AKTIF','CUTI','TENAGA LUAR')
    ORDER BY p.nik
");
while($row = mysqli_fetch_assoc($res)) {
    $pegawaiArr[] = $row;
}

// dropdown kategori berkas
$kategoriArr = [];
$res = mysqli_query($conn,"SELECT DISTINCT kategori FROM master_berkas_pegawai ORDER BY kategori");
while($row = mysqli_fetch_assoc($res)) {
    $kategoriArr[] = $row['kategori'];
}

// dropdown nama berkas
$berkasArr = [];
$res = mysqli_query($conn,"SELECT kode,nama_berkas,kategori, no_urut FROM master_berkas_pegawai ORDER BY kategori,no_urut");
while($row = mysqli_fetch_assoc($res)) {
    $berkasArr[] = $row;
}

// ambil data
if ($filter === '') {
    $sql = "SELECT b.nik,p.nama,b.tgl_uploud,m.kategori,m.nama_berkas,b.berkas,b.kode_berkas
            FROM berkas_pegawai b
            INNER JOIN pegawai p ON p.nik=b.nik
            INNER JOIN master_berkas_pegawai m ON m.kode=b.kode_berkas
            WHERE 1=0";
} elseif ($filter === 'ALL') {
    $sql = "SELECT b.nik,p.nama,b.tgl_uploud,m.kategori,m.nama_berkas,b.berkas,b.kode_berkas
            FROM berkas_pegawai b
            INNER JOIN pegawai p ON p.nik=b.nik
            INNER JOIN master_berkas_pegawai m ON m.kode=b.kode_berkas
            WHERE p.stts_aktif IN ('AKTIF','CUTI','TENAGA LUAR')
            ORDER BY b.tgl_uploud ASC LIMIT $limit OFFSET $offset";
} else {
    $sql = "SELECT b.nik,p.nama,b.tgl_uploud,m.kategori,m.nama_berkas,b.berkas,b.kode_berkas
            FROM berkas_pegawai b
            INNER JOIN pegawai p ON p.nik=b.nik
            INNER JOIN master_berkas_pegawai m ON m.kode=b.kode_berkas
            WHERE m.kategori='" . mysqli_real_escape_string($conn, $filter) . "'
              AND p.stts_aktif IN ('AKTIF','CUTI','TENAGA LUAR')
            ORDER BY b.tgl_uploud ASC LIMIT $limit OFFSET $offset";
}

$result = mysqli_query($conn, $sql);

// dropdown kategori
$kategoriRes = mysqli_query($conn, "SELECT DISTINCT kategori FROM master_berkas_pegawai ORDER BY kategori");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Berkas Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  
<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase text-center flex-grow-1">Berkas Pegawai</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
        <a href="../index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
      </div>
    </div>
    <div class="card-body">
      <!-- Filter -->
      <form method="get" class="mb-3">
        <label for="filter" class="form-label">Filter Kategori Berkas:</label>
        <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
          <option value="ALL" <?= $filter==='ALL'?'selected':'' ?>>-- Pilih Semua --</option>
          <?php while($kat=mysqli_fetch_assoc($kategoriRes)): ?>
            <option value="<?= htmlspecialchars($kat['kategori']) ?>" <?= $filter==$kat['kategori']?'selected':'' ?>>
              <?= htmlspecialchars($kat['kategori']) ?>
            </option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
      </form>

      <!-- Tabel -->
      <div class="table-wrapper">
        <table class="table table-bordered table-striped align-middle table-berkas">
          <thead class="table-dark text-center">
            <tr>
              <th>No.</th>
              <th>NIK</th>
              <th>Nama Pegawai</th>
              <th>Tgl Upload</th>
              <th>Kategori Berkas</th>
              <th>Nama Berkas</th>
              <th>File Berkas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(mysqli_num_rows($result)===0): ?>
              <tr><td colspan="8" class="text-center text-muted">Silakan pilih kategori berkas untuk menampilkan data</td></tr>
            <?php else: $no=$offset+1; while($row=mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nik']) ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['tgl_uploud']) ?></td>
                <td><?= htmlspecialchars($row['kategori']) ?></td>
                <td><?= htmlspecialchars($row['nama_berkas']) ?></td>
                <td class="text-center">
                  <?php if (!empty($row['berkas'])): ?>
                    <?php 
                      $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                      $path = "/" . $appFolder . "/webapps/penggajian/" . htmlspecialchars($row['berkas']);
                    ?>
                    <a href="<?= $path ?>" target="_blank" class="btn btn-sm btn-info">📂 Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">No File</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?hapus=<?= urlencode($row['nik'].'|'.$row['kode_berkas'].'|'.$row['tgl_uploud']) ?>" 
                     class="btn btn-danger btn-sm" 
                     onclick="return confirm('Hapus data ini?')">🗑️ Hapus</a>
                </td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if($totalPages>=1): ?>
      <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="?page=<?= max(1,$page-1) ?>&filter=<?= urlencode($filter) ?>">Prev</a>
          </li>
          <?php
            $start=max(1,$page-1);
            $end=min($totalPages,$page+1);
            for($i=$start;$i<=$end;$i++):
          ?>
            <li class="page-item <?= $i==$page?'active':'' ?>">
              <a class="page-link" href="?page=<?= $i ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
            <a class="page-link" href="?page=<?= min($totalPages,$page+1) ?>&filter=<?= urlencode($filter) ?>">Next</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="mode" value="insert">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Berkas Pegawai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Pegawai -->
        <div class="mb-3">
          <label class="form-label">Pegawai</label>
          <select name="nik" id="nikSelect" class="form-select" required>
            <option value="">-- Pilih Pegawai --</option>
            <?php foreach ($pegawaiArr as $peg): ?>
              <option value="<?= htmlspecialchars($peg['nik']) ?>"
                      data-jk="<?= htmlspecialchars($peg['jk']) ?>"
                      data-pendidikan="<?= htmlspecialchars($peg['pendidikan']) ?>"
                      data-jabatan="<?= htmlspecialchars($peg['jbtn']) ?>"
                      data-bidang="<?= htmlspecialchars($peg['bidang']) ?>"
                      data-departemen="<?= htmlspecialchars($peg['departemen']) ?>"
                      data-jenjang="<?= htmlspecialchars($peg['jnj_jabatan']) ?>">
                <?= htmlspecialchars($peg['nik']) ?> - <?= htmlspecialchars($peg['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Informasi Pegawai (readonly) -->
        <div class="mb-3"><label class="form-label">Jenis Kelamin</label>
          <input type="text" id="jkField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3"><label class="form-label">Pendidikan</label>
          <input type="text" id="pendField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3"><label class="form-label">Jabatan</label>
          <input type="text" id="jabField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3"><label class="form-label">Bidang</label>
          <input type="text" id="bidField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3"><label class="form-label">Departemen</label>
          <input type="text" id="depField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3"><label class="form-label">Jenjang Jabatan</label>
          <input type="text" id="jenjField" class="form-control bg-danger text-white fw-bold" readonly>
        </div>

        <!-- Kategori Berkas -->
        <div class="mb-3">
          <label class="form-label">Kategori Berkas</label>
          <select id="kategoriSelect" class="form-select" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach($kategoriArr as $kat): ?>
              <option value="<?= htmlspecialchars($kat) ?>"><?= htmlspecialchars($kat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Nama Berkas -->
        <div class="mb-3">
          <label class="form-label">Nama Berkas</label>
          <select name="kode_berkas" id="berkasSelect" class="form-select" required>
            <option value="">-- Pilih Berkas --</option>
          </select>
        </div>

        <!-- File Berkas -->
        <div class="mb-3">
          <label class="form-label">File Berkas (PDF/Gambar)</label>
          <input type="file" name="file_berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>

        <!-- Tanggal Upload -->
        <div class="mb-3">
          <label class="form-label">Tanggal Upload</label>
          <input type="date" name="tgl_uploud" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('nikSelect').addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  document.getElementById('jkField').value   = opt.getAttribute('data-jk');
  document.getElementById('pendField').value = opt.getAttribute('data-pendidikan');
  document.getElementById('jabField').value  = opt.getAttribute('data-jabatan');
  document.getElementById('bidField').value  = opt.getAttribute('data-bidang');
  document.getElementById('depField').value  = opt.getAttribute('data-departemen');
  document.getElementById('jenjField').value = opt.getAttribute('data-jenjang');
});
</script>

<script>
const berkasData = <?= json_encode($berkasArr) ?>;
document.getElementById('kategoriSelect').addEventListener('change', function() {
  const kategori = this.value;
  const berkasSelect = document.getElementById('berkasSelect');
  berkasSelect.innerHTML = '<option value="">-- Pilih Berkas --</option>';
  berkasData.filter(b => b.kategori === kategori).forEach(b => {
    const opt = document.createElement('option');
    opt.value = b.kode;
    opt.textContent = b.nama_berkas;
    berkasSelect.appendChild(opt);
  });
});
</script>

</body>
</html>
