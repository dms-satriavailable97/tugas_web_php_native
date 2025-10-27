<?php
/*
 * File: config/database.php
 * Konfigurasi koneksi database PDO
 */

// Pengaturan koneksi database
$host = '127.0.0.1'; // atau 'localhost'
$db   = 'godonate_db'; // Nama database yang kita buat
$user = 'root';       // Username database Anda (default XAMPP/Laragon)
$pass = '';           // Password database Anda (default XAMPP/Laragon)
$charset = 'utf8mb4'; // Charset yang disarankan

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opsi untuk PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mode error: lempar exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mode fetch: array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Matikan emulasi prepared statements
];

try {
     // Membuat instance PDO
     $pdo = new PDO($dsn, $user, $pass, $options);
     
} catch (\PDOException $e) {
     /*
      * Sesuai spesifikasi:
      * Tampilkan pesan error informatif, BUKAN stack trace.
      * Hentikan eksekusi skrip.
      */
     // die("Koneksi database gagal: " . $e->getMessage()); // <- JANGAN LAKUKAN INI DI PRODUKSI
     
     // Pesan yang lebih aman:
     http_response_code(500); // Internal Server Error
     die("Terjadi masalah dengan koneksi database. Silakan coba lagi nanti.");
}

// Variabel $pdo sekarang siap digunakan di file manapun yang meng-include file ini.
?>