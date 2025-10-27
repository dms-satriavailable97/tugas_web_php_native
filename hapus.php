<?php
session_start();
require 'config/database.php';

// 1. Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login+terlebih+dahulu");
    exit;
}

// 2. Ambil ID dari URL dan validasi
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: dashboard.php?message=ID+tidak+valid");
    exit;
}

// 3. Proses Hapus Data
try {
    // Gunakan Prepared Statements untuk DELETE (Wajib)
    $sql = "DELETE FROM program_donasi WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    // Eksekusi query dengan ID
    $stmt->execute([$id]);
    
    // Redirect kembali ke dashboard dengan pesan sukses
    header("Location: dashboard.php?message=Data+berhasil+dihapus!");
    exit;

} catch (PDOException $e) {
    // Jika gagal, redirect dengan pesan error
    // (Pesan $e->getMessage() tidak boleh ditampilkan di produksi)
    header("Location: dashboard.php?message=Gagal+menghapus+data.");
    exit;
}

?>