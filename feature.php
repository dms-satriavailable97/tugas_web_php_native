<?php
session_start();
require 'config/database.php';

// 1. Cek Login Admin
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login");
    exit;
}

// 2. Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: dashboard.php?message=ID+tidak+valid");
    exit;
}

try {
    // 3. (PENTING) Reset semua program lain jadi TIDAK utama
    // Ini memastikan hanya ada SATU program utama
    $sql_reset = "UPDATE program_donasi SET is_featured = 0 WHERE is_featured = 1";
    $pdo->query($sql_reset);

    // 4. Jadikan program yang dipilih sebagai program utama (is_featured = 1)
    $sql_feature = "UPDATE program_donasi SET is_featured = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql_feature);
    $stmt->execute([$id]);

    // 5. Kembalikan ke dashboard
    header("Location: dashboard.php?message=Program+utama+berhasil+diperbarui!");
    exit;

} catch (PDOException $e) {
    header("Location: dashboard.php?message=Gagal+memperbarui+data.");
    exit;
}
?>