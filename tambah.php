<?php
session_start();
require 'config/database.php'; // Hubungkan ke database

// 1. CEK APAKAH USER SUDAH LOGIN
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login+terlebih+dahulu");
    exit;
}

$error = '';
$sukses = '';

// 2. PROSES FORM JIKA DI-SUBMIT (METHOD POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Ambil data form
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $target_donasi = trim($_POST['target_donasi']);
    
    // Variabel untuk nama file gambar
    $nama_gambar = null; 

    // Validasi data teks
    if (empty($judul) || empty($deskripsi) || empty($target_donasi)) {
        $error = "Semua field (judul, deskripsi, target donasi) wajib diisi!";
    
    } else if (!is_numeric($target_donasi) || $target_donasi <= 0) {
        $error = "Target donasi harus berupa angka positif!";
    
    // 3. PROSES VALIDASI GAMBAR (JIKA ADA YANG DIUPLOAD)
    } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        
        $file = $_FILES['gambar'];
        $nama_asli = $file['name'];
        $ukuran_file = $file['size'];
        $lokasi_tmp = $file['tmp_name'];
        
        // Cek ekstensi file
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi_file, $ekstensi_diizinkan)) {
            $error = "Format gambar tidak diizinkan! (Hanya JPG, JPEG, PNG)";
        
        // Cek ukuran file (maks 2MB)
        } else if ($ukuran_file > 5 * 1024 * 1024) { 
            $error = "Ukuran gambar terlalu besar! (Maks 5MB)";
        
        } else {
            // 4. FILE LOLOS VALIDASI
            // Buat nama file unik (timestamp-namaunik.ext)
            $nama_gambar = time() . '-' . uniqid() . '.' . $ekstensi_file;
            
            // Tentukan path tujuan upload
            $path_tujuan = 'uploads/' . $nama_gambar;
            
            // Pindahkan file dari folder sementara ke folder 'uploads'
            if (!move_uploaded_file($lokasi_tmp, $path_tujuan)) {
                $error = "Gagal memindahkan file gambar.";
                $nama_gambar = null; // Gagalkan proses
            }
        }
    }
    
    // 5. SIMPAN KE DATABASE (JIKA TIDAK ADA ERROR)
    if (empty($error)) {
        try {
            // Query INSERT sekarang menyertakan kolom 'gambar'
            $sql = "INSERT INTO program_donasi (judul, deskripsi, target_donasi, gambar) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            
            // Eksekusi dengan 4 parameter
            $stmt->execute([$judul, $deskripsi, $target_donasi, $nama_gambar]);
            
            $sukses = "Program donasi baru berhasil ditambahkan!";
            // Kosongkan form setelah sukses
            $_POST = []; 

        } catch (PDOException $e) {
            $error = "Gagal menyimpan data ke database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Program Donasi - GoDonate</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .form-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-container h1 { color: #0066cc; text-align: center; margin-bottom: 30px; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease; min-height: 150px; font-family: "Segoe UI", Arial, sans-serif; }
        .form-group textarea:focus { border-color: #0066cc; outline: none; }
        .message { padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .error-message { background: #f8d7da; color: #dc3545; }
        .success-message { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <?php include 'navigation.php'; ?>

    <div class="form-container">
        <h1>Tambah Program Donasi Baru</h1>
        
        <?php if (!empty($error)): ?>
            <p class="message error-message"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($sukses)): ?>
            <p class="message success-message"><?= htmlspecialchars($sukses); ?></p>
        <?php endif; ?>

        <form method="POST" action="tambah.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="judul">Judul Program:</label>
                <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($_POST['judul'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi:</label>
                <textarea id="deskripsi" name="deskripsi" required><?= htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="target_donasi">Target Donasi (Rp):</label>
                <input type="number" id="target_donasi" name="target_donasi" placeholder="Contoh: 10000000" value="<?= htmlspecialchars($_POST['target_donasi'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="gambar">Gambar Program:</label>
                <input type="file" id="gambar" name="gambar" accept="image/jpeg, image/png">
                <small>Format yang diizinkan: JPG, PNG. Ukuran maks: 5MB.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Simpan Program</button>
            <a href="dashboard.php" class="btn btn-secondary" style="width: 100%; padding: 12px; margin-top: 10px;">Kembali ke dashboard</a>
        </form>
    </div>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>