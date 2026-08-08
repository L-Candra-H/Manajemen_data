<?php
session_start();
include __DIR__ . '/conf/conf.php';
include __DIR__ . '/conf/auth.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

// Hanya administrator yang boleh masuk
if ($_SESSION["hak_akses"] !== "administrator") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Administrator</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/index.css">
  <link rel="stylesheet" href="layout/header.css">
</head>
<body>
<?php include __DIR__ . '/layout/header.php'; ?>

<main class="main-content container-fluid mt-3"> 
  <div class="row justify-content-center">

    <!-- Modul Administrator -->
    <div class="col-md-6 mb-3">
      <div class="card shadow h-100 text-center border-danger">
        <div class="card-body">
          <h5 class="card-title fw-bold text-danger">⚙️ Modul Administrator</h5>
          <p class="text-muted mb-0 small">Menu khusus administrator</p>

          <!-- Isi sementara -->
          <a href="pengaturan/user.php" class="btn btn-outline-danger btn-sm mt-3">👤 User Menu Kepegawaian</a>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/layout/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
