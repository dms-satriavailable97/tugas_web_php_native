<?php
// 1. Sertakan file koneksi database
require 'config/database.php';

// 2. Ambil ID dari URL
// Kita pastikan ID-nya adalah angka (integer) untuk keamanan tambahan
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika ID tidak valid (0 atau tidak ada), hentikan skrip
if ($id <= 0) {
    die("ID program donasi tidak valid.");
}

// 3. Query untuk mengambil SATU program donasi berdasarkan ID
try {
    // Sesuai syarat: Wajib pakai PDO Prepared Statements
    $sql = "SELECT * FROM program_donasi WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    // Eksekusi query dengan ID yang sudah aman
    $stmt->execute([$id]);
    
    // Ambil datanya (gunakan fetch() bukan fetchAll() karena kita cuma butuh 1 baris)
    $program = $stmt->fetch();

    // 4. Cek apakah data ditemukan
    if (!$program) {
        // Jika ID tidak ada di database
        die("Program donasi tidak ditemukan.");
    }

} catch (\PDOException $e) {
    // Tangani error jika query gagal
    die("Gagal mengambil data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($program['judul']); ?> - GoDonate</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* Style untuk halaman detail */
        .detail-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden; /* Biar gambar tidak keluar dari rounded corner */
        }
        
        .detail-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
        }

        .detail-content {
            padding: 30px 40px;
        }

        .detail-content h1 {
            color: #0066cc;
            text-align: left;
            margin-bottom: 20px;
        }
        
        /* Ubah <p> deskripsi agar bisa menampilkan baris baru (line break) */
        .detail-description {
            font-size: 1.1em;
            color: #333;
            line-height: 1.7;
            white-space: pre-wrap; /* Ini kuncinya! */
        }

        .detail-stats {
            margin-top: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #0066cc;
        }

        .detail-stats p {
            font-size: 1.2em;
            margin: 10px 0;
        }
        
        .detail-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

    </style>
</head>
<body>
    
    <?php include 'navigation.php'; ?>

    <main>
        <div class="detail-container">
            
            <?php if (!empty($program['gambar'])): ?>
                <img src="uploads/<?= htmlspecialchars($program['gambar']); ?>" alt="<?= htmlspecialchars($program['judul']); ?>" class="detail-image">
            <?php else: ?>
                <img src="assets/donasi.jpg" alt="Placeholder Donasi" class="detail-image">
            <?php endif; ?>

            <div class="detail-content">
                <h1><?= htmlspecialchars($program['judul']); ?></h1>
                
                <p class="detail-description">
                    <?= htmlspecialchars($program['deskripsi']); ?>
                </p>

                <div class="detail-stats">
                    <p>
                        <strong>Terkumpul:</strong> 
                        <span style="color: #0066cc; font-weight: bold;">
                            Rp <?= number_format($program['terkumpul'], 0, ',', '.'); ?>
                        </span>
                    </p>
                    <p>
                        <strong>Target Donasi:</strong> 
                        Rp <?= number_format($program['target_donasi'], 0, ',', '.'); ?>
                    </p>
                </div>

                <div class="detail-actions">
                    <a href="https://wa.me/+628997800507" class="btn btn-primary">Donasi Sekarang via WhatsApp</a>
                    
                    <a href="index.php#donasi" class="btn btn-secondary">Kembali ke Daftar</a>
                </div>
            </div>
        </div>
    </main>

    
    
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>