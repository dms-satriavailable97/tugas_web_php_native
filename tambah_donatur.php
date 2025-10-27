<?php
session_start();
require 'config/database.php'; // Hubungkan ke database

// 1. CEK APAKAH USER SUDAH LOGIN (ADMIN)
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login+terlebih+dahulu");
    exit;
}

$error = '';
$sukses = '';

// 2. PROSES FORM JIKA DI-SUBMIT (METHOD POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Ambil data form
    $nama = trim($_POST['nama']);
    $jumlah_donasi = trim($_POST['jumlah_donasi']);
    $pesan_singkat = trim($_POST['pesan_singkat']);
    
    // Variabel untuk nama file foto
    $nama_foto = null; 

    // Validasi data teks
    if (empty($nama) || empty($jumlah_donasi)) {
        $error = "Nama Donatur dan Jumlah Donasi wajib diisi!";
    
    } else if (!is_numeric($jumlah_donasi) || $jumlah_donasi <= 0) {
        $error = "Jumlah donasi harus berupa angka positif!";
    
    // 3. PROSES VALIDASI FOTO (JIKA ADA YANG DIUPLOAD)
    } else if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        
        $file = $_FILES['foto'];
        $nama_asli = $file['name'];
        $ukuran_file = $file['size'];
        $lokasi_tmp = $file['tmp_name'];
        
        // Cek ekstensi file
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi_file, $ekstensi_diizinkan)) {
            $error = "Format foto tidak diizinkan! (Hanya JPG, JPEG, PNG)";
        
        // Cek ukuran file (maks 2MB)
        } else if ($ukuran_file > 2 * 1024 * 1024) { 
            $error = "Ukuran foto terlalu besar! (Maks 2MB)";
        
        } else {
            // 4. FILE LOLOS VALIDASI
            // Buat nama file unik
            $nama_foto = time() . '-' . uniqid() . '.' . $ekstensi_file;
            
            // Tentukan path tujuan upload (SESUAI PERMINTAAN ANDA)
            $path_tujuan = 'uploads/donatur/' . $nama_foto;
            
            // Pindahkan file dari folder sementara ke folder 'uploads/donatur/'
            if (!move_uploaded_file($lokasi_tmp, $path_tujuan)) {
                $error = "Gagal memindahkan file foto.";
                $nama_foto = null; // Gagalkan proses
            }
        }
    }
    
    // 5. SIMPAN KE DATABASE (JIKA TIDAK ADA ERROR)
    if (empty($error)) {
        try {
            // Query INSERT ke tabel 'donatur'
            $sql = "INSERT INTO donatur (nama, jumlah_donasi, pesan_singkat, foto) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            
            // Eksekusi dengan 4 parameter
            $stmt->execute([$nama, $jumlah_donasi, $pesan_singkat, $nama_foto]);
            
            $sukses = "Donatur baru berhasil ditambahkan!";
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
    <title>Tambah Donatur Baru - GoDonate</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .form-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-container h1 { color: #006cc; text-align: center; margin-bottom: 30px; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease; min-height: 100px; font-family: "Segoe UI", Arial, sans-serif; }
        .form-group textarea:focus { border-color: #0066cc; outline: none; }
        .message { padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .error-message { background: #f8d7da; color: #dc3545; }
        .success-message { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <?php include 'navigation.php'; ?>

    <div class="form-container">
        <h1>Tambah Donatur Baru</h1>
        
        <?php if (!empty($error)): ?>
            <p class="message error-message"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($sukses)): ?>
            <p class="message success-message"><?= htmlspecialchars($sukses); ?></p>
        <?php endif; ?>

        <form method="POST" action="tambah_donatur.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="nama">Nama Donatur:</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="jumlah_donasi">Jumlah Donasi (Rp):</label>
                <input type="number" id="jumlah_donasi" name="jumlah_donasi" placeholder="Contoh: 500000" value="<?= htmlspecialchars($_POST['jumlah_donasi'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="pesan_singkat">Pesan Singkat (Opsional):</label>
                <textarea id="pesan_singkat" name="pesan_singkat"><?= htmlspecialchars($_POST['pesan_singkat'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto Donatur (Opsional):</label>
                <input type="file" id="foto" name="foto" accept="image/jpeg, image/png">
                <small>Format yang diizinkan: JPG, PNG. Ukuran maks: 2MB.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Simpan Donatur</button>
            <a href="dashboard.php" class="btn btn-secondary" style="width: 100%; padding: 12px; margin-top: 10px;">Kembali ke Dashboard</a>
        </form>
    </div>
    
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>