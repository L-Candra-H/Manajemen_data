<?php
include __DIR__ . '/../conf/auth.php';
include __DIR__ . '/../conf/conf.php';

if (!cekAkses('pegawai_admin') && !cekAkses('pegawai_user')) {
    echo "<div class='alert alert-danger'>Akses ditolak. Anda tidak memiliki hak ke menu Pegawai.</div>";
    exit;
}

// ambil filter dari query string
$filter = isset($_GET['stts_aktif']) ? $_GET['stts_aktif'] : '';
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 6; // jumlah baris per halaman
$offset = ($page - 1) * $limit;

$q = null;
$totalRows = 0;
$totalPages = 0;

if ($filter !== '') {
    if ($filter === 'ALL') {
        $sqlCount = "SELECT COUNT(*) as total FROM pegawai";
        $sqlData  = "SELECT pegawai.*, 
                            kelompok_jabatan.nama_kelompok,
                            jnj_jabatan.nama as nama_jenjang,
                            departemen.nama as nama_departemen,
                            resiko_kerja.nama_resiko,
                            emergency_index.nama_emergency,
                            stts_wp.ktg as stts_wp_ktg,
                            stts_kerja.ktg as stts_kerja_ktg
                     FROM pegawai
                     LEFT JOIN kelompok_jabatan ON pegawai.kode_kelompok = kelompok_jabatan.kode_kelompok
                     LEFT JOIN jnj_jabatan ON pegawai.jnj_jabatan = jnj_jabatan.kode
                     LEFT JOIN departemen ON pegawai.departemen = departemen.dep_id
                     LEFT JOIN bidang ON pegawai.bidang = bidang.nama
                     LEFT JOIN resiko_kerja ON pegawai.kode_resiko = resiko_kerja.kode_resiko
                     LEFT JOIN emergency_index ON pegawai.kode_emergency = emergency_index.kode_emergency
                     LEFT JOIN stts_wp ON pegawai.stts_wp = stts_wp.stts
                     LEFT JOIN stts_kerja ON pegawai.stts_kerja = stts_kerja.stts
                     ORDER BY pegawai.nik ASC LIMIT $limit OFFSET $offset";
    } else {
        $safeFilter = mysqli_real_escape_string($conn, $filter);
        $sqlCount = "SELECT COUNT(*) as total FROM pegawai WHERE stts_aktif='$safeFilter'";
        $sqlData  = "SELECT pegawai.*, 
                            kelompok_jabatan.nama_kelompok,
                            jnj_jabatan.nama as nama_jenjang,
                            departemen.nama as nama_departemen,
                            resiko_kerja.nama_resiko,
                            emergency_index.nama_emergency,
                            stts_wp.ktg as stts_wp_ktg,
                            stts_kerja.ktg as stts_kerja_ktg
                     FROM pegawai
                     LEFT JOIN kelompok_jabatan ON pegawai.kode_kelompok = kelompok_jabatan.kode_kelompok
                     LEFT JOIN jnj_jabatan ON pegawai.jnj_jabatan = jnj_jabatan.kode
                     LEFT JOIN departemen ON pegawai.departemen = departemen.dep_id
                     LEFT JOIN resiko_kerja ON pegawai.kode_resiko = resiko_kerja.kode_resiko
                     LEFT JOIN emergency_index ON pegawai.kode_emergency = emergency_index.kode_emergency
                     LEFT JOIN stts_wp ON pegawai.stts_wp = stts_wp.stts
                     LEFT JOIN stts_kerja ON pegawai.stts_kerja = stts_kerja.stts
                     WHERE pegawai.stts_aktif='$safeFilter'
                     ORDER BY pegawai.nik ASC LIMIT $limit OFFSET $offset";
    }
    $resultCount = mysqli_query($conn, $sqlCount);
    if ($resultCount) {
        $totalRows  = mysqli_fetch_assoc($resultCount)['total'];
        $totalPages = ceil($totalRows / $limit);
    }
    $q = mysqli_query($conn, $sqlData);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Pegawai</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../layout/header.css">
  <link rel="stylesheet" href="pegawai.css">
</head>
<body>
  <?php include __DIR__ . '/../layout/header.php'; ?>

  <main class="main-content">
    <div class="container-fluid mt-4">
      <div class="card shadow">
        <!-- HEADER -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-uppercase text-center flex-grow-1">Data Pegawai</h5>
          <div class="d-flex gap-2">
            <?php if(cekAkses('pegawai_admin')): ?>
              <a href="tambah_pegawai.php" class="btn btn-light btn-sm">➕ Tambah Pegawai</a>
            <?php endif; ?>
            <a href="../index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
          </div>
        </div>

        <!-- BODY -->
        <div class="card-body p-3">
          <!-- Dropdown filter -->
          <form method="get" class="mb-3">
            <label for="stts_aktif" class="form-label">Filter Status Aktif:</label>
            <select name="stts_aktif" id="stts_aktif" class="form-select form-select-sm" style="max-width:220px;display:inline-block;">
              <option value="">-- Pilih Status --</option>
              <option value="ALL" <?= $filter==='ALL' ? 'selected' : '' ?>>Pilih Semua</option>
              <option value="AKTIF" <?= $filter==='AKTIF' ? 'selected' : '' ?>>AKTIF</option>
              <option value="CUTI" <?= $filter==='CUTI' ? 'selected' : '' ?>>CUTI</option>
              <option value="KELUAR" <?= $filter==='KELUAR' ? 'selected' : '' ?>>KELUAR</option>
              <option value="TENAGA LUAR" <?= $filter==='TENAGA LUAR' ? 'selected' : '' ?>>TENAGA LUAR</option>
              <option value="NON AKTIF" <?= $filter==='NON AKTIF' ? 'selected' : '' ?>>NON AKTIF</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
          </form>

          <!-- Wrapper untuk scroll -->
          <div class="table-wrapper">
            <table class="table table-striped table-bordered table-pegawai align-middle">
              <thead>
                <tr>
                  <th>NIP</th>
                  <th>Nama</th>
                  <th>J.K</th>
                  <th>Jabatan</th>
                  <th>Jenjang</th>
                  <th>Kelompok Jabatan</th>
                  <th>Departemen</th>
                  <th>Bagian</th>
                  <th>Resiko Kerja</th>
                  <th>Tingkat Emergency</th>
                  <th>Status WP</th>
                  <th>Status Karyawan</th>
                  <th>NPWP</th>
                  <th>Pendidikan</th>
                  <th>Tmp. Lahir</th>
                  <th>Tgl. Lahir</th>
                  <th>Alamat</th>
                  <th>Kota</th>
                  <th>Mulai Kerja</th>
                  <th>Masa Kerja</th>
                  <th>Kode Index</th>
                  <th>Bank</th>
                  <th>Rekening</th>
                  <th>Stts Aktif</th>
                  <th>Wajib Masuk</th>
                  <th>Mulai Kontrak</th>
                  <th>Photo</th>
                  <th>No KTP</th>
                  <?php if(cekAkses('pegawai_admin')): ?>
                    <th>Aksi</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php if (!$q): ?>
                  <tr><td colspan="33" class="text-center text-muted">Silakan pilih status aktif untuk menampilkan data</td></tr>
                <?php elseif (mysqli_num_rows($q) === 0): ?>
                  <tr><td colspan="33" class="text-center text-muted">Tidak ada data pegawai</td></tr>
                <?php else: ?>
                  <?php while($row = mysqli_fetch_assoc($q)): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['jk']) ?></td>
                    <td><?= htmlspecialchars($row['jbtn']) ?></td>
                    <td><?= htmlspecialchars($row['nama_jenjang'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_kelompok'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_departemen'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['bidang']) ?></td>
                    <td><?= htmlspecialchars($row['nama_resiko'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_emergency'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['stts_wp_ktg'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['stts_kerja_ktg'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['npwp']) ?></td>
                    <td><?= htmlspecialchars($row['pendidikan']) ?></td>
                    <td><?= htmlspecialchars($row['tmp_lahir']) ?></td>
                    <td><?= htmlspecialchars($row['tgl_lahir']) ?></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td><?= htmlspecialchars($row['kota']) ?></td>
                    <td><?= htmlspecialchars($row['mulai_kerja']) ?></td>
                    <td><?= htmlspecialchars($row['ms_kerja']) ?></td>
                    <td><?= htmlspecialchars($row['indexins']) ?></td>
                    <td><?= htmlspecialchars($row['bpd']) ?></td>
                    <td><?= htmlspecialchars($row['rekening']) ?></td>
                    <td><?= htmlspecialchars($row['stts_aktif']) ?></td>
                    <td><?= htmlspecialchars($row['wajibmasuk']) ?></td>
                    <td><?= htmlspecialchars($row['mulai_kontrak']) ?></td>
                    <td class="text-center">
                      <?php if (!empty($row['photo'])): ?>
                        <?php 
                          $appFolder = explode('/', trim($_SERVER['REQUEST_URI'], '/'))[0];
                          $path = "/" . $appFolder . "/webapps/penggajian/" . htmlspecialchars($row['photo']);
                        ?>
                        <!-- Thumbnail -->
                        <img src="<?= $path ?>" alt="Foto Pegawai" class="img-thumbnail" style="width:35px;height:35px;cursor:pointer"
                             data-bs-toggle="modal" data-bs-target="#lihatFoto<?= $row['id'] ?>">

                        <!-- Modal -->
                        <div class="modal fade" id="lihatFoto<?= $row['id'] ?>" tabindex="-1">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Foto Pegawai</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body text-center">
                                <img src="<?= $path ?>" alt="Foto Pegawai" class="img-fluid">
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">No Photo</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['no_ktp']) ?></td>
                    <?php if(cekAkses('pegawai_admin')): ?>
                      <td><a href="edit_pegawai.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a></td>
                    <?php endif; ?>
                  </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination ringkas -->
          <?php if ($filter !== '' && $totalPages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <!-- Prev -->
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?stts_aktif=<?= $filter ?>&page=<?= max(1, $page-1) ?>">Prev</a>
              </li>

              <?php
              // tampilkan max 3 halaman terdekat
              $start = max(1, $page - 1);
              $end   = min($totalPages, $page + 1);
              for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?stts_aktif=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <!-- Next -->
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?stts_aktif=<?= $filter ?>&page=<?= min($totalPages, $page+1) ?>">Next</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../layout/footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
