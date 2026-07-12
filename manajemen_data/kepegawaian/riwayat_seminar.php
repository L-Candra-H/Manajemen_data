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

// pagination
$limit = 5;
$page  = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset= ($page-1)*$limit;

// filter pegawai
$filter = $_GET['filter'] ?? '';

// ambil daftar pegawai aktif untuk dropdown filter
$listPegawaiRes = mysqli_query($conn,"
    SELECT id, nik, nama 
    FROM pegawai 
    WHERE stts_aktif='AKTIF' 
    ORDER BY nik ASC
");
$listPegawai = [];
while($peg = mysqli_fetch_assoc($listPegawaiRes)){
    $listPegawai[] = $peg;
}

// proses tambah riwayat seminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $id              = $_POST['id'] ?? '';
    $tingkat         = $_POST['tingkat'] ?? '';
    $jenis           = $_POST['jenis'] ?? '';
    $nama_seminar    = $_POST['nama_seminar'] ?? '';
    $peranan         = $_POST['peranan'] ?? '';
    $mulai           = $_POST['mulai'] ?? '';
    $selesai         = $_POST['selesai'] ?? '';
    $penyelengara    = $_POST['penyelengara'] ?? '';
    $tempat          = $_POST['tempat'] ?? '';
    $filePath        = "";

    if (!empty($_FILES['berkas']['name'])) {
        $fileName   = basename($_FILES['berkas']['name']);
        $targetDir  = __DIR__ . "/../../webapps/penggajian/pages/riwayatseminar/berkas/";
        $targetFile = $targetDir . $fileName;

        // pastikan folder ada
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true)) {
                die("Folder tujuan tidak bisa dibuat. Periksa permission.");
            }
        }

        // hanya simpan gambar dan pdf
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','pdf'])) {
            if (move_uploaded_file($_FILES['berkas']['tmp_name'], $targetFile)) {
                $filePath = "pages/riwayatseminar/berkas/" . $fileName;
            } else {
                die("Upload berkas gagal, data tidak disimpan.");
            }
        }
    }

    // hanya insert kalau upload sukses
    if ($filePath !== "") {
        $stmt = $conn->prepare("INSERT INTO riwayat_seminar
            (id, tingkat, jenis, nama_seminar, peranan, mulai, 
             selesai, penyelengara, tempat, berkas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $id, $tingkat, $jenis, $nama_seminar,
                          $peranan, $mulai, $selesai, $penyelengara, $tempat, $filePath);
        $stmt->execute();
        $stmt->close();
    }
}

// query data pegawai + riwayat
if($filter===''){
    $sql = "SELECT p.id AS peg_id, p.nik, p.nama, d.nama AS departemen, p.bidang,
                   r.nama_seminar, r.mulai, r.penyelengara, r.tempat
            FROM pegawai p
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            LEFT JOIN riwayat_seminar r ON r.id = p.id
            WHERE 1=0";
} elseif($filter==='ALL'){
    $sql = "SELECT p.id AS peg_id, p.nik, p.nama, d.nama AS departemen, p.bidang,
                   r.nama_seminar, r.mulai, r.penyelengara, r.tempat
            FROM pegawai p
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            LEFT JOIN riwayat_seminar r ON r.id = p.id
            WHERE p.stts_aktif='AKTIF'
            ORDER BY p.nik ASC, r.nama_seminar ASC
            LIMIT $limit OFFSET $offset";
} else {
    $sql = "SELECT p.id AS peg_id, p.nik, p.nama, d.nama AS departemen, p.bidang,
                   r.nama_seminar, r.mulai, r.penyelengara, r.tempat
            FROM pegawai p
            LEFT JOIN departemen d ON p.departemen = d.dep_id
            LEFT JOIN riwayat_seminar r ON r.id = p.id
            WHERE p.nik='".mysqli_real_escape_string($conn,$filter)."'
            ORDER BY r.nama_seminar ASC
            LIMIT $limit OFFSET $offset";
}
$result = mysqli_query($conn,$sql);

// hitung total data untuk pagination
if($filter==='ALL'){
    $countSql = "SELECT COUNT(*) AS total FROM pegawai WHERE stts_aktif='AKTIF'";
} elseif($filter!==''){
    $countSql = "SELECT COUNT(*) AS total FROM pegawai WHERE nik='".mysqli_real_escape_string($conn,$filter)."'";
} else {
    $countSql = "SELECT 0 AS total";
}
$countRes = mysqli_query($conn,$countSql);
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = ceil($totalRows / $limit);

// kelompokkan hasil per pegawai
$pegawaiData = [];
while($row=mysqli_fetch_assoc($result)){
    $pegId = $row['peg_id'];
    if(!isset($pegawaiData[$pegId])){
        $pegawaiData[$pegId] = [
            'nik'=>$row['nik'],
            'nama'=>$row['nama'],
            'departemen'=>$row['departemen'],
            'bidang'=>$row['bidang'],
            'riwayat'=>[]
        ];
    }
    if($row['nama_seminar']!==null){
        $pegawaiData[$pegId]['riwayat'][] = [
            'nama_seminar'=>$row['nama_seminar'],
            'mulai'=>$row['mulai'],
            'penyelengara'=>$row['penyelengara'],
            'tempat'=>$row['tempat']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Kegiatan Ilmiah & Pelatihan Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Riwayat Kegiatan Ilmiah & Pelatihan Pegawai</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
        <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>
    </div>

    <div class="card-body">
      <!-- FILTER -->
      <form method="get" class="mb-3">
        <label for="filter" class="form-label">Filter Pegawai:</label>
        <select name="filter" id="filter" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
          <option value="">-- Pilih Pegawai --</option>
          <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
          <?php foreach($listPegawai as $peg): ?>
            <option value="<?= $peg['nik'] ?>" <?= ($filter==$peg['nik'])?'selected':'' ?>>
              <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
      </form>

      <!-- Tabel Pegawai -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-riwayat_seminar align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>NIP</th>
              <th>Nama</th>
              <th>Departemen</th>
              <th>Bidang</th>
              <th>Riwayat Kegiatan Ilmiah & Pelatihan Pegawai</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($pegawaiData)): ?>
            <tr><td colspan="6" class="text-center text-muted">Silakan pilih pegawai untuk menampilkan data</td></tr>
          <?php else: 
            foreach($pegawaiData as $pegId=>$data): ?>
              <tr>
                <td><?= htmlspecialchars($data['nik']) ?></td>
                <td><?= htmlspecialchars($data['nama']) ?></td>
                <td><?= htmlspecialchars($data['departemen']) ?></td>
                <td><?= htmlspecialchars($data['bidang']) ?></td>
                <td>
                  <?php if(!empty($data['riwayat'])): ?>
                    <table class="table table-sm table-bordered mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>No</th>
                          <th>Nama Kegiatan</th>
                          <th>Tanggal</th>
                          <th>Penyelenggara</th>
                          <th>Tempat</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $no=1; foreach($data['riwayat'] as $rj): ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($rj['nama_seminar']) ?></span></td>
                            <td class="text-primary"><?= htmlspecialchars($rj['mulai']) ?></td>
                            <td class="text-success"><?= htmlspecialchars($rj['penyelengara']) ?></td>
                            <td class="text-success"><?= htmlspecialchars($rj['tempat']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="detail_riwayat_seminar.php?id=<?= $pegId ?>" class="btn btn-info btn-sm">Detail</a>
                </td>
              </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if($totalPages > 1): ?>
      <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mt-3">
          <!-- Tombol Prev -->
          <li class="page-item <?= ($page<=1)?'disabled':'' ?>">
            <a class="page-link" href="?filter=<?= urlencode($filter) ?>&page=<?= max(1,$page-1) ?>">Prev</a>
          </li>

          <!-- Nomor Halaman (hanya 3 sekitar halaman aktif) -->
          <?php
            $start = max(1, $page-1);
            $end   = min($totalPages, $page+1);
            for($i=$start; $i<=$end; $i++): ?>
              <li class="page-item <?= ($i==$page)?'active':'' ?>">
                <a class="page-link" href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
          <?php endfor; ?>

          <!-- Tombol Next -->
          <li class="page-item <?= ($page>=$totalPages)?'disabled':'' ?>">
            <a class="page-link" href="?filter=<?= urlencode($filter) ?>&page=<?= min($totalPages,$page+1) ?>">Next</a>
          </li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Riwayat Kegiatan Ilmiah & Pelatihan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" enctype="multipart/form-data" action="">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-body">
          <div class="row">
            <!-- Kolom Kanan -->
            <div class="col-md-6">
              <div class="mb-3">
                <label for="nipSelect" class="form-label">NIP</label>
                <select name="id" id="nipSelect" class="form-select">
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach($listPegawai as $peg): ?>
                    <option value="<?= $peg['id'] ?>" data-nama="<?= htmlspecialchars($peg['nama']) ?>">
                      <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="namaPegawai" class="form-label">Nama</label>
                <input type="text" id="namaPegawai" class="form-control bg-danger text-white fw-bold" readonly>
              </div>

              <div class="mb-3">
                <label>Level</label>
                  <select name="tingkat" class="form-select">
                    <option value="">-- Pilih Tingkat --</option>
                    <option>Local</option><option>Regional</option><option>Nasional</option><option>Internasional</option>
                  </select>
              </div>

              <div class="mb-3">
                <label>Jenis Kegiatan</label>
                  <select name="jenis" class="form-select">
                    <option value="">-- Pilih Jenis --</option>
                    <option>WORKSHOP</option><option>SIMPOSIUM</option><option>SEMINAR</option><option>FGD</option>
                    <option>PELATIHAN</option><option>LAINNYA</option>
                  </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Nama Kegiatan</label>
                <input type="text" name="nama_seminar" class="form-control">
              </div>

              <div class="mb-3">
                <label class="form-label">Peranan</label>
                <input type="text" name="peranan" class="form-control">
              </div>
            </div>

            <!-- Kolom Kiri -->
            <div class="col-md-6">
              <label>Tanggal Mulai</label>
              <input type="date" name="mulai" class="form-control">

              <label>Sampai Dengan</label>
              <input type="date" name="selesai" class="form-control">

              <label>Penyelenggara</label>
              <input type="text" name="penyelengara" class="form-control">

              <label>Tempat</label>
              <input type="text" name="tempat" class="form-control">

              <label>Sertifikat (PDF/Gambar)</label>
              <input type="file" name="berkas" class="form-control" accept="image/*">
            </div>
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

<script>
// sinkronisasi NIP -> Nama
document.getElementById('nipSelect').addEventListener('change', function() {
  var nama = this.options[this.selectedIndex].getAttribute('data-nama');
  document.getElementById('namaPegawai').value = nama ? nama : '';
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
