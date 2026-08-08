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

// jika ada request hapus
if (isset($_GET['hapus_id']) && isset($_GET['hapus_tanggal'])) {
    $hapusId      = mysqli_real_escape_string($conn, $_GET['hapus_id']);
    $hapusTanggal = mysqli_real_escape_string($conn, $_GET['hapus_tanggal']);

    // hapus pinjaman
    $sqlDel = "DELETE FROM peminjaman_koperasi
               WHERE id='$hapusId' AND tanggal='$hapusTanggal'";
    mysqli_query($conn, $sqlDel);

    // hapus semua angsuran terkait pinjaman tersebut
    $sqlDelAngsuran = "DELETE FROM angsuran_koperasi
                       WHERE id='$hapusId' AND tanggal_pinjam='$hapusTanggal'";
    mysqli_query($conn, $sqlDelAngsuran);

    // redirect agar tidak reload GET berulang
    header("Location: detail_pinjaman_koperasi.php?id=$hapusId");
    exit;
}

// array bulan untuk label
$bulanArr = [
  1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
  5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
  9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

// ambil id pegawai dari GET
$idPegawai = $_GET['id'] ?? '';
if (!$idPegawai) {
    echo "<div class='alert alert-danger'>Pegawai tidak ditemukan.</div>";
    exit;
}

// ambil data pegawai
$qPegawai = mysqli_query($conn,"SELECT nik,nama FROM pegawai WHERE id='".mysqli_real_escape_string($conn,$idPegawai)."'");
$pegawai  = mysqli_fetch_assoc($qPegawai);

// pagination setup
$limit  = 6;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// hitung total data
$sqlCount = "SELECT COUNT(*) AS total 
             FROM peminjaman_koperasi 
             WHERE id='".mysqli_real_escape_string($conn,$idPegawai)."' 
               AND status='Belum Lunas'";
$resCount = mysqli_query($conn,$sqlCount);
$rowCount = mysqli_fetch_assoc($resCount);
$jmlData  = $rowCount['total'] ?? 0;
$totalPages = $jmlData > 0 ? ceil($jmlData / $limit) : 1;

// jika ada request tambah pinjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjaman'])) {
    $idPegawai     = mysqli_real_escape_string($conn, $_POST['id']);
    $tanggalPinjam = $_POST['yyyy'].'-'.str_pad($_POST['mm'],2,'0',STR_PAD_LEFT).'-'.str_pad($_POST['dd'],2,'0',STR_PAD_LEFT);
    $banyakAngsur  = intval($_POST['banyak_angsur']);
    $pinjaman      = intval($_POST['pinjaman']);

    // hitung pokok & jasa sesuai aturan koperasi
    $pokok = floor($pinjaman / $banyakAngsur);

    // contoh: jasa 1% dari pinjaman
    $jasa  = floor($pinjaman * 0.01);

    $sqlIns = "INSERT INTO peminjaman_koperasi 
               (id, tanggal, pinjaman, banyak_angsur, pokok, jasa, status)
               VALUES ('$idPegawai','$tanggalPinjam','$pinjaman','$banyakAngsur','$pokok','$jasa','Belum Lunas')";
    mysqli_query($conn, $sqlIns);

    header("Location: detail_pinjaman_koperasi.php?id=$idPegawai");
    exit;
}

