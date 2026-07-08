<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// fungsi cek hak akses berdasarkan session
function cekAkses($hak) {
    return !empty($_SESSION[$hak]) && $_SESSION[$hak] == true;
}
?>
