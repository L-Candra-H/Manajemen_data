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

// proses insert jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'insert') {
    $no_pengajuan   = mysqli_real_escape_string($conn, $_POST['no_pengajuan'] ?? '');
    $tanggal        = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
    $tanggal_awal   = mysqli_real_escape_string($conn, $_POST['tanggal_awal'] ?? '');
    $tanggal_akhir  = mysqli_real_escape_string($conn, $_POST['tanggal_akhir'] ?? '');
    $nik            = mysqli_real_escape_string($conn, $_POST['nik'] ?? '');
    $urgensi        = mysqli_real_escape_string($conn, $_POST['urgensi'] ?? '');
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $jumlah         = mysqli_real_escape_string($conn, $_POST['jumlah'] ?? '0');
    $kepentingan    = mysqli_real_escape_string($conn, $_POST['kepentingan'] ?? '');
    $nik_pj         = mysqli_real_escape_string($conn, $_POST['nik_pj'] ?? '');
    $status         = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    $sqlInsert = "INSERT INTO pengajuan_cuti 
        (no_pengajuan, tanggal, tanggal_awal, tanggal_akhir, nik, urgensi, alamat, jumlah, kepentingan, nik_pj, status)
        VALUES 
        ('$no_pengajuan','$tanggal','$tanggal_awal','$tanggal_akhir','$nik','$urgensi','$alamat','$jumlah','$kepentingan','$nik_pj','$status')";

    if (!mysqli_query($conn, $sqlInsert)) {
        die("Error insert: " . mysqli_error($conn));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'update') {
    $no_pengajuan   = mysqli_real_escape_string($conn, $_POST['no_pengajuan']);
    $tanggal        = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $tanggal_awal   = mysqli_real_escape_string($conn, $_POST['tanggal_awal']);
    $tanggal_akhir  = mysqli_real_escape_string($conn, $_POST['tanggal_akhir']);
    $nik            = mysqli_real_escape_string($conn, $_POST['nik']);
    $urgensi        = mysqli_real_escape_string($conn, $_POST['urgensi']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $jumlah         = mysqli_real_escape_string($conn, $_POST['jumlah']);
    $kepentingan    = mysqli_real_escape_string($conn, $_POST['kepentingan']);
    $nik_pj         = mysqli_real_escape_string($conn, $_POST['nik_pj']);
    $status         = mysqli_real_escape_string($conn, $_POST['status']);

    $sqlUpdate = "UPDATE pengajuan_cuti SET 
        tanggal='$tanggal',
        tanggal_awal='$tanggal_awal',
        tanggal_akhir='$tanggal_akhir',
        nik='$nik',
        urgensi='$urgensi',
        alamat='$alamat',
        jumlah='$jumlah',
        kepentingan='$kepentingan',
        nik_pj='$nik_pj',
        status='$status'
        WHERE no_pengajuan='$no_pengajuan'";

    if (!mysqli_query($conn, $sqlUpdate)) {
        die("Error update: " . mysqli_error($conn));
    }
}

if (($_GET['mode'] ?? '') === 'delete' && isset($_GET['no_pengajuan'])) {
    $no_pengajuan = mysqli_real_escape_string($conn, $_GET['no_pengajuan']);
    $sqlDelete = "DELETE FROM pengajuan_cuti WHERE no_pengajuan='$no_pengajuan'";
    if (!mysqli_query($conn, $sqlDelete)) {
        die("Error delete: " . mysqli_error($conn));
    }
}

// pagination
$limit = 5;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset= ($page-1)*$limit;

// filter tanggal
$tgl_awal  = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

$where = '';
if($tgl_awal && $tgl_akhir){
    $where = "WHERE pc.tanggal BETWEEN '".mysqli_real_escape_string($conn,$tgl_awal)."' 
                                   AND '".mysqli_real_escape_string($conn,$tgl_akhir)."'";
} elseif($tgl_awal){
    $where = "WHERE pc.tanggal >= '".mysqli_real_escape_string($conn,$tgl_awal)."'";
} elseif($tgl_akhir){
    $where = "WHERE pc.tanggal <= '".mysqli_real_escape_string($conn,$tgl_akhir)."'";
} else {
    // default: kosongkan tabel sebelum filter dipilih
    $where = "WHERE 1=0";
}

// query data pengajuan cuti dengan relasi pegawai, departemen
$sql = "SELECT pc.*, 
               p.nama AS nama, 
               p.bidang AS bidang, 
               d.nama AS departemen,
               pj.nama AS nama_pj, 
               pj.bidang AS bidang_pj, 
               dpj.nama AS departemen_pj
        FROM pengajuan_cuti pc
        LEFT JOIN pegawai p ON pc.nik = p.nik
        LEFT JOIN departemen d ON p.departemen = d.dep_id
        LEFT JOIN pegawai pj ON pc.nik_pj = pj.nik
        LEFT JOIN departemen dpj ON pj.departemen = dpj.dep_id
        $where
        ORDER BY pc.tanggal ASC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// hitung total data untuk pagination
$countSql = "SELECT COUNT(*) AS total 
             FROM pengajuan_cuti pc
             $where";
$countRes = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = ceil($totalRows / $limit);

// kumpulkan data
$cutiData = [];
while($row=mysqli_fetch_assoc($result)){
  $cutiData[] = $row;
}

// --- QUERY PEGAWAI UNTUK DROPDOWN MODAL TAMBAH ---
$listPegawai = [];
$resPeg = mysqli_query($conn,"
    SELECT p.nik, p.nama, p.bidang, d.nama AS departemen
    FROM pegawai p
    LEFT JOIN departemen d ON p.departemen = d.dep_id
    WHERE p.stts_aktif='AKTIF'
    ORDER BY p.nik
");
while($peg = mysqli_fetch_assoc($resPeg)){
    $listPegawai[] = $peg;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengajuan Cuti Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
<?php include __DIR__ . '/../layout/header.php'; ?>

<main class="main-content container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Pengajuan Cuti Pegawai</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
        <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
      </div>
    </div>

    <div class="card-body">
      <!-- FILTER RANGE TANGGAL -->
      <form method="get" class="mb-3">
        <label for="tgl_awal" class="form-label">Tanggal Awal :</label>
        <input type="date" name="tgl_awal" id="tgl_awal" 
          value="<?= htmlspecialchars($_GET['tgl_awal'] ?? '') ?>" 
          class="form-control form-control-sm" style="max-width:180px;display:inline-block;">

        <label for="tgl_akhir" class="form-label ms-2">Tanggal Akhir :</label>
        <input type="date" name="tgl_akhir" id="tgl_akhir" 
          value="<?= htmlspecialchars($_GET['tgl_akhir'] ?? '') ?>" 
          class="form-control form-control-sm" style="max-width:180px;display:inline-block;">

        <button type="submit" class="btn btn-secondary btn-sm ms-2">Terapkan</button>
      </form>

      <!-- TABEL CUTI -->
      <div class="table-wrapper">
        <table class="table table-striped table-bordered table-pengajuan_cuti align-middle">
          <thead class="table-dark text-center">
            <tr>
              <th>No. Pengajuan</th>
              <th>Tanggal</th>
              <th>Tgl Awal</th>
              <th>Tgl Akhir</th>
              <th>NIK</th>
              <th>Diajukan Oleh</th>
              <th>Bidang</th>
              <th>Departemen</th>
              <th>Jenis Cuti</th>
              <th>Alamat Tujuan</th>
              <th>Jml Cuti</th>
              <th>Kepentingan</th>
              <th>NIK P.J.</th>
              <th>P.J. Terkait</th>
              <th>Bidang</th>
              <th>Departemen</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($cutiData)): ?>
            <tr><td colspan="18" class="text-center text-muted">Belum ada pengajuan cuti</td></tr>
          <?php else: 
            foreach($cutiData as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['no_pengajuan']) ?></td>
                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_awal']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_akhir']) ?></td>
                <td><?= htmlspecialchars($row['nik']) ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['bidang']) ?></td>
                <td><?= htmlspecialchars($row['departemen']) ?></td>
                <td><?= htmlspecialchars($row['urgensi']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td><?= htmlspecialchars($row['jumlah']) ?></td>
                <td><?= htmlspecialchars($row['kepentingan']) ?></td>
                <td><?= htmlspecialchars($row['nik_pj']) ?></td>
                <td><?= htmlspecialchars($row['nama_pj']) ?></td>
                <td><?= htmlspecialchars($row['bidang_pj']) ?></td>
                <td><?= htmlspecialchars($row['departemen_pj']) ?></td>
                <td class="text-center">
                  <?php if ($row['status'] === 'Proses Pengajuan'): ?>
                    <span class="badge bg-info"><?= htmlspecialchars($row['status']) ?></span>
                  <?php elseif ($row['status'] === 'Disetujui'): ?>
                    <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
                  <?php elseif ($row['status'] === 'Ditolak'): ?>
                    <span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <button class="btn btn-warning btn-sm"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEdit"
                          onclick="isiEditModal(
                            '<?= $row['no_pengajuan'] ?>',
                            '<?= $row['tanggal'] ?>',
                            '<?= $row['tanggal_awal'] ?>',
                            '<?= $row['tanggal_akhir'] ?>',
                            '<?= $row['nik'] ?>',
                            '<?= $row['urgensi'] ?>',
                            '<?= $row['alamat'] ?>',
                            '<?= $row['jumlah'] ?>',
                            '<?= $row['kepentingan'] ?>',
                            '<?= $row['nik_pj'] ?>',
                            '<?= htmlspecialchars($row['nama']) ?>',
                            '<?= htmlspecialchars($row['bidang']) ?>',
                            '<?= htmlspecialchars($row['departemen']) ?>',
                            '<?= $row['status'] ?>',
                            '<?= htmlspecialchars($row['nama_pj']) ?>'
                          )">
                    Edit
                  </button>
                  <a href="pengajuan_cuti.php?mode=delete&no_pengajuan=<?= htmlspecialchars($row['no_pengajuan'] ?? '') ?>" 
                     onclick="return confirm('Yakin hapus data ini?')" 
                     class="btn btn-danger btn-sm">Hapus</a>
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
            $start = max(1,$page-1);
            $end = min($totalPages,$page+1);
            for($i=$start;$i<=$end;$i++): ?>
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
        <h5 class="modal-title">Tambah Pengajuan Cuti</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="pengajuan_cuti.php">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-body">
          <div class="row">
            <!-- Kolom Kiri -->
            <div class="col-md-6">
              <div class="mb-3">
                <label>No. Pengajuan</label>
                <input type="text" name="no_pengajuan" id="noPengajuan" class="form-control bg-info text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Tgl. Pengajuan</label>
                <input type="date" name="tanggal" id="tanggalPengajuan" class="form-control" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="mb-3">
                <label>Jenis Cuti</label>
                <select name="urgensi" class="form-select">
                  <option value="">-- Pilih Jenis Cuti --</option>
                  <option value="Tahunan">Tahunan</option>
                  <option value="Besar">Besar</option>
                  <option value="Sakit">Sakit</option>
                  <option value="Bersalin">Bersalin</option>
                  <option value="Alasan Penting">Alasan Penting</option>
                  <option value="Keterangan Lainnya">Keterangan Lainnya</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Diajukan Oleh (NIK)</label>
                <select name="nik" id="nikSelect" class="form-select">
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach($listPegawai as $peg): ?>
                    <option value="<?= $peg['nik'] ?>"
                            data-nama="<?= htmlspecialchars($peg['nama']) ?>"
                            data-bidang="<?= htmlspecialchars($peg['bidang']) ?>"
                            data-departemen="<?= htmlspecialchars($peg['departemen']) ?>">
                      <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Nama Pegawai</label>
                <input type="text" id="namaPegawai" class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Bidang</label>
                <input type="text" name="bidang" class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Departemen</label>
                <input type="text" name="departemen" class="form-control bg-danger text-white fw-bold" readonly>
              </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="col-md-6">
              <div class="mb-3">
                <label>P.J. Terkait (NIK)</label>
                <select name="nik_pj" id="pjSelect" class="form-select">
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach($listPegawai as $peg): ?>
                    <option value="<?= $peg['nik'] ?>" data-nama="<?= htmlspecialchars($peg['nama']) ?>">
                      <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Nama P.J.</label>
                <input type="text" id="namaPJ" class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Alamat Tujuan</label>
                <input type="text" name="alamat" class="form-control">
              </div>
              <div class="mb-3">
                <label>Tanggal Cuti</label>
                <div class="input-group">
                  <input type="date" name="tanggal_awal" id="tglAwal" class="form-control">
                  <span class="input-group-text">s/d</span>
                  <input type="date" name="tanggal_akhir" id="tglAkhir" class="form-control">
                </div>
              </div>
              <div class="mb-3">
                <label>Jml. Cuti</label>
                <div class="input-group">
                  <input type="number" name="jumlah" id="jmlCuti" 
                         class="form-control bg-info text-white fw-bold" readonly>
                  <span class="input-group-text">Hari</span>
                </div>
              </div>
              <div class="mb-3">
                <label>Status</label>
                <select class="form-select" disabled>
                  <option value="Proses Pengajuan" selected>Proses Pengajuan</option>
                  <option value="Disetujui">Disetujui</option>
                  <option value="Ditolak">Ditolak</option>
                </select>
                <!-- hidden input untuk tetap kirim value -->
                <input type="hidden" name="status" value="Proses Pengajuan">
              </div>
              <div class="mb-3">
                <label>Kepentingan Cuti</label>
                <textarea name="kepentingan" class="form-control"></textarea>
              </div>
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

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">Edit Pengajuan Cuti</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="pengajuan_cuti.php">
        <input type="hidden" name="mode" value="update">
        <div class="modal-body">
          <div class="row">
            <!-- Kolom Kiri -->
            <div class="col-md-6">
              <div class="mb-3">
                <label>No. Pengajuan</label>
                <input type="text" name="no_pengajuan" id="edit_noPengajuan"
                       class="form-control bg-info text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Tgl. Pengajuan</label>
                <input type="date" name="tanggal" id="edit_tanggalPengajuan"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Jenis Cuti</label>
                <select name="urgensi" id="edit_urgensi" class="form-select">
                  <option value="Tahunan">Tahunan</option>
                  <option value="Besar">Besar</option>
                  <option value="Sakit">Sakit</option>
                  <option value="Bersalin">Bersalin</option>
                  <option value="Alasan Penting">Alasan Penting</option>
                  <option value="Keterangan Lainnya">Keterangan Lainnya</option>
                </select>
              </div>
              <div class="mb-3">
                <label>NIK</label>
                <input type="text" name="nik" id="edit_nik"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Nama Pegawai</label>
                <input type="text" id="edit_namaPegawai"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Bidang</label>
                <input type="text" id="edit_bidang"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Departemen</label>
                <input type="text" id="edit_departemen"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="col-md-6">
              <div class="mb-3">
                <label>P.J. Terkait (NIK)</label>
                <select name="nik_pj" id="edit_pjSelect" class="form-select">
                  <option value="">-- Pilih Pegawai --</option>
                  <?php foreach($listPegawai as $peg): ?>
                    <option value="<?= $peg['nik'] ?>" data-nama="<?= htmlspecialchars($peg['nama']) ?>">
                      <?= $peg['nik'] ?> - <?= $peg['nama'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Nama P.J.</label>
                <input type="text" id="edit_namaPJ"
                       class="form-control bg-danger text-white fw-bold" readonly>
              </div>
              <div class="mb-3">
                <label>Alamat Tujuan</label>
                <input type="text" name="alamat" id="edit_alamat" class="form-control">
              </div>
              <div class="mb-3">
                <label>Tanggal Cuti</label>
                <div class="input-group">
                  <input type="date" name="tanggal_awal" id="edit_tglAwal" class="form-control">
                  <span class="input-group-text">s/d</span>
                  <input type="date" name="tanggal_akhir" id="edit_tglAkhir" class="form-control">
                </div>
              </div>
              <div class="mb-3">
                <label>Jumlah Cuti</label>
                <div class="input-group">
                  <input type="number" name="jumlah" id="edit_jumlah"
                         class="form-control bg-info text-white fw-bold" readonly>
                  <span class="input-group-text">Hari</span>
                </div>
              </div>
              <div class="mb-3">
                <label>Kepentingan Cuti</label>
                <textarea name="kepentingan" id="edit_kepentingan" class="form-control"></textarea>
              </div>
              <div class="mb-3">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-select">
                  <option value="Proses Pengajuan">Proses Pengajuan</option>
                  <option value="Disetujui">Disetujui</option>
                  <option value="Ditolak">Ditolak</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// sinkronisasi NIK -> Nama Pegawai + filter PJ
document.getElementById('nikSelect').addEventListener('change', function() {
  var selectedNik = this.value;
  var opt = this.options[this.selectedIndex];

  // isi Nama Pegawai, Bidang, Departemen
  document.getElementById('namaPegawai').value = opt.getAttribute('data-nama') || '';
  document.querySelector('input[name="bidang"]').value = opt.getAttribute('data-bidang') || '';
  document.querySelector('input[name="departemen"]').value = opt.getAttribute('data-departemen') || '';

  // filter dropdown PJ: sembunyikan NIK yang sama
  var pjSelect = document.getElementById('pjSelect');
  for (let i = 0; i < pjSelect.options.length; i++) {
    let pjOpt = pjSelect.options[i];
    if(pjOpt.value === selectedNik){
      pjOpt.style.display = 'none';
    } else {
      pjOpt.style.display = '';
    }
  }
});

// sinkronisasi NIK -> Nama PJ
document.getElementById('pjSelect').addEventListener('change', function() {
  var nama = this.options[this.selectedIndex].getAttribute('data-nama');
  document.getElementById('namaPJ').value = nama ? nama : '';
});

// hitung jumlah cuti otomatis
function hitungHariCuti() {
  let awal = document.getElementById('tglAwal').value;
  let akhir = document.getElementById('tglAkhir').value;
  if(awal && akhir) {
    let start = new Date(awal);
    let end = new Date(akhir);
    let diff = Math.floor((end - start)/(1000*60*60*24));
    document.getElementById('jmlCuti').value = diff+1; // inclusive
  }
}
document.getElementById('tglAwal').addEventListener('change',hitungHariCuti);
document.getElementById('tglAkhir').addEventListener('change',hitungHariCuti);

function hitungJumlahCutiEdit(){
  let tglAwal = document.getElementById('edit_tglAwal').value;
  let tglAkhir = document.getElementById('edit_tglAkhir').value;
  if(tglAwal && tglAkhir){
    let start = new Date(tglAwal);
    let end = new Date(tglAkhir);
    let diff = (end - start) / (1000*60*60*24) + 1; // +1 biar inklusif
    if(diff > 0){
      document.getElementById('edit_jumlah').value = diff;
    } else {
      document.getElementById('edit_jumlah').value = 0;
    }
  }
}

// pasang event listener
document.getElementById('edit_tglAwal').addEventListener('change',hitungJumlahCutiEdit);
document.getElementById('edit_tglAkhir').addEventListener('change',hitungJumlahCutiEdit);


// generate No. Pengajuan otomatis
function generateNoPengajuan() {
  let tanggalInput = document.getElementById('tanggalPengajuan').value;
  let today = tanggalInput ? new Date(tanggalInput) : new Date();

  if(isNaN(today)) {
    console.error("Tanggal tidak valid");
    return;
  }

  let y = today.getFullYear();
  let m = String(today.getMonth()+1).padStart(2,'0');
  let d = String(today.getDate()).padStart(2,'0');
  let base = "PC" + y + m + d;

  fetch('../layout/generate_nomor.php?tanggal='+y+m+d)
    .then(res => res.text())
    .then(noUrut => {
      // isi field no_pengajuan di form
      document.getElementById('noPengajuan').value = base + noUrut;
    })
    .catch(err => {
      console.error(err);
      document.getElementById('noPengajuan').value = base + "001"; // fallback
    });
}

// panggil saat modal dibuka
document.getElementById('modalTambah').addEventListener('shown.bs.modal', generateNoPengajuan);

// panggil lagi saat tanggal pengajuan diubah manual
document.getElementById('tanggalPengajuan').addEventListener('change', generateNoPengajuan);

function isiEditModal(no_pengajuan, tanggal, tgl_awal, tgl_akhir, nik, urgensi, alamat,
  jumlah, kepentingan, nik_pj, nama, bidang, departemen, status, namaPJ) {
  
  document.getElementById('edit_noPengajuan').value = no_pengajuan;
  document.getElementById('edit_tanggalPengajuan').value = tanggal;
  document.getElementById('edit_tglAwal').value = tgl_awal;
  document.getElementById('edit_tglAkhir').value = tgl_akhir;
  document.getElementById('edit_nik').value = nik;
  document.getElementById('edit_urgensi').value = urgensi;
  document.getElementById('edit_alamat').value = alamat;
  document.getElementById('edit_jumlah').value = jumlah;
  document.getElementById('edit_kepentingan').value = kepentingan;
  document.getElementById('edit_namaPegawai').value = nama;
  document.getElementById('edit_bidang').value = bidang;
  document.getElementById('edit_departemen').value = departemen;
  document.getElementById('edit_status').value = status;

  // set dropdown PJ sesuai data lama
  var pjSelect = document.getElementById('edit_pjSelect');
  pjSelect.value = nik_pj;

  // sembunyikan NIK pengaju
  for (let i = 0; i < pjSelect.options.length; i++) {
    if (pjSelect.options[i].value === nik) {
      pjSelect.options[i].style.display = 'none';
    }
  }

  // isi nama PJ sesuai option yang match
  var opt = pjSelect.querySelector('option[value="'+nik_pj+'"]');
  document.getElementById('edit_namaPJ').value = opt ? opt.getAttribute('data-nama') : namaPJ;
}

// sinkronisasi Nama PJ saat dropdown berubah
document.getElementById('edit_pjSelect').addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  document.getElementById('edit_namaPJ').value = opt ? opt.getAttribute('data-nama') : '';
});

// sinkronisasi NIK -> Nama PJ di modal Edit
document.getElementById('edit_pjSelect').addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  document.getElementById('edit_namaPJ').value = opt ? opt.getAttribute('data-nama') : '';
});

// Validasi sebelum submit form Tambah/Edit di pengajuan_cuti.php
document.querySelectorAll('form[action="pengajuan_cuti.php"]').forEach(form => {
  form.addEventListener('submit', function(e) {
    let awal = form.querySelector('#tglAwal, #edit_tglAwal')?.value;
    let akhir = form.querySelector('#tglAkhir, #edit_tglAkhir')?.value;
    let jumlah = form.querySelector('#jmlCuti, #edit_jumlah')?.value;

    if (awal && akhir) {
      let start = new Date(awal);
      let end = new Date(akhir);

      if (end < start) {
        e.preventDefault();
        alert("Tanggal akhir tidak boleh lebih kecil dari tanggal awal!");
        return;
      }
    }

    if (jumlah <= 0) {
      e.preventDefault();
      alert("Jumlah cuti harus lebih dari 0 hari!");
      return;
    }
  });
});

</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