// ambil data pinjaman
$sql = "SELECT pk.id AS pegawai_id, pk.tanggal, pk.pinjaman, 
               pk.banyak_angsur, pk.pokok, pk.jasa, pk.status,
               p.nik, p.nama
        FROM peminjaman_koperasi pk
        JOIN pegawai p ON pk.id = p.id
        WHERE pk.id='".mysqli_real_escape_string($conn,$idPegawai)."'
          AND pk.status='Belum Lunas'
        ORDER BY pk.tanggal ASC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn,$sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Pinjaman Koperasi</title>
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
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">
          Detail Pinjaman Koperasi | <?= htmlspecialchars($pegawai['nik']).' - '.htmlspecialchars($pegawai['nama']) ?>
        </h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <a href="peminjaman_koperasi.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">
        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-koperasi align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Tgl. Pinjam</th>
                <th>Pinjaman</th>
                <th>Jml. Angsur</th>
                <th>Pokok</th>
                <th>Jasa</th>
                <th>Angsuran</th>
                <th>Status</th>
                <th>Proses</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
              <?php while($row = mysqli_fetch_assoc($result)): 
                  $pokok    = $row['pinjaman'] / $row['banyak_angsur'];
                  $jasa     = $row['pinjaman'] * 0.01;
                  $angsuran = $pokok + $jasa;
              ?>
                <tr>
                  <td><?= htmlspecialchars($row['tanggal']) ?></td>
                  <td><?= number_format($row['pinjaman'],0,',','.') ?></td>
                  <td><?= $row['banyak_angsur'] ?></td>
                  <td><?= number_format($pokok,0,',','.') ?></td>
                  <td><?= number_format($jasa,0,',','.') ?></td>
                  <td><?= number_format($angsuran,0,',','.') ?></td>
                  <td><?= $row['status'] ?></td>
                  <td class="text-center">
                      <a href="rincian_angsuran.php?id=<?= $row['pegawai_id'] ?>&tanggal=<?= urlencode($row['tanggal']) ?>" 
                         class="btn btn-sm btn-info">Detail</a>
                      <a href="detail_pinjaman_koperasi.php?hapus_id=<?= $row['pegawai_id'] ?>&hapus_tanggal=<?= urlencode($row['tanggal']) ?>" 
                         class="btn btn-sm btn-danger" 
                         onclick="return confirm('Yakin hapus pinjaman ini?')">🗑️ Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center">Tidak ada pinjaman Belum Lunas</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Info Data -->
        <div class="mt-2 small text-start text-muted">
          Data : <?= $jmlData ?>,
        </div>

        <!-- Pagination -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?id=<?= $idPegawai ?>&page=<?= max(1, $page - 1) ?>">« Prev</a>
              </li>
              <?php
                $start = max(1, $page - 1);
                $end   = min($totalPages, $page + 1);
                for ($i = $start; $i <= $end; $i++):
              ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?id=<?= $idPegawai ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?id=<?= $idPegawai ?>&page=<?= min($totalPages, $page + 1) ?>">Next »</a>
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
      <form method="post" class="modal-content">
        <input type="hidden" name="id" value="<?= $idPegawai ?>">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Pinjaman</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIP</label>
            <input type="text" class="form-control bg-danger" value="<?= htmlspecialchars($pegawai['nik']) ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control bg-danger" value="<?= htmlspecialchars($pegawai['nama']) ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Pinjam</label>
            <div class="d-flex gap-2">
              <!-- Hari -->
              <select name="dd" id="dd" class="form-select" style="max-width:80px;">
                <!-- default isi 1–31, nanti disesuaikan via JS -->
                <?php for($d=1;$d<=31;$d++): ?>
                  <option value="<?= $d ?>"><?= str_pad($d,2,'0',STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
              </select>

              <!-- Bulan -->
              <select name="mm" id="mm" class="form-select" style="max-width:120px;">
                <?php foreach($bulanArr as $num=>$label): ?>
                  <option value="<?= $num ?>"><?= $label ?></option>
                <?php endforeach; ?>
              </select>

              <!-- Tahun -->
              <select name="yyyy" id="yyyy" class="form-select" style="max-width:100px;">
                <?php for($y=date('Y'); $y>=1960; $y--): ?>
                  <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Angsuran</label>
            <div class="input-group">
              <input type="number" name="banyak_angsur" class="form-control" min="1" required>
              <span class="input-group-text">x</span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Pinjaman</label>
            <input type="number" name="pinjaman" class="form-control" min="0" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
        </div>
      </form>
    </div>
  </div>
  <!-- End Modal Tambah -->

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
  function updateDays() {
    const month = parseInt(document.getElementById('mm').value);
    const year  = parseInt(document.getElementById('yyyy').value);
    const daysInMonth = new Date(year, month, 0).getDate(); // hitung jumlah hari

    const ddSelect = document.getElementById('dd');
    ddSelect.innerHTML = '';
    for (let d=1; d<=daysInMonth; d++) {
      const opt = document.createElement('option');
      opt.value = d;
      opt.text = d.toString().padStart(2,'0');
      ddSelect.appendChild(opt);
    }
  }

  // trigger saat bulan/tahun berubah
  document.getElementById('mm').addEventListener('change', updateDays);
  document.getElementById('yyyy').addEventListener('change', updateDays);

  // panggil sekali saat load
  updateDays();
  </script>

</body>
</html>

