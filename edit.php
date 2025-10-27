<?php
session_start();
require 'config/database.php';

// 1. Cek Login
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login+terlebih+dahulu");
    exit;
}

$error = '';
$sukses = '';

// 2. Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: dashboard.php?message=ID+tidak+valid");
    exit;
}

// 3. PROSES FORM JIKA DI-SUBMIT (METHOD POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Ambil data form
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $target_donasi = trim($_POST['target_donasi']);
    $terkumpul = trim($_POST['terkumpul']);
    // Ambil nama gambar lama dari input hidden
    $nama_gambar_lama = $_POST['gambar_lama'] ?? null;
    $nama_gambar = $nama_gambar_lama; // Default: pakai gambar lama

    // Validasi data teks
    if (empty($judul) || empty($deskripsi) || empty($target_donasi) || $terkumpul === '') {
        $error = "Semua field wajib diisi!";
    } else if (!is_numeric($target_donasi) || $target_donasi <= 0) {
        $error = "Target donasi harus berupa angka positif!";
    } else if (!is_numeric($terkumpul) || $terkumpul < 0) {
        $error = "Donasi terkumpul harus berupa angka (minimal 0)!";
    }
    
    // 4. PROSES JIKA ADA GAMBAR BARU DIUPLOAD
    else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        
        $file = $_FILES['gambar'];
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi_file, $ekstensi_diizinkan)) {
            $error = "Format gambar tidak diizinkan! (Hanya JPG, JPEG, PNG)";
        } else if ($file['size'] > 5 * 1024 * 1024) { 
            $error = "Ukuran gambar terlalu besar! (Maks 5MB)";
        } else {
            // 5. FILE BARU LOLOS VALIDASI
            // Buat nama unik baru
            $nama_gambar_baru = time() . '-' . uniqid() . '.' . $ekstensi_file;
            $path_tujuan_baru = 'uploads/' . $nama_gambar_baru;
            
            // Pindahkan file baru
            if (move_uploaded_file($file['tmp_name'], $path_tujuan_baru)) {
                
                // HAPUS GAMBAR LAMA (JIKA ADA)
                if (!empty($nama_gambar_lama) && file_exists('uploads/' . $nama_gambar_lama)) {
                    unlink('uploads/' . $nama_gambar_lama);
                }
                
                // Set $nama_gambar ke nama file yang BARU
                $nama_gambar = $nama_gambar_baru; 
                
            } else {
                $error = "Gagal memindahkan file gambar baru.";
            }
        }
    }
    
    // 6. UPDATE DATABASE (JIKA TIDAK ADA ERROR)
    if (empty($error)) {
        try {
            // Query UPDATE sekarang menyertakan kolom 'gambar'
            $sql = "UPDATE program_donasi 
                    SET judul = ?, deskripsi = ?, target_donasi = ?, terkumpul = ?, gambar = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            // $nama_gambar bisa jadi nama LAMA atau nama BARU
            $stmt->execute([$judul, $deskripsi, $target_donasi, $terkumpul, $nama_gambar, $id]);
            
            header("Location: dashboard.php?message=Data+berhasil+diperbarui!");
            exit;

        } catch (PDOException $e) {
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
    
    // Jika ada error, data $program akan diambil dari $_POST
    $program = [
        'id' => $id, 'judul' => $_POST['judul'], 'deskripsi' => $_POST['deskripsi'],
        'target_donasi' => $_POST['target_donasi'], 'terkumpul' => $_POST['terkumpul'],
        'gambar' => $nama_gambar_lama // Tetap tampilkan gambar lama jika update gagal
    ];

} else {
    
    // 7. JIKA BUKAN POST (METHOD GET), AMBIL DATA LAMA DARI DB
    try {
        $sql = "SELECT * FROM program_donasi WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $program = $stmt->fetch();

        if (!$program) {
            header("Location: dashboard.php?message=Data+tidak+ditemukan");
            exit;
        }
    } catch (PDOException $e) {
        die("Gagal mengambil data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Program Donasi - GoDonate</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .form-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-container h1 { color: #0066cc; text-align: center; margin-bottom: 30px; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease; min-height: 150px; font-family: "Segoe UI", Arial, sans-serif; }
        .form-group textarea:focus { border-color: #0066cc; outline: none; }
        .message { padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .error-message { background: #f8d7da; color: #dc3545; }
    </style>
</head>
<body>

    <?php include 'navigation.php'; ?>

    <div class="form-container">
        <h1>Edit Program: <?= htmlspecialchars($program['judul']); ?></h1>
        
        <?php if (!empty($error)): ?>
            <p class="message error-message"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="edit.php?id=<?= $program['id']; ?>" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="judul">Judul Program:</label>
                <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($program['judul']); ?>" required>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi:</label>
                <textarea id="deskripsi" name="deskripsi" required><?= htmlspecialchars($program['deskripsi']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="target_donasi">Target Donasi (Rp):</label>
                <input type="number" id="target_donasi" name="target_donasi" value="<?= htmlspecialchars($program['target_donasi']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="terkumpul">Donasi Terkumpul (Rp):</label>
                <input type="number" id="terkumpul" name="terkumpul" value="<?= htmlspecialchars($program['terkumpul']); ?>" required>
            </div>

            <div class="form-group">
                <label for="gambar">Ganti Gambar Program:</label>
                <input type="file" id="gambar" name="gambar" accept="image/jpeg, image/png">
                <small>Kosongkan jika tidak ingin mengganti gambar. Maks 2MB.</small>
            </div>

            <?php if (!empty($program['gambar'])): ?>
                <div class="form-group">
                    <label>Gambar Saat Ini:</label>
                    <img src="uploads/<?= htmlspecialchars($program['gambar']); ?>" alt="Gambar saat ini" style="width: 200px; height: auto; border-radius: 8px;">
                    <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($program['gambar']); ?>">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Update Program</button>
            <a href="dashboard.php" class="btn btn-secondary" style="width: 100%; padding: 12px; margin-top: 10px;">Batal</a>
        </form>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>