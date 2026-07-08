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

// handler insert/update/hapus/reactivate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $kd_dokter = $_POST['kd_dokter'] ?? '';
    $gol  = $_POST['gol_drh'] ?? '';
    $agama= $_POST['agama'] ?? '';
    $nikah= $_POST['stts_nikah'] ?? '';
    $telp = $_POST['no_telp'] ?? '';
    $email= $_POST['email'] ?? '';
    $sps  = $_POST['kd_sps'] ?? '';
    $alumni = $_POST['alumni'] ?? '';
    $ijin   = $_POST['no_ijn_praktek'] ?? '';

    if ($mode === 'insert') {
        $pg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama,jk,tmp_lahir,tgl_lahir,alamat FROM pegawai WHERE nik='$kd_dokter'"));
        $nm_dokter = $pg['nama'];
        $jk        = ($pg['jk']=='Pria'?'L':'P');
        $tmp       = $pg['tmp_lahir'];
        $tgl       = $pg['tgl_lahir'];
        $alamat    = $pg['alamat'];

        $stmt = $conn->prepare("INSERT INTO dokter 
        (kd_dokter,nm_dokter,jk,tmp_lahir,tgl_lahir,gol_drh,agama,almt_tgl,no_telp,email,stts_nikah,kd_sps,alumni,no_ijn_praktek,status) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $status = '1'; // enum, jadi string
        $stmt->bind_param(
            "sssssssssssssss",
            $kd_dokter, $nm_dokter, $jk, $tmp, $tgl,
            $gol, $agama, $alamat, $telp, $email,
            $nikah, $sps, $alumni, $ijin, $status
        );
        $stmt->execute();

    } elseif ($mode === 'update') {
        $stmt = $conn->prepare("UPDATE dokter 
        SET gol_drh=?, agama=?, no_telp=?, email=?, stts_nikah=?, kd_sps=?, alumni=?, no_ijn_praktek=? 
        WHERE kd_dokter=?");
        $stmt->bind_param("sssssssss", $gol,$agama,$telp,$email,$nikah,$sps,$alumni,$ijin,$kd_dokter);
        $stmt->execute();
    } elseif ($mode === 'reactivate') {
        $stmt = $conn->prepare("UPDATE dokter SET status='1' WHERE kd_dokter=? AND status='0'");
        $stmt->bind_param("s", $kd_dokter);
        $stmt->execute();
    } elseif ($mode === 'hapus') {
        $stmt = $conn->prepare("UPDATE dokter SET status='0' WHERE kd_dokter=?");
        $stmt->bind_param("s", $kd_dokter);
        $stmt->execute();
    }
}

// pagination
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM dokter WHERE status='1'");
$totalRows = (int) mysqli_fetch_assoc($countResult)['total'];
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 1;

// ambil data dokter aktif
$sql = "SELECT d.kd_dokter,d.nm_dokter,d.jk,d.tmp_lahir,d.tgl_lahir,d.gol_drh,d.agama,
               d.almt_tgl,d.no_telp,d.email,d.stts_nikah,s.kd_sps,s.nm_sps,d.alumni,d.no_ijn_praktek
        FROM dokter d
        LEFT JOIN spesialis s ON d.kd_sps=s.kd_sps
        WHERE d.status='1'
        ORDER BY d.kd_dokter
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// dropdown spesialis
$spsArr = [];
$res = mysqli_query($conn, "SELECT kd_sps,nm_sps FROM spesialis ORDER BY kd_sps");
while($row = mysqli_fetch_assoc($res)) { $spsArr[] = $row; }

// dropdown pegawai untuk tambah dokter
$pegawaiArr = [];
$res = mysqli_query($conn, "SELECT p.nik, p.nama, p.jk, p.tmp_lahir, p.tgl_lahir, p.alamat, p.stts_aktif
                            FROM pegawai p
                            LEFT JOIN dokter d ON d.kd_dokter = p.nik
                            WHERE (d.kd_dokter IS NULL OR d.status NOT IN ('0','1'))
                              AND p.stts_aktif NOT IN ('CUTI','KELUAR','TENAGA LUAR','NON AKTIF')
                              AND (
                                   p.jbtn LIKE '%dokter%' 
                                   OR p.jbtn LIKE '%dr.%' 
                                   OR p.jbtn LIKE '%prof.%'
                                   OR p.nama LIKE 'dr.%'
                                   OR p.nama LIKE 'Dr.%'
                                   OR p.nama LIKE 'Prof.%'
                              )
                            ORDER BY p.nik");
while($row = mysqli_fetch_assoc($res)) { 
    $pegawaiArr[] = $row; 
}

// dropdown dokter untuk reaktivasi
$reactivateArr = [];
$res = mysqli_query($conn, "SELECT d.kd_dokter,d.nm_dokter,d.jk,d.tmp_lahir,d.tgl_lahir,d.gol_drh,d.agama,
                                   d.almt_tgl,d.no_telp,d.email,d.stts_nikah,s.nm_sps,d.alumni,d.no_ijn_praktek
                            FROM dokter d
                            LEFT JOIN spesialis s ON d.kd_sps=s.kd_sps
                            WHERE d.status='0'
                            ORDER BY d.kd_dokter");
while($row = mysqli_fetch_assoc($res)) { $reactivateArr[] = $row; }

// ambil filter spesialis dari GET
$filter = $_GET['spesialis'] ?? '';

$sql = "SELECT d.kd_dokter,d.nm_dokter,d.jk,d.tmp_lahir,d.tgl_lahir,d.gol_drh,d.agama,
               d.almt_tgl,d.no_telp,d.email,d.stts_nikah,s.kd_sps,s.nm_sps,d.alumni,d.no_ijn_praktek
        FROM dokter d
        LEFT JOIN spesialis s ON d.kd_sps=s.kd_sps
        WHERE d.status='1'";

// kalau ada filter spesialis
if (!empty($filter) && $filter !== 'ALL') {
    $sql .= " AND d.kd_sps='" . mysqli_real_escape_string($conn, $filter) . "'";
}

$sql .= " ORDER BY d.kd_dokter LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dokter</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Dokter</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">➕ Tambah</button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalReaktivasi">🔄 Reaktivasi</button>
          <a href="../index.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <div class="card-body p-3">

        <form method="get" class="mb-3">
          <label for="spesialis" class="form-label">Filter Spesialis:</label>
          <select name="spesialis" id="spesialis" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
            <option value="">-- Pilih Spesialis --</option>
            <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
            <?php foreach($spsArr as $s): ?>
              <option value="<?= htmlspecialchars($s['kd_sps']) ?>" <?= $filter===$s['kd_sps'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nm_sps']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>

        <div class="table-wrapper">
          <table class="table table-striped table-bordered table-dokter align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Kode Dokter</th><th>Nama Dokter</th><th>J.K</th><th>Tmp. Lahir</th><th>Tgl. Lahir</th>
                <th>G.D</th><th>Agama</th><th>Alamat</th><th>No. Telp</th><th>Email</th>
                <th>Stts. Nikah</th><th>Spesialis</th><th>Alumni</th><th>No. Ijin Praktek</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($filter)): ?>
                <tr>
                  <td colspan="13" class="text-center text-muted">
                    Silakan pilih spesialis untuk menampilkan data
                  </td>
                </tr>
              <?php elseif (mysqli_num_rows($result) === 0): ?>
                <tr>
                  <td colspan="13" class="text-center text-muted">
                    Tidak ada data dokter
                  </td>
                </tr>
              <?php else: ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['kd_dokter']) ?></td>
                  <td><?= htmlspecialchars($row['nm_dokter']) ?></td>
                  <td><?= htmlspecialchars($row['jk']) ?></td>
                  <td><?= htmlspecialchars($row['tmp_lahir']) ?></td>
                  <td><?= htmlspecialchars($row['tgl_lahir']) ?></td>
                  <td><?= htmlspecialchars($row['gol_drh']) ?></td>
                  <td><?= htmlspecialchars($row['agama']) ?></td>
                  <td><?= htmlspecialchars($row['almt_tgl']) ?></td>
                  <td><?= htmlspecialchars($row['no_telp']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['stts_nikah']) ?></td>
                  <td><?= htmlspecialchars($row['nm_sps']) ?></td>
                  <td><?= htmlspecialchars($row['alumni']) ?></td>
                  <td><?= htmlspecialchars($row['no_ijn_praktek']) ?></td>
                  <td class="text-center">
                    <button class="btn btn-warning btn-sm"
                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                            data-kd_dokter="<?= htmlspecialchars($row['kd_dokter']) ?>"
                            data-nama="<?= htmlspecialchars($row['nm_dokter']) ?>"
                            data-jk="<?= htmlspecialchars($row['jk']) ?>"
                            data-tmp="<?= htmlspecialchars($row['tmp_lahir']) ?>"
                            data-tgl="<?= htmlspecialchars($row['tgl_lahir']) ?>"
                            data-gol="<?= htmlspecialchars($row['gol_drh']) ?>"
                            data-agama="<?= htmlspecialchars($row['agama']) ?>"
                            data-alamat="<?= htmlspecialchars($row['almt_tgl']) ?>"
                            data-telp="<?= htmlspecialchars($row['no_telp']) ?>"
                            data-email="<?= htmlspecialchars($row['email']) ?>"
                            data-nikah="<?= htmlspecialchars($row['stts_nikah']) ?>"
                            data-sps="<?= htmlspecialchars($row['kd_sps'] ?? '') ?>"
                            data-alumni="<?= htmlspecialchars($row['alumni']) ?>"
                            data-ijin="<?= htmlspecialchars($row['no_ijn_praktek']) ?>">
                      ✏️ Update
                    </button>
                    <form action="" method="post" style="display:inline">
                      <input type="hidden" name="mode" value="hapus">
                      <input type="hidden" name="kd_dokter" value="<?= htmlspecialchars($row['kd_dokter']) ?>">
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
              <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&spesialis=<?= urlencode($filter) ?>">Prev</a>
            </li>

            <!-- Nomor Halaman -->
            <?php for ($i = max(1, $page - 1); $i <= min($totalPages, $page + 1); $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&spesialis=<?= urlencode($filter) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <!-- Tombol Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>&spesialis=<?= urlencode($filter) ?>">Next</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>

      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>

<!-- Modal Tambah Dokter -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" class="modal-content">
      <input type="hidden" name="mode" value="insert">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Dokter</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Kode Dokter (NIP Pegawai)</label>
          <select name="kd_dokter" id="addKdDokter" class="form-select" required>
            <option value="">-- Pilih Dokter --</option>
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
          <label>Nama Dokter</label>
          <input type="text" id="addNama" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Jenis Kelamin</label>
          <input type="text" id="addJk" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Tmp/Tgl. Lahir</label>
          <input type="text" id="addTmpTgl" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Gol. Darah</label>
          <select name="gol_drh" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option>A</option><option>B</option><option>O</option><option>AB</option><option>-</option>
          </select>
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
          <label>Alamat Tinggal</label>
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
          <label>Spesialis</label>
          <select name="kd_sps" class="form-select" required>
            <option value="">-- Pilih Spesialis --</option>
            <?php foreach($spsArr as $sps): ?>
              <option value="<?= htmlspecialchars($sps['kd_sps']) ?>"><?= htmlspecialchars($sps['nm_sps']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label>Alumni</label>
          <input type="text" name="alumni" class="form-control">
        </div>
        <div class="mb-3">
          <label>No. Ijin Praktek</label>
          <input type="text" name="no_ijn_praktek" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Dokter -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" class="modal-content">
      <input type="hidden" name="mode" value="update">
      <input type="hidden" name="kd_dokter" id="editKdDokter">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Update Dokter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Kode Dokter</label>
          <input type="text" id="editKdDokterText" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Nama Dokter</label>
          <input type="text" id="editNama" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Jenis Kelamin</label>
          <input type="text" id="editJk" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Tmp/Tgl. Lahir</label>
          <input type="text" id="editTmpTgl" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>Gol. Darah</label>
          <select name="gol_drh" id="editGol" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option>A</option><option>B</option><option>O</option><option>AB</option><option>-</option>
          </select>
        </div>
        <div class="mb-3">
          <label>Agama</label>
          <select name="agama" id="editAgama" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option>ISLAM</option><option>KRISTEN</option><option>KATOLIK</option>
            <option>HINDU</option><option>BUDHA</option><option>KONG HU CHU</option><option>-</option>
          </select>
        </div>
        <div class="mb-3">
          <label>Status Nikah</label>
          <select name="stts_nikah" id="editNikah" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option>BELUM MENIKAH</option><option>MENIKAH</option><option>JANDA</option>
            <option>DUDHA</option><option>JOMBLO</option>
          </select>
        </div>
        <div class="mb-3">
          <label>Alamat Tinggal</label>
          <input type="text" id="editAlamat" class="form-control bg-danger text-white fw-bold" readonly>
        </div>
        <div class="mb-3">
          <label>No. Telp</label>
          <input type="text" name="no_telp" id="editTelp" class="form-control">
        </div>
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" id="editEmail" class="form-control">
        </div>
        <div class="mb-3">
          <label>Spesialis</label>
          <select name="kd_sps" id="editSps" class="form-select" required>
            <option value="">-- Pilih Spesialis --</option>
            <?php foreach($spsArr as $sps): ?>
              <option value="<?= htmlspecialchars($sps['kd_sps']) ?>"><?= htmlspecialchars($sps['nm_sps']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label>Alumni</label>
          <input type="text" name="alumni" id="editAlumni" class="form-control">
        </div>
        <div class="mb-3">
          <label>No. Ijin Praktek</label>
          <input type="text" name="no_ijn_praktek" id="editIjin" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reaktivasi Dokter -->
<div class="modal fade" id="modalReaktivasi" tabindex="-1">
  <div class="modal-dialog">
    <form action="" method="post" class="modal-content">
      <input type="hidden" name="mode" value="reactivate">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Reaktivasi Dokter</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih Dokter</label>
          <select name="kd_dokter" id="reactivateKdDokter" class="form-select" required>
            <option value="">-- Pilih Dokter --</option>
            <?php foreach($reactivateArr as $rt): ?>
              <option value="<?= htmlspecialchars($rt['kd_dokter']) ?>"
                      data-nama="<?= htmlspecialchars($rt['nm_dokter']) ?>"
                      data-jk="<?= htmlspecialchars($rt['jk']) ?>"
                      data-tmp="<?= htmlspecialchars($rt['tmp_lahir']) ?>"
                      data-tgl="<?= htmlspecialchars($rt['tgl_lahir']) ?>"
                      data-gol="<?= htmlspecialchars($rt['gol_drh']) ?>"
                      data-agama="<?= htmlspecialchars($rt['agama']) ?>"
                      data-alamat="<?= htmlspecialchars($rt['almt_tgl']) ?>"
                      data-telp="<?= htmlspecialchars($rt['no_telp']) ?>"
                      data-email="<?= htmlspecialchars($rt['email']) ?>"
                      data-nikah="<?= htmlspecialchars($rt['stts_nikah']) ?>"
                      data-sps="<?= htmlspecialchars($rt['nm_sps']) ?>"
                      data-alumni="<?= htmlspecialchars($rt['alumni']) ?>"
                      data-ijin="<?= htmlspecialchars($rt['no_ijn_praktek']) ?>">
                <?= htmlspecialchars($rt['kd_dokter']) ?> - <?= htmlspecialchars($rt['nm_dokter']) ?> - <?= htmlspecialchars($rt['nm_sps']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label>Nama Dokter</label><input type="text" id="reactivateNama" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Jenis Kelamin</label><input type="text" id="reactivateJk" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Tmp/Tgl. Lahir</label><input type="text" id="reactivateTmpTgl" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Gol. Darah</label><input type="text" id="reactivateGol" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Agama</label><input type="text" id="reactivateAgama" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Status Nikah</label><input type="text" id="reactivateNikah" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Alamat</label><input type="text" id="reactivateAlamat" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>No. Telp</label><input type="text" id="reactivateTelp" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Email</label><input type="text" id="reactivateEmail" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Spesialis</label><input type="text" id="reactivateSps" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>Alumni</label><input type="text" id="reactivateAlumni" class="form-control bg-danger text-white fw-bold" readonly></div>
        <div class="mb-3"><label>No. Ijin Praktek</label><input type="text" id="reactivateIjin" class="form-control bg-danger text-white fw-bold" readonly></div>
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
  // Auto-fill Tambah Dokter
  var addKdDokter = document.getElementById('addKdDokter');
  if (addKdDokter) {
    addKdDokter.addEventListener('change', function() {
      var opt = this.options[this.selectedIndex];
      document.getElementById('addNama').value   = opt.getAttribute('data-nama') || '';
      document.getElementById('addJk').value     = opt.getAttribute('data-jk') || '';
      document.getElementById('addTmpTgl').value = (opt.getAttribute('data-tmp') || '') + ' / ' + (opt.getAttribute('data-tgl') || '');
      document.getElementById('addAlamat').value = opt.getAttribute('data-alamat') || '';
    });
  }

  // Auto-fill Reaktivasi Dokter
  var reactivateKdDokter = document.getElementById('reactivateKdDokter');
  if (reactivateKdDokter) {
    reactivateKdDokter.addEventListener('change', function() {
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
      document.getElementById('reactivateSps').value    = opt.getAttribute('data-sps') || '';
      document.getElementById('reactivateAlumni').value = opt.getAttribute('data-alumni') || '';
      document.getElementById('reactivateIjin').value   = opt.getAttribute('data-ijin') || '';
    });
  }

  // Auto-fill Edit Dokter
  var modalEdit = document.getElementById('modalEdit');
  if (modalEdit) {
    modalEdit.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('editKdDokter').value = button.getAttribute('data-kd_dokter') || '';
      document.getElementById('editKdDokterText').value = button.getAttribute('data-kd_dokter') || '';
      document.getElementById('editNama').value = button.getAttribute('data-nama') || '';
      document.getElementById('editJk').value = button.getAttribute('data-jk') || '';
      document.getElementById('editTmpTgl').value = (button.getAttribute('data-tmp') || '') + ' / ' + (button.getAttribute('data-tgl') || '');
      document.getElementById('editGol').value = button.getAttribute('data-gol') || '';
      document.getElementById('editAgama').value = button.getAttribute('data-agama') || '';
      document.getElementById('editNikah').value = button.getAttribute('data-nikah') || '';
      document.getElementById('editAlamat').value = button.getAttribute('data-alamat') || '';
      document.getElementById('editTelp').value = button.getAttribute('data-telp') || '';
      document.getElementById('editEmail').value = button.getAttribute('data-email') || '';
      document.getElementById('editSps').value = button.getAttribute('data-sps') || '';
      document.getElementById('editAlumni').value = button.getAttribute('data-alumni') || '';
      document.getElementById('editIjin').value   = button.getAttribute('data-ijin') || '';
    });
  }
</script>
</body>
</html>
