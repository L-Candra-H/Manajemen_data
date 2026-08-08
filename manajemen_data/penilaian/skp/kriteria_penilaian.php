<?php
session_start();
include __DIR__ . '/../../conf/auth.php';
include __DIR__ . '/../../conf/conf.php';

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

// === PROSES CRUD ===
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi'])) {
    $aksi=$_POST['aksi'];

    if($aksi==='tambah'){
        $kriteria=$_POST['nama_kriteria'];
        $kode_kategori=$_POST['kode_kategori'];

        // generate kode otomatis KPxxxxxxxx
        $qLast=$conn->query("SELECT kode_kriteria FROM skp_kriteria_penilaian ORDER BY kode_kriteria DESC LIMIT 1");
        $lastKode=$qLast->num_rows>0?$qLast->fetch_assoc()['kode_kriteria']:'KP00000000';
        $num=intval(substr($lastKode,2))+1;
        $kode='KP'.str_pad($num,8,'0',STR_PAD_LEFT);

        $sql="INSERT INTO skp_kriteria_penilaian (kode_kriteria,nama_kriteria,kode_kategori) VALUES (?,?,?)";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("sss",$kode,$kriteria,$kode_kategori);
        $stmt->execute();
    }

    if($aksi==='update'){
        $kode=$_POST['kode_kriteria'];
        $kriteria=$_POST['nama_kriteria'];
        $kode_kategori=$_POST['kode_kategori'];

        $sql="UPDATE skp_kriteria_penilaian SET nama_kriteria=?, kode_kategori=? WHERE kode_kriteria=?";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("sss",$kriteria,$kode_kategori,$kode);
        $stmt->execute();
    }

    if($aksi==='hapus'){
        $kode=$_POST['kode_kriteria'];
        $sql="DELETE FROM skp_kriteria_penilaian WHERE kode_kriteria=?";
        $stmt=$conn->prepare($sql);
        $stmt->bind_param("s",$kode);
        $stmt->execute();
    }
}

if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];
    $sql = "DELETE FROM skp_kriteria_penilaian WHERE kode_kriteria=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode);
    $stmt->execute();

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    header("Location: ?page=$page");
    exit;
}

// === PAGINATION ===
$limit=5;
$page=isset($_GET['page'])?max(1,intval($_GET['page'])):1;
$offset=($page-1)*$limit;

$sqlData="SELECT k.kode_kriteria,k.nama_kriteria,k.kode_kategori,
                 c.nama_kategori,c.sasaran
          FROM skp_kriteria_penilaian k
          LEFT JOIN skp_kategori_penilaian c ON k.kode_kategori=c.kode_kategori
          ORDER BY k.kode_kriteria ASC LIMIT ?,?";
$stmt=$conn->prepare($sqlData);
$stmt->bind_param("ii",$offset,$limit);
$stmt->execute();
$result=$stmt->get_result();

$total=$conn->query("SELECT COUNT(*) AS ttl FROM skp_kriteria_penilaian")->fetch_assoc()['ttl'];
$totalPages=ceil($total/$limit);

// === Ambil data kategori untuk dropdown ===
$qKategori=$conn->query("SELECT kode_kategori,nama_kategori,sasaran FROM skp_kategori_penilaian ORDER BY kode_kategori ASC");
$kategoriList=[];
while($row=$qKategori->fetch_assoc()){
    $kategoriList[]=$row;
}

// mapping sasaran
$sasaranMap=[
  '1'=>'1. Mengidentifikasi Pasien Dengan Benar',
  '2'=>'2. Meningkatkan Komunikasi Yang Efektif',
  '3'=>'3. Meningkatkan Keamanan Obat-Obatan Yang Harus Diwaspadai',
  '4'=>'4. Memastikan Lokasi Pembedahan Yang Benar, Prosedur Yang Benar, Pembedahan Pada Pasien Yang Benar',
  '5'=>'5. Mengurangi Risiko Infeksi Akibat Perawatan Kesehatan',
  '6'=>'6. Mengurangi Risiko Cidera Pasien Akibat Terjatuh'
];

