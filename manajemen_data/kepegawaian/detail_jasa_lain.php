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

$nik = $_GET['nik'] ?? '';
$nama = '';
$jmlData = 0;
$result  = false;

// proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $tahunBulan = $_POST['tahun_bulan'] ?? '';
    $nikPost    = $_POST['nik'] ?? '';
    $bsr_jasa   = $_POST['bsr_jasa'] ?? 0;
    $ktg        = $_POST['ktg'] ?? '';

    if ($tahunBulan && $nikPost) {
        [$tahun,$bulan] = explode('-', $tahunBulan);
        $resId = mysqli_query($conn,"SELECT id FROM pegawai WHERE nik='".mysqli_real_escape_string($conn,$nikPost)."'");
        if ($rowId = mysqli_fetch_assoc($resId)) {
            $idPeg = $rowId['id'];
            mysqli_query($conn,"INSERT INTO jasa_lain (id, thn, bln, bsr_jasa, ktg) 
                                VALUES ('$idPeg','$tahun','$bulan','$bsr_jasa','".mysqli_real_escape_string($conn,$ktg)."')");
        }
    }
    header("Location: detail_jasa_lain.php?nik=".$nikPost);
    exit;
}

// proses hapus (pakai kombinasi kunci)
if (isset($_GET['thn'], $_GET['bln'], $_GET['bsr_jasa'], $_GET['ktg']) && $nik) {
    $thn = intval($_GET['thn']);
    $bln = intval($_GET['bln']);
    $bsr = floatval($_GET['bsr_jasa']);
    $ktg = mysqli_real_escape_string($conn,$_GET['ktg']);

    $resId = mysqli_query($conn,"SELECT id FROM pegawai WHERE nik='".mysqli_real_escape_string($conn,$nik)."'");
    if ($rowId = mysqli_fetch_assoc($resId)) {
        $idPeg = $rowId['id'];
        mysqli_query($conn,"DELETE FROM jasa_lain 
                            WHERE id='$idPeg' AND thn='$thn' AND bln='$bln' 
                                  AND bsr_jasa='$bsr' AND ktg='$ktg' LIMIT 1");
    }
    header("Location: detail_jasa_lain.php?nik=".$nik);
    exit;
}

if ($nik) {
    $resPegawai = mysqli_query($conn, "SELECT nama FROM pegawai WHERE nik='".mysqli_real_escape_string($conn,$nik)."'");
    if ($rowPegawai = mysqli_fetch_assoc($resPegawai)) {
        $nama = $rowPegawai['nama'];
    }

    // ambil data jasa lain detail
    $sql = "SELECT thn, bln, bsr_jasa, ktg 
            FROM jasa_lain 
            WHERE id = (SELECT id FROM pegawai WHERE nik='".mysqli_real_escape_string($conn,$nik)."')";
    $result = mysqli_query($conn, $sql);
    $jmlData = $result ? mysqli_num_rows($result) : 0;
}

$bulanArr = [
    1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
    5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
    9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

$listTahunBulan = [];
$resTahun = mysqli_query($conn,"SELECT tahun, bulan FROM set_tahun ORDER BY tahun DESC, bulan DESC");
if ($resTahun) {
    while($t = mysqli_fetch_assoc($resTahun)){
        $listTahunBulan[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Jasa Lain</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
          Detail Jasa Lain | <?= htmlspecialchars($nik) ?> - <?= htmlspecialchars($nama) ?>
        </h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="jasa_lain.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-jasa_lain align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Tahun</th>
                <th>Bulan</th>
                <th>Besar Jasa</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $jmlData > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['thn']) ?></td>
                    <td><?= $bulanArr[(int)$row['bln']] ?></td>
                    <td><?= number_format($row['bsr_jasa'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['ktg']) ?></td>
                    <td class="text-center">
                      <a href="detail_jasa_lain.php?nik=<?= urlencode($nik) ?>&thn=<?= $row['thn'] ?>&bln=<?= $row['bln'] ?>&bsr_jasa=<?= $row['bsr_jasa'] ?>&ktg=<?= urlencode($row['ktg']) ?>" 
                         class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')">🗑️ Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center">Tidak ada data</td></tr>
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
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <form method="post" action="detail_jasa_lain.php?nik=<?= urlencode($nik) ?>">
          <input type="hidden" name="mode" value="insert">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Jasa Lain</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Tahun - Bulan</label>
                <select name="tahun_bulan" class="form-select" required>
                  <option value="">-- Pilih Tahun + Bulan --</option>
                  <?php foreach($listTahunBulan as $tb): ?>
                    <option value="<?= $tb['tahun'].'-'.$tb['bulan'] ?>">
                      <?= $tb['tahun'].' - '.$bulanArr[(int)$tb['bulan']] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">NIP</label>
                <input type="text" class="form-control bg-danger text-white fw-bold"
                       name="nik" value="<?= htmlspecialchars($nik) ?>" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" class="form-control bg-danger text-white fw-bold"
                       value="<?= htmlspecialchars($nama) ?>" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label">Besar Jasa</label>
                <input type="number" class="form-control" name="bsr_jasa" required>
              </div>
              <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <input type="text" class="form-control" name="ktg" required>
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
