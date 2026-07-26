<?php
session_start();
include __DIR__ . '/conf/conf.php';

if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ambil id_user dari session gabungan "Nama (id_user)"
    $id_user_session = $_SESSION['user_login'];
    if (preg_match('/\((.*?)\)$/', $id_user_session, $matches)) {
        $id_user = $matches[1]; // hasil: id_user murni
    } else {
        $id_user = $id_user_session; // fallback
    }

    $old_pass     = $_POST['passworde'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm_pass) {
        header("Location: index.php?error=KonfirmasiPasswordTidakSama");
        exit;
    }

    // cek password lama
    $stmt = $conn->prepare("SELECT AES_DECRYPT(password,'windi') AS pass 
                            FROM user 
                            WHERE AES_DECRYPT(id_user,'nur')=?");
    $stmt->bind_param("s", $id_user);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['pass'] === $old_pass) {
        // update password baru
        $update = $conn->prepare("UPDATE user 
                                  SET password=AES_ENCRYPT(?,'windi') 
                                  WHERE AES_DECRYPT(id_user,'nur')=?");
        $update->bind_param("ss", $new_pass, $id_user);
        if ($update->execute()) {
            session_destroy();
            header("Location: login.php?info=PasswordBerhasilDiubahSilakanLogin");
            exit;
        } else {
            die("Update gagal: " . $conn->error);
        }
    } else {
        header("Location: index.php?error=PasswordLamaSalah");
        exit;
    }
}
?>
