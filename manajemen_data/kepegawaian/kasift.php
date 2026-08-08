<?php
session_start();
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

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

// proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $idPeg   = intval($_POST['id']);
    $jmlks   = $_POST['jmlks'] ?? '';
    $bsr     = $_POST['bsr'] ?? 0;

    if ($idPeg) {
        mysqli_query($conn,"INSERT INTO kasift (id, jmlks, bsr) 
                            VALUES ('$idPeg','".mysqli_real_escape_string($conn,$jmlks)."','$bsr')");
    }
    header("Location: kasift.php");
    exit;
}

// proses hapus
if (isset($_GET['delete_id'])) {
    $delId = intval($_GET['delete_id']);
    mysqli_query($conn,"DELETE FROM kasift WHERE id='$delId'");
    header("Location: kasift.php");
    exit;
}

// ambil filter dari GET
$filterNik = $_GET['filterNik'] ?? '';
$result = false;
$jmlData = 0;

if ($filterNik) {
    if ($filterNik === 'ALL') {
        $sql = "SELECT k.jmlks, k.bsr, p.nik, p.nama, 
                       p.jbtn AS jabatan, 
                       jj.nama AS kode_jnj, 
                       d.nama AS departemen, 
                       p.bidang AS bidang, 
                       p.id
                FROM kasift k
                JOIN pegawai p ON k.id = p.id
                LEFT JOIN jnj_jabatan jj ON p.jnj_jabatan = jj.kode
                LEFT JOIN departemen d ON p.departemen = d.dep_id
                WHERE p.stts_aktif='AKTIF'
                ORDER BY p.nik ASC";
    } else {
        $sql = "SELECT k.jmlks, k.bsr, p.nik, p.nama, 
                       p.jbtn AS jabatan, 
                       jj.nama AS kode_jnj, 
                       d.nama AS departemen, 
                       p.bidang AS bidang, 
                       p.id
                FROM kasift k
                JOIN pegawai p ON k.id = p.id
                LEFT JOIN jnj_jabatan jj ON p.jnj_jabatan = jj.kode
                LEFT JOIN departemen d ON p.departemen = d.dep_id
                WHERE p.nik='".mysqli_real_escape_string($conn,$filterNik)."'
                  AND p.stts_aktif='AKTIF'
                ORDER BY p.nik ASC";
    }
    $result = mysqli_query($conn,$sql);
    $jmlData = $result ? mysqli_num_rows($result) : 0;
}

// ambil daftar pegawai untuk dropdown filter (urut ASC NIK)
$listPegawai = mysqli_query($conn,"SELECT nik,nama FROM pegawai WHERE stts_aktif='AKTIF' ORDER BY nik ASC");

// ambil daftar pegawai untuk dropdown tambah (exclude yang sudah ada di kasift)
$pegawaiList = [];
$resPeg = mysqli_query($conn,"SELECT id, nik, nama FROM pegawai WHERE stts_aktif='AKTIF' AND id NOT IN (SELECT id FROM kasift) ORDER BY nik ASC");
if ($resPeg) {
    while($row = mysqli_fetch_assoc($resPeg)){
        $pegawaiList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kasift</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
    <!-- HEADER -->
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">kasift</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

        <!-- Filter Pegawai -->
        <form method="get" class="mb-3">
          <label for="filterNik" class="form-label">Filter Pegawai:</label>
          <select name="filterNik" id="filterNik" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Pegawai --</option>
            <option value="ALL" <?= $filterNik==='ALL'?'selected':'' ?>>Pilih Semua</option>
            <?php while($p = mysqli_fetch_assoc($listPegawai)): ?>
              <option value="<?= $p['nik'] ?>" <?= ($filterNik==$p['nik'])?'selected':'' ?>>
                <?= $p['nik'].' - '.$p['nama'] ?>
              </option>
            <?php endwhile; ?>
          </select>

        <!-- Tombol Terapkan -->
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <!-- TABEL -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-kasift align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>KS</th>
                <th>Bsr.Tnj</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Kode Jenjang</th>
                <th>Departemen</th>
                <th>Bidang</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $jmlData > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['jmlks']) ?></td>
                    <td><?= number_format($row['bsr'],0,',','.') ?></td>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['jabatan']) ?></td>
                    <td><?= htmlspecialchars($row['kode_jnj']) ?></td>
                    <td><?= htmlspecialchars($row['departemen']) ?></td>
                    <td><?= htmlspecialchars($row['bidang']) ?></td>
                    <td class="text-center">
                      <a href="kasift.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="9" class="text-center">Tidak ada data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="kasift.php">
          <input type="hidden" name="mode" value="insert">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Kasift</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Pegawai</label>
                <select name="id" class="form-select" required>
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach($pegawaiList as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['nik'].' - '.$p['nama'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label">Jml.KS</label>
                <input type="text" class="form-control" name="jmlks" required>
                <small class="text-muted">
                  isi dengan "-" jika ingin KS mengikuti normal masuk, isi dengan angka masuk jika tidak !!!
                </small>
              </div>
              <div class="col-md-12">
                <label class="form-label">Besar Tunjangan</label>
                <input type="number" class="form-control" name="bsr" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">💾 Simpan</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