// === Generate kode baru untuk modal tambah ===
$qLast=$conn->query("SELECT kode_kriteria FROM skp_kriteria_penilaian ORDER BY kode_kriteria DESC LIMIT 1");
$lastKode=$qLast->num_rows>0?$qLast->fetch_assoc()['kode_kriteria']:'KP00000000';
$num=intval(substr($lastKode,2))+1;
$newKode='KP'.str_pad($num,8,'0',STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kriteria Pengkajian SKP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="skp.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Kriteria Pengkajian Sasaran Keselamatan Pasien</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <!-- Tabel -->
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-master_skp align-middle">
            <thead class="table-dark text-center">
            <tr>
              <th>Kode Kriteria</th>
              <th>Kriteria</th>
              <th>Kode Kategori</th>
              <th>Kategori</th>
              <th>Sasaran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result->num_rows==0): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">Belum ada data</td></tr>
            <?php else: ?>
            <?php while($row=$result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['kode_kriteria'])?></td>
                <td><?= htmlspecialchars($row['nama_kriteria'])?></td>
                <td><?= htmlspecialchars($row['kode_kategori'])?></td>
                <td><?= htmlspecialchars($row['nama_kategori'])?></td>
                <td><?= $sasaranMap[$row['sasaran']]?></td>
                <td class="text-center">
                  <!-- Tombol Edit -->
                  <button class="btn btn-warning btn-sm"  
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEdit"
                          data-kode="<?= $row['kode_kriteria']?>" 
                          data-kriteria="<?= $row['nama_kriteria']?>" data-kategori="<?= $row['kode_kategori']?>">
                    ✏️ Edit
                  </button>
                  <!-- Tombol Hapus -->
                  <a href="?hapus=<?= urlencode($row['kode_kriteria']) ?>"
                     onclick="return confirm('Yakin hapus data ini?')" 
                     class="btn btn-danger btn-sm">
                    🗑️ Hapus
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">« Prev</a>
          </li>
          <?php
            $startPage = max(1, $page - 1);
            $endPage   = min($totalPages, $page + 1);
            for ($i = $startPage; $i <= $endPage; $i++):
          ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next »</a>
          </li>
        </ul>
      </nav>

    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg-custom"> <!-- modal-lg agar lebih lebar -->
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="tambah">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Kriteria Penilaian</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Baris 1 -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label>Kode Kriteria</label>
                <input type="text" name="kode_kriteria" class="form-control bg-danger text-white" 
                       value="<?= $newKode ?>" readonly>
              </div>
              <div class="col-md-8">
                <label>Kriteria</label>
                <input type="text" name="nama_kriteria" class="form-control" required>
              </div>
            </div>
            <!-- Baris 2 -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label>Kode</label>
                <select name="kode_kategori" id="add_kode_kategori" class="form-select" required>
                  <option value="">-- Pilih Kode/Kategori --</option>
                  <?php foreach($kategoriList as $kat): ?>
                    <option value="<?= $kat['kode_kategori']?>"
                            data-nama="<?= $kat['nama_kategori']?>"
                            data-sasaran="<?= $kat['sasaran']?>">
                      <?= $kat['kode_kategori']?> - <?= $kat['nama_kategori']?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label>Kategori</label>
                <input type="text" id="add_nama_kategori" class="form-control bg-danger text-white" readonly>
              </div>
              <div class="col-md-4">
                <label>Sasaran</label>
                <input type="text" id="add_sasaran" class="form-control bg-danger text-white" readonly>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg-custom"> <!-- modal-lg agar lebih lebar -->
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="aksi" value="update">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Kriteria Penilaian</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Baris 1 -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label>Kode Kriteria</label>
                <input type="text" name="kode_kriteria" id="edit_kode" 
                       class="form-control bg-danger text-white" readonly>
              </div>
              <div class="col-md-8">
                <label>Kriteria</label>
                <input type="text" name="nama_kriteria" id="edit_kriteria" class="form-control" required>
              </div>
            </div>
            <!-- Baris 2 -->
            <div class="row mb-3">
              <div class="col-md-4">
                <label>Kode</label>
                <select name="kode_kategori" id="edit_kode_kategori" class="form-select" required>
                  <?php foreach($kategoriList as $kat): ?>
                    <option value="<?= $kat['kode_kategori']?>"
                            data-nama="<?= $kat['nama_kategori']?>"
                            data-sasaran="<?= $kat['sasaran']?>">
                      <?= $kat['kode_kategori']?> - <?= $kat['nama_kategori']?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label>Kategori</label>
                <input type="text" id="edit_nama_kategori" class="form-control bg-danger text-white" readonly>
              </div>
              <div class="col-md-4">
                <label>Sasaran</label>
                <input type="text" id="edit_sasaran" class="form-control bg-danger text-white" readonly>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">🔄 Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", function(){
    // Modal Tambah: isi kategori & sasaran otomatis
    var addSelect=document.getElementById('add_kode_kategori');
    if(addSelect){
      addSelect.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        document.getElementById('add_nama_kategori').value=opt.getAttribute('data-nama')||'';
        var sas=opt.getAttribute('data-sasaran');
        document.getElementById('add_sasaran').value=sas?<?= json_encode($sasaranMap) ?>[sas]:'';
      });
    }

    // Modal Edit: isi field dari tombol
    var modalEdit=document.getElementById('modalEdit');
    modalEdit.addEventListener('show.bs.modal',function(event){
      var button=event.relatedTarget;
      var kode=button.getAttribute('data-kode');
      var kriteria=button.getAttribute('data-kriteria');
      var kategori=button.getAttribute('data-kategori');

      document.getElementById('edit_kode').value=kode;
      document.getElementById('edit_kriteria').value=kriteria;
      document.getElementById('edit_kode_kategori').value=kategori;

      var opt=document.querySelector('#edit_kode_kategori option[value="'+kategori+'"]');
      if(opt){
        document.getElementById('edit_nama_kategori').value=opt.getAttribute('data-nama')||'';
        var sas=opt.getAttribute('data-sasaran');
        document.getElementById('edit_sasaran').value=sas?<?= json_encode($sasaranMap) ?>[sas]:'';
      }
    });

    // Modal Edit: update kategori & sasaran saat dropdown berubah
    var editSelect=document.getElementById('edit_kode_kategori');
    if(editSelect){
      editSelect.addEventListener('change',function(){
        var opt=this.options[this.selectedIndex];
        document.getElementById('edit_nama_kategori').value=opt.getAttribute('data-nama')||'';
        var sas=opt.getAttribute('data-sasaran');
        document.getElementById('edit_sasaran').value=sas?<?= json_encode($sasaranMap) ?>[sas]:'';
      });
    }
  });

  </script>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
