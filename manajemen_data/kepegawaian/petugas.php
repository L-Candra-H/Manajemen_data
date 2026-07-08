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

// handler insert/update/hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $nip  = $_POST['nip'] ?? '';
    $gol  = $_POST['gol_darah'] ?? '';
    $agama= $_POST['agama'] ?? '';
    $nikah= $_POST['stts_nikah'] ?? '';
    $telp = $_POST['no_telp'] ?? '';
    $email= $_POST['email'] ?? '';
    $jbtn = $_POST['kd_jbtn'] ?? '';

    if ($mode === 'insert') {
        // ambil data pegawai
        $pg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama,jk,tmp_lahir,tgl_lahir,alamat FROM pegawai WHERE nik='$nip'"));
        $nama   = $pg['nama'];
        $jk     = ($pg['jk']=='Pria'?'L':'P');
        $tmp    = $pg['tmp_lahir'];
        $tgl    = $pg['tgl_lahir'];
        $alamat = $pg['alamat'];

        $stmt = $conn->prepare("INSERT INTO petugas 
        (nip,nama,jk,tmp_lahir,tgl_lahir,gol_darah,agama,stts_nikah,alamat,kd_jbtn,no_telp,email,status) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $status = '1'; // enum, jadi string
        $stmt->bind_param(
            "sssssssssssss",
            $nip, $nama, $jk, $tmp, $tgl,
            $gol, $agama, $nikah, $alamat,
            $jbtn, $telp, $email, $status
        );
        $stmt->execute();

    } elseif ($mode === 'update') {
        $stmt = $conn->prepare("UPDATE petugas 
        SET gol_darah=?, agama=?, stts_nikah=?, no_telp=?, email=?, kd_jbtn=? 
        WHERE nip=?");
        $stmt->bind_param("sssssss", $gol, $agama, $nikah, $telp, $email, $jbtn, $nip);
        $stmt->execute();
    } elseif ($mode === 'reactivate') {
        $stmt = $conn->prepare("UPDATE petugas SET status='1' WHERE nip=? AND status='0'");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
    } elseif ($mode === 'hapus') {
        $stmt = $conn->prepare("UPDATE petugas SET status='0' WHERE nip=?");
        $stmt->bind_param("s", $nip);
        $stmt->execute();
    }
}

// pagination
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM petugas WHERE status='1'");
$totalRows = (int) mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 1;

// ambil data petugas aktif
$sql = "SELECT pt.nip, pt.nama, pt.jk, pt.tmp_lahir, pt.tgl_lahir, pt.gol_darah,
               pt.agama, pt.stts_nikah, pt.alamat, jb.kd_jbtn, jb.nm_jbtn, pt.no_telp, pt.email
        FROM petugas pt
        LEFT JOIN jabatan jb ON pt.kd_jbtn=jb.kd_jbtn
        WHERE pt.status='1'
        ORDER BY pt.nip
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// dropdown jabatan
$jbtnArr = [];
$res = mysqli_query($conn, "SELECT kd_jbtn, nm_jbtn FROM jabatan ORDER BY kd_jbtn");
while($row = mysqli_fetch_assoc($res)) { $jbtnArr[] = $row; }

// dropdown pegawai untuk tambah
$pegawaiArr = [];
$res = mysqli_query($conn, "SELECT p.nik, p.nama, p.jk, p.tmp_lahir, p.tgl_lahir, p.alamat, p.stts_aktif
                            FROM pegawai p
                            LEFT JOIN petugas pt ON pt.nip = p.nik
                            WHERE p.jbtn NOT LIKE '%dokter%'
                              AND (pt.nip IS NULL OR pt.status NOT IN ('0','1'))
                              AND p.stts_aktif NOT IN ('CUTI','KELUAR','TENAGA LUAR','NON AKTIF')
                            ORDER BY p.nik");
while($row = mysqli_fetch_assoc($res)) { 
    $pegawaiArr[] = $row; 
}

// dropdown petugas untuk reaktivasi
$reactivateArr = [];
$res = mysqli_query($conn, "SELECT pt.nip, pt.nama, pt.jk, pt.tmp_lahir, pt.tgl_lahir,
                                   pt.gol_darah, pt.agama, pt.stts_nikah, pt.alamat,
                                   jb.nm_jbtn, pt.no_telp, pt.email
                            FROM petugas pt
                            LEFT JOIN jabatan jb ON pt.kd_jbtn = jb.kd_jbtn
                            WHERE pt.status='0'
                              AND jb.nm_jbtn NOT LIKE '%dokter%'
                              AND jb.nm_jbtn NOT LIKE '%dr.%'
                              AND jb.nm_jbtn NOT LIKE '%dok%'
                              AND jb.nm_jbtn NOT LIKE '%Prof.%'
                            ORDER BY pt.nip");
while($row = mysqli_fetch_assoc($res)) { 
    $reactivateArr[] = $row; 
}

// ambil filter jabatan dari GET
$filter = $_GET['jabatan'] ?? '';

$sql = "SELECT d.nip,d.nama,d.jk,d.tmp_lahir,d.tgl_lahir,d.gol_darah,d.agama,
               d.stts_nikah, d.alamat, s.nm_jbtn, d.no_telp, d.email
        FROM petugas d
        LEFT JOIN jabatan s ON d.kd_jbtn=s.kd_jbtn
        WHERE d.status='1'";

// kalau ada filter jabatan
if (!empty($filter) && $filter !== 'ALL') {
    $sql .= " AND d.kd_jbtn='" . mysqli_real_escape_string($conn, $filter) . "'";
}

$sql .= " ORDER BY d.nip LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Petugas</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Petugas</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalReaktivasi">🔄 Reaktivasi</button>
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

        <form method="get" class="mb-3">
          <label for="jabatan" class="form-label">Filter Jabatan:</label>
          <select name="jabatan" id="jabatan" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Jabatan --</option>
            <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php foreach($jbtnArr as $s): ?>
              <option value="<?= htmlspecialchars($s['kd_jbtn']) ?>" <?= $filter===$s['kd_jbtn'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nm_jbtn']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-petugas align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>NIP</th><th>Nama Petugas</th><th>J.K</th><th>Tmp. Lahir</th><th>Tgl. Lahir</th>
                <th>G.D</th><th>Agama</th><th>Stts. Nikah</th><th>Alamat</th><th>Jabatan</th>
                <th>No. Telp</th><th>Email</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($filter)): ?>
                <tr>
                  <td colspan="13" class="text-center text-muted">
                    Silakan pilih jabatan untuk menampilkan data
                  </td>
                </tr>
              <?php elseif (mysqli_num_rows($result) === 0): ?>
                <tr>
                  <td colspan="13" class="text-center text-muted">
                    Tidak ada data petugas
                  </td>
                </tr>
              <?php else: ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['nip']) ?></td>
                  <td><?= htmlspecialchars($row['nama']) ?></td>
                  <td><?= htmlspecialchars($row['jk']) ?></td>
                  <td><?= htmlspecialchars($row['tmp_lahir']) ?></td>
                  <td><?= htmlspecialchars($row['tgl_lahir']) ?></td>
                  <td><?= htmlspecialchars($row['gol_darah']) ?></td>
                  <td><?= htmlspecialchars($row['agama']) ?></td>
                  <td><?= htmlspecialchars($row['stts_nikah']) ?></td>
                  <td><?= htmlspecialchars($row['alamat']) ?></td>
                  <td><?= htmlspecialchars($row['nm_jbtn']) ?></td>
                  <td><?= htmlspecialchars($row['no_telp']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td class="text-center">
                    <button class="btn btn-warning btn-sm"
                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                            data-nip="<?= htmlspecialchars($row['nip']) ?>"
                            data-nama="<?= htmlspecialchars($row['nama']) ?>"
                            data-jk="<?= htmlspecialchars($row['jk']) ?>"
                            data-tmp="<?= htmlspecialchars($row['tmp_lahir']) ?>"
                            data-tgl="<?= htmlspecialchars($row['tgl_lahir']) ?>"
                            data-gol="<?= htmlspecialchars($row['gol_darah']) ?>"
                            data-agama="<?= htmlspecialchars($row['agama']) ?>"
                            data-nikah="<?= htmlspecialchars($row['stts_nikah']) ?>"
                            data-alamat="<?= htmlspecialchars($row['alamat']) ?>"
                            data-telp="<?= htmlspecialchars($row['no_telp']) ?>"
                            data-email="<?= htmlspecialchars($row['email']) ?>"
                            data-kd-jbtn="<?= htmlspecialchars($row['kd_jbtn'] ?? '') ?>">
                      ✏️ Update
                    </button>
                    <form action="" method="post" style="display:inline">
                      <input type="hidden" name="mode" value="hapus">
                      <input type="hidden" name="nip" value="<?= htmlspecialchars($row['nip']) ?>">
                      <button type="submit" class="btn btn-danger btn-sm">🗑 Hapus</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages >= 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination justify-content-center">
            <!-- Tombol Prev -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&jabatan=<?= urlencode($filter) ?>">Prev</a>
            </li>

            <!-- Nomor Halaman -->
            <?php for ($i = max(1, $page - 1); $i <= min($totalPages, $page + 1); $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&jabatan=<?= urlencode($filter) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&jabatan=<?= urlencode($filter) ?>">Next</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>

      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="insert">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Tambah Petugas</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih NIP - Petugas</label>
            <select name="nip" id="addNip" class="form-select" required>
              <option value="">-- Pilih Petugas --</option>
              <?php foreach($pegawaiArr as $pg): ?>
                <option value="<?= htmlspecialchars($pg['nik']) ?>"
                        data-nama="<?= htmlspecialchars($pg['nama']) ?>"
                        data-jk="<?= ($pg['jk']=='Pria'?'L':'P') ?>"
                        data-tmp="<?= htmlspecialchars($pg['tmp_lahir']) ?>"
                        data-tgl="<?= htmlspecialchars($pg['tgl_lahir']) ?>"
                        data-alamat="<?= htmlspecialchars($pg['alamat']) ?>">
                  <?= htmlspecialchars($pg['nik']) ?> - <?= htmlspecialchars($pg['nama']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Nama Petugas</label>
            <input type="text" id="addNama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label>Jenis Kelamin</label>
            <input type="text" id="addJk" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label>Gol. Darah</label>
            <select name="gol_darah" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>A</option><option>B</option><option>O</option><option>AB</option><option>-</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Tmp/Tgl. Lahir</label>
            <input type="text" id="addTmpTgl" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label>Agama</label>
            <select name="agama" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>ISLAM</option><option>KRISTEN</option><option>KATOLIK</option>
              <option>HINDU</option><option>BUDHA</option><option>KONG HU CHU</option><option>-</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Status Nikah</label>
            <select name="stts_nikah" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>BELUM MENIKAH</option><option>MENIKAH</option><option>JANDA</option>
              <option>DUDHA</option><option>JOMBLO</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Alamat</label>
            <input type="text" id="addAlamat" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label>No. Telp</label>
            <input type="text" name="no_telp" class="form-control">
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="mb-3">
            <label>Jabatan</label>
            <select name="kd_jbtn" class="form-select" required>
              <option value="">-- Pilih Jabatan --</option>
              <?php foreach($jbtnArr as $jb): ?>
                <option value="<?= htmlspecialchars($jb['kd_jbtn']) ?>"><?= htmlspecialchars($jb['nm_jbtn']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="update">
        <input type="hidden" name="nip" id="editNip">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Update Petugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">NIP</label>
            <input type="text" id="editNipText" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" id="editNama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Jenis Kelamin</label>
            <input type="text" id="editJk" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Tmp/Tgl. Lahir</label>
            <input type="text" id="editTmpTgl" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Gol. Darah</label>
            <select name="gol_darah" id="editGol" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>A</option><option>B</option><option>O</option><option>AB</option><option>-</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Agama</label>
            <select name="agama" id="editAgama" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>ISLAM</option><option>KRISTEN</option><option>KATOLIK</option>
              <option>HINDU</option><option>BUDHA</option><option>KONG HU CHU</option><option>-</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status Nikah</label>
            <select name="stts_nikah" id="editNikah" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option>BELUM MENIKAH</option><option>MENIKAH</option><option>JANDA</option>
              <option>DUDHA</option><option>JOMBLO</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <input type="text" id="editAlamat" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">No. Telp</label>
            <input type="text" name="no_telp" id="editTelp" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="editEmail" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Jabatan</label>
            <select name="kd_jbtn" id="editJbtn" class="form-select" required>
              <option value="">-- Pilih Jabatan --</option>
              <?php foreach($jbtnArr as $jb): ?>
                <option value="<?= htmlspecialchars($jb['kd_jbtn']) ?>"><?= htmlspecialchars($jb['nm_jbtn']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="modalReaktivasi" tabindex="-1">
    <div class="modal-dialog">
      <form action="" method="post" class="modal-content">
        <input type="hidden" name="mode" value="reactivate">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title">Reaktivasi Petugas</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Pilih Petugas</label>
            <select name="nip" id="reactivateNip" class="form-select" required>
              <option value="">-- Pilih Petugas --</option>
              <?php foreach($reactivateArr as $rt): ?>
                <option value="<?= htmlspecialchars($rt['nip']) ?>"
                        data-nama="<?= htmlspecialchars($rt['nama']) ?>"
                        data-jk="<?= htmlspecialchars($rt['jk']) ?>"
                        data-tmp="<?= htmlspecialchars($rt['tmp_lahir']) ?>"
                        data-tgl="<?= htmlspecialchars($rt['tgl_lahir']) ?>"
                        data-gol="<?= htmlspecialchars($rt['gol_darah']) ?>"
                        data-agama="<?= htmlspecialchars($rt['agama']) ?>"
                        data-nikah="<?= htmlspecialchars($rt['stts_nikah']) ?>"
                        data-alamat="<?= htmlspecialchars($rt['alamat']) ?>"
                        data-telp="<?= htmlspecialchars($rt['no_telp']) ?>"
                        data-email="<?= htmlspecialchars($rt['email']) ?>"
                        data-kd-jbtn="<?= htmlspecialchars($rt['nm_jbtn'] ?? '') ?>">
                  <?= htmlspecialchars($rt['nip']) ?> - <?= htmlspecialchars($rt['nama']) ?> - <?= htmlspecialchars($rt['nm_jbtn']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Petugas</label>
            <input type="text" id="reactivateNama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Jenis Kelamin</label>
            <input type="text" id="reactivateJk" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Tmp/Tgl. Lahir</label>
            <input type="text" id="reactivateTmpTgl" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Gol. Darah</label>
            <input type="text" id="reactivateGol" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Agama</label>
            <input type="text" id="reactivateAgama" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Status Nikah</label>
            <input type="text" id="reactivateNikah" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <input type="text" id="reactivateAlamat" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">No. Telp</label>
            <input type="text" id="reactivateTelp" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="text" id="reactivateEmail" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Jabatan</label>
            <input type="text" id="reactivateJbtn" class="form-control bg-danger text-white fw-bold" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">🔄 Reaktivasi</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

  <script>
    var addNip = document.getElementById('addNip');
    if (addNip) {
      addNip.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        document.getElementById('addNama').value   = opt.getAttribute('data-nama') || '';
        document.getElementById('addJk').value     = opt.getAttribute('data-jk') || '';
        document.getElementById('addTmpTgl').value = (opt.getAttribute('data-tmp') || '') + ' / ' + (opt.getAttribute('data-tgl') || '');
        document.getElementById('addAlamat').value = opt.getAttribute('data-alamat') || '';
      });
    }

    var reactivasiNip = document.getElementById('reactivateNip');
    if (reactivasiNip) {
      reactivasiNip.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        document.getElementById('reactivateNama').value   = opt.getAttribute('data-nama') || '';
        document.getElementById('reactivateJk').value     = opt.getAttribute('data-jk') || '';
        document.getElementById('reactivateTmpTgl').value = (opt.getAttribute('data-tmp') || '') + ' / ' + (opt.getAttribute('data-tgl') || '');
        document.getElementById('reactivateGol').value    = opt.getAttribute('data-gol') || '';
        document.getElementById('reactivateAgama').value  = opt.getAttribute('data-agama') || '';
        document.getElementById('reactivateNikah').value  = opt.getAttribute('data-nikah') || '';
        document.getElementById('reactivateAlamat').value = opt.getAttribute('data-alamat') || '';
        document.getElementById('reactivateTelp').value   = opt.getAttribute('data-telp') || '';
        document.getElementById('reactivateEmail').value  = opt.getAttribute('data-email') || '';
        document.getElementById('reactivateJbtn').value   = opt.getAttribute('data-kd-jbtn') || '';
      });
    }

    var modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
      modalEdit.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editNip').value = button.getAttribute('data-nip') || '';
        document.getElementById('editNipText').value = button.getAttribute('data-nip') || '';
        document.getElementById('editNama').value = button.getAttribute('data-nama') || '';
        document.getElementById('editJk').value = button.getAttribute('data-jk') || '';
        document.getElementById('editTmpTgl').value = (button.getAttribute('data-tmp') || '') + ' / ' + (button.getAttribute('data-tgl') || '');
        document.getElementById('editGol').value = button.getAttribute('data-gol') || '';
        document.getElementById('editAgama').value = button.getAttribute('data-agama') || '';
        document.getElementById('editNikah').value = button.getAttribute('data-nikah') || '';
        document.getElementById('editAlamat').value = button.getAttribute('data-alamat') || '';
        document.getElementById('editTelp').value = button.getAttribute('data-telp') || '';
        document.getElementById('editEmail').value = button.getAttribute('data-email') || '';
        document.getElementById('editJbtn').value = button.getAttribute('data-kd-jbtn') || '';
      });
    }
  </script>
</body>
</html>
