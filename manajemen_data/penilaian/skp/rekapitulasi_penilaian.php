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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rekapitulasi Pengkajian Petugas/Dokter Dalam Implementasi SKP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../layout/header.css">
  <link rel="stylesheet" href="skp.css">
</head>
<body>
  <?php include __DIR__ . '/../../layout/header.php'; ?>

  <main class="container-fluid mt-4">
    <div class="card shadow">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-uppercase flex-grow-1 text-center">Rekapitulasi Pengkajian Petugas/Dokter Dalam Implementasi Sasaran Keselamatan Pasien</h5>
        <div class="d-flex gap-2">
          <a href="../../index_penilaian.php" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
        </div>
      </div>

      <!-- Tambahan UNDER CONSTRUCTION -->
      <div class="card-body text-center">
        <h1 class="display-3 fw-bold text-danger">"UNDER CONSTRUCTION"</h1>
      </div>

    </div>
  </main>

  <?php include __DIR__ . '/../../layout/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

