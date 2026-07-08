<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$nik = $_GET['nik'] ?? '';
$sql = "SELECT nik, nama, indek AS indek_struktural, pengurang, cuti_diambil, dankes 
        FROM pegawai WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result) ?? [];
?>

<div class="modal-header bg-primary text-white">
  <h5 class="modal-title" id="editModalLabel">Edit Data Index Pegawai</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
  <form action="index_pegawai.php" method="post">
    <input type="hidden" name="nik" value="<?= htmlspecialchars($row['nik'] ?? '') ?>">

    <div class="mb-3">
      <label class="form-label">NIP</label>
      <input type="text" class="form-control bg-danger text-white fw-bold" value="<?= htmlspecialchars($row['nik'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">Nama</label>
      <input type="text" class="form-control bg-danger text-white fw-bold" value="<?= htmlspecialchars($row['nama'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">Index Struktural</label>
      <input type="text" class="form-control" name="indek_struktural" value="<?= htmlspecialchars($row['indek_struktural'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Pengurang</label>
      <div class="input-group">
        <input type="text" class="form-control" name="pengurang" 
               value="<?= htmlspecialchars($row['pengurang'] ?? '') ?>">
        <span class="input-group-text">%</span>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Cuti Yang Sudah Diambil</label>
      <div class="input-group">
        <input type="text" class="form-control" name="cuti_diambil" 
               value="<?= htmlspecialchars($row['cuti_diambil'] ?? '') ?>">
        <span class="input-group-text">X</span>
      </div>
      <small class="form-text text-muted">
        Hanya untuk menampilkan data cuti yang tidak diinput lewat lampiran. 
        Sisa cuti dihitung dari data ini ditambah input cuti tiap bulan.
      </small>
    </div>

    <div class="mb-3">
      <label class="form-label">Dana Kesehatan Selama 1 Tahun</label>
      <div class="input-group">
        <span class="input-group-text">Rp.</span>
        <input type="text" class="form-control" name="dankes" 
               value="<?= htmlspecialchars($row['dankes'] ?? '') ?>">
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    </div>
  </form>
</div>
