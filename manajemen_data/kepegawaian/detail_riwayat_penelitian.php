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
    $id               = mysqli_real_escape_string($conn, $_POST['hapus_id']);
    $judul_penelitian = mysqli_real_escape_string($conn, $_POST['hapus_judul_penelitian']);
    $tahun            = mysqli_real_escape_string($conn, $_POST['hapus_tahun']);
    $peranan          = mysqli_real_escape_string($conn, $_POST['hapus_peranan']);
    $judul_jurnal     = mysqli_real_escape_string($conn, $_POST['hapus_judul_jurnal']);

    $sqlDel = "DELETE FROM riwayat_penelitian 
               WHERE id='$id'
                 AND judul_penelitian='$judul_penelitian' 
                 AND tahun='$tahun' 
                 AND peranan='$peranan' 
                 AND judul_jurnal='$judul_jurnal' 
               LIMIT 1";

    if(mysqli_query($conn,$sqlDel)){
        $id = $_GET['id'] ?? '';
        header("Location: detail_riwayat_penelitian.php?id=".$id);
        exit;
    } else {
        die("Gagal menghapus: ".mysqli_error($conn)." | Query: ".$sqlDel);
    }
}

// --- QUERY DATA RIWAYAT ---
$id = $_GET['id'] ?? '';
$sql = "SELECT r.*, p.nik, p.nama 
        FROM riwayat_penelitian r 
        INNER JOIN pegawai p ON r.id=p.id 
        WHERE r.id='".mysqli_real_escape_string($conn,$id)."' 
        ORDER BY r.judul_penelitian ASC";
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
  <title>Detail Riwayat Penelitian</title>
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
        Detail Riwayat Penelitian - <?= htmlspecialchars($dataPegawai['nama'] ?? '') ?>
      </h5>
      <div class="d-flex gap-2">
        <a href="riwayat_penelitian.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
      </div>
    </div>
    <div class="card-body">

      <!-- Ringkasan Riwayat Penelitian -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-riwayat_penelitian align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>Jenis Penelitian</th>
              <th>Peranan</th>
              <th>Judul Penelitian</th>
              <th>Diterbitkan di Jurnal</th>
              <th>Tahun</th>
              <th>Biaya</th>
              <th>Asal Dana</th>
              <th>Berkas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($riwayat)): ?>
              <tr><td colspan="10" class="text-center text-muted">Tidak ada riwayat penelitian</td></tr>
            <?php else: ?>
              <?php foreach($riwayat as $rj): ?>
                <tr>
                  <td><?= $rj['jenis_penelitian'] ?></td>
                  <td><?= $rj['peranan'] ?></td>
                  <td><?= $rj['judul_penelitian'] ?></td>
                  <td><?= $rj['judul_jurnal'] ?></td>
                  <td><?= $rj['tahun'] ?></td>
                  <td><?= $rj['biaya_penelitian'] ?></td>
                  <td><?= $rj['asal_dana'] ?></td>
                  <td class="text-center">
                    <?php if($rj['berkas'] && !str_ends_with($rj['berkas'],'.pdf')): ?>
                      <?php 
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder . "/webapps/penggajian/pages/riwayatpenelitian/berkas/" . basename($rj['berkas']);
                      ?>
                      <!-- Thumbnail -->
                      <img src="<?= $path ?>" class="img-thumbnail" style="max-height:80px;cursor:pointer"
                           data-bs-toggle="modal" data-bs-target="#lihatBerkas<?= $rj['id'] ?>">

                      <!-- Modal gambar -->
                      <div class="modal fade" id="lihatBerkas<?= $rj['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Penelitian</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                              <img src="<?= $path ?>" class="img-fluid">
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php elseif($rj['berkas'] && str_ends_with($rj['berkas'],'.pdf')): ?>
                      <?php 
                        $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                        $path = "/" . $appFolder . "/webapps/penggajian/pages/riwayatpenelitian/berkas/" . basename($rj['berkas']);
                      ?>
                      <button type="button" class="btn btn-sm btn-info" 
                              data-bs-toggle="modal" data-bs-target="#lihatPdf<?= $rj['id'] ?>">
                        Lihat PDF
                      </button>
                      <div class="modal fade" id="lihatPdf<?= $rj['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Berkas Penelitian (PDF)</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              <iframe src="<?= $path ?>" style="width:100%;height:600px;" frameborder="0"></iframe>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <form method="post" onsubmit="return confirm('Yakin hapus riwayat penelitian ini?')">
                      <input type="hidden" name="hapus_id" value="<?= $rj['id'] ?>">
                      <input type="hidden" name="hapus_judul_penelitian" value="<?= $rj['judul_penelitian'] ?>">
                      <input type="hidden" name="hapus_tahun" value="<?= $rj['tahun'] ?>">
                      <input type="hidden" name="hapus_peranan" value="<?= $rj['peranan'] ?>">
                      <input type="hidden" name="hapus_judul_jurnal" value="<?= $rj['judul_jurnal'] ?>">
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
