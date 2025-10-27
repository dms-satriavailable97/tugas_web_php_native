<?php
session_start();
require 'config/database.php';

// 1. Cek Login Admin
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login");
    exit;
}

// 2. Ambil ID dan validasi
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: kelola_donatur.php?message=ID+tidak+valid");
    exit;
}

try {
    // 3. (PENTING) Ambil nama file foto SEBELUM dihapus dari DB
    $sql_select = "SELECT foto FROM donatur WHERE id = ?";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([$id]);
    $donatur = $stmt_select->fetch();
    $foto_lama = $donatur['foto'] ?? null;

    // 4. Hapus data dari database
    $sql_delete = "DELETE FROM donatur WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id]);
    
    // 5. Jika delete DB berhasil, HAPUS file foto dari server
    if ($stmt_delete->rowCount() > 0 && !empty($foto_lama)) {
        $path_foto = 'uploads/donatur/' . $foto_lama;
        if (file_exists($path_foto)) {
            unlink($path_foto);
        }
    }
    
    header("Location: kelola_donatur.php?message=Data+donatur+berhasil+dihapus!");
    exit;

} catch (PDOException $e) {
    header("Location: kelola_donatur.php?message=Gagal+menghapus+data.");
    exit;
}
?>