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

// --- LOGIKA HAPUS ---
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])){
    $id            = mysqli_real_escape_string($conn, $_POST['hapus_id']);
    $kode_evaluasi = mysqli_real_escape_string($conn, $_POST['hapus_kode_evaluasi']);
    $tahun         = mysqli_real_escape_string($conn, $_POST['hapus_tahun']);
    $keterangan    = mysqli_real_escape_string($conn, $_POST['hapus_keterangan']);

    $sqlDel = "DELETE FROM evaluasi_kinerja_pegawai 
               WHERE id='$id'
                 AND kode_evaluasi='$kode_evaluasi' 
                 AND tahun='$tahun' 
                 AND keterangan='$keterangan' 
               LIMIT 1";

    if(mysqli_query($conn,$sqlDel)){
        $id = $_GET['id'] ?? '';
        header("Location: detail_riwayat_evaluasi.php?id=".$id);
        exit;
    } else {
        die("Gagal menghapus: ".mysqli_error($conn)." | Query: ".$sqlDel);
    }
}

// --- QUERY DATA RIWAYAT ---
$id = $_GET['id'] ?? '';
$sql = "SELECT r.*, p.nik, p.nama, e.nama_evaluasi 
        FROM evaluasi_kinerja_pegawai r 
        INNER JOIN pegawai p ON r.id=p.id 
        LEFT JOIN evaluasi_kinerja e ON r.kode_evaluasi = e.kode_evaluasi
        WHERE r.id='".mysqli_real_escape_string($conn,$id)."' 
        ORDER BY r.tahun ASC";
$res = mysqli_query($conn,$sql);

$riwayat = [];
while($row = mysqli_fetch_assoc($res)){
    $riwayat[] = $row;
}

$dataPegawai = $riwayat[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Riwayat Evaluasi</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>

<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase text-center flex-grow-1">
        Detail Riwayat Evaluasi - <?= htmlspecialchars($dataPegawai['nama'] ?? '') ?>
      </h5>
      <div class="d-flex gap-2">
        <a href="riwayat_evaluasi.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
      </div>
    </div>
    <div class="card-body">

      <!-- Ringkasan Riwayat Evaluasi -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-riwayat_evaluasi align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Tahun</th>
              <th>Bulan</th>
              <th>Hasil Evaluasi</th>
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($riwayat)): ?>
              <tr><td colspan="10" class="text-center text-muted">Tidak ada riwayat evaluasi</td></tr>
            <?php else: ?>
              <?php foreach($riwayat as $rj): ?>
                <tr>
                  <td><?= $rj['tahun'] ?></td>
                  <td><?= $rj['bulan'] ?></td>
                  <td><?= $rj['nama_evaluasi'] ?></td>
                  <td><?= $rj['keterangan'] ?></td>
                  <td class="text-center">
                    <form method="post" onsubmit="return confirm('Yakin hapus riwayat evaluasi ini?')">
                      <input type="hidden" name="hapus_id" value="<?= $rj['id'] ?>">
                      <input type="hidden" name="hapus_kode_evaluasi" value="<?= $rj['kode_evaluasi'] ?>">
                      <input type="hidden" name="hapus_tahun" value="<?= $rj['tahun'] ?>">
                      <input type="hidden" name="hapus_keterangan" value="<?= $rj['keterangan'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
