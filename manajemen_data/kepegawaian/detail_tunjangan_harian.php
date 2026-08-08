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

$nik  = $_GET['nik'] ?? '';
$nama = $_GET['nama'] ?? '';

$sqlPegawai = "SELECT id, nik, nama FROM pegawai WHERE nik='" . mysqli_real_escape_string($conn, $nik) . "'";
$resPegawai = mysqli_query($conn, $sqlPegawai);
$pegawai    = mysqli_fetch_assoc($resPegawai);

// tambah tunjangan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $idTnj = intval($_POST['id_tnj']);
    $stmt  = $conn->prepare("INSERT INTO pnm_tnj_harian (id, id_tnj) VALUES (?, ?)");
    $stmt->bind_param("ii", $pegawai['id'], $idTnj);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_tunjangan_harian.php?nik=" . urlencode($nik) . "&nama=" . urlencode($nama));
    exit;
}

// hapus
if (isset($_GET['hapus'])) {
    $hapusId = intval($_GET['hapus']);
    $stmt    = $conn->prepare("DELETE FROM pnm_tnj_harian WHERE id_tnj=? AND id=?");
    $stmt->bind_param("ii", $hapusId, $pegawai['id']);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_tunjangan_harian.php?nik=" . urlencode($nik) . "&nama=" . urlencode($nama));
    exit;
}

// ambil daftar tunjangan pegawai
$sqlTunjangan = "
    SELECT mb.id, mb.nama AS nama_tunjangan, COALESCE(mb.tnj,0) AS besar_tunjangan
    FROM pnm_tnj_harian pb
    LEFT JOIN master_tunjangan_harian mb ON pb.id_tnj = mb.id
    WHERE pb.id = '{$pegawai['id']}'
";
$resTunjangan = mysqli_query($conn, $sqlTunjangan);
$jmlData      = $resTunjangan ? mysqli_num_rows($resTunjangan) : 0;

// ambil daftar master tunjangan harian yang belum dipakai
$sqlMaster = "
    SELECT id, nama, COALESCE(tnj,0) AS besar_tunjangan
    FROM master_tunjangan_harian
    WHERE id NOT IN (
        SELECT id_tnj FROM pnm_tnj_harian WHERE id = '{$pegawai['id']}'
    )
    ORDER BY nama ASC
";
$resMaster = mysqli_query($conn, $sqlMaster);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Tunjangan Harian</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <!-- HEADER -->
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
          Detail Tunjangan Harian | <?= htmlspecialchars($pegawai['nik']) ?> - <?= htmlspecialchars($pegawai['nama']) ?>
        </h5>
        <a href="tunjangan.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>

      <div class="card-body p-3">
        <!-- Form Tambah Tunjangan -->
        <form method="post" class="d-flex flex-wrap align-items-center mb-3">
          <label class="form-label mb-0 me-2">NIP :</label>
          <input type="text" class="form-control bg-danger me-3" value="<?= htmlspecialchars($pegawai['nik']) ?>" readonly style="width:auto;">
          
          <label class="form-label mb-0 me-2">Nama :</label>
          <input type="text" class="form-control bg-danger me-3" value="<?= htmlspecialchars($pegawai['nama']) ?>" readonly style="width:auto;">
          
          <label class="form-label mb-0 me-2">Nama Tunjangan :</label>
          <select name="id_tnj" class="form-select me-3" style="width:auto;" required>
            <option value="">-- Pilih Tunjangan Harian --</option>
            <?php while ($m = mysqli_fetch_assoc($resMaster)): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
            <?php endwhile; ?>
          </select>
          
          <div class="ms-auto d-flex gap-2">
            <button type="submit" name="tambah" class="btn btn-success">💾 Simpan</button>
            <a href="tunjangan.php" class="btn btn-secondary">❌ Batal</a>
          </div>
        </form>

        <!-- Tabel Data -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Nama Tunjangan</th>
                <th>Besar Tunjangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($jmlData > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($resTunjangan)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nama_tunjangan']) ?></td>
                    <td><?= number_format($row['besar_tunjangan'],0,',','.') ?></td>
                    <td class="text-center">
                      <a href="detail_tunjangan_harian.php?nik=<?= urlencode($nik) ?>&nama=<?= urlencode($nama) ?>&hapus=<?= $row['id'] ?>"
                         class="btn btn-danger btn-sm"
                         onclick="return confirm('Hapus tunjangan ini?')">🗑️ Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="3" class="text-center text-muted">Belum ada tunjangan harian</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Info Data -->
        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
