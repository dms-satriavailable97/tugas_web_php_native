<?php
session_start();
require 'config/database.php';

// 1. Cek Login Admin
if (!isset($_SESSION['username'])) {
    header("Location: login.php?message=Silakan+login");
    exit;
}

$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: kelola_donatur.php?message=ID+tidak+valid");
    exit;
}

// 2. PROSES FORM JIKA DI-SUBMIT (METHOD POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Ambil data form
    $nama = trim($_POST['nama']);
    $jumlah_donasi = trim($_POST['jumlah_donasi']);
    $pesan_singkat = trim($_POST['pesan_singkat']);
    $foto_lama = $_POST['foto_lama'] ?? null;
    $nama_foto = $foto_lama; // Default: pakai foto lama

    // Validasi data teks
    if (empty($nama) || empty($jumlah_donasi)) {
        $error = "Nama dan Jumlah Donasi wajib diisi!";
    } else if (!is_numeric($jumlah_donasi) || $jumlah_donasi < 0) {
        $error = "Jumlah donasi harus berupa angka (minimal 0)!";
    }
    
    // 3. PROSES JIKA ADA FOTO BARU DIUPLOAD
    else if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        
        $file = $_FILES['foto'];
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ekstensi_file, $ekstensi_diizinkan)) {
            $error = "Format foto tidak diizinkan! (Hanya JPG, JPEG, PNG)";
        } else if ($file['size'] > 2 * 1024 * 1024) { 
            $error = "Ukuran foto terlalu besar! (Maks 2MB)";
        } else {
            // 4. FILE BARU LOLOS VALIDASI
            $nama_foto_baru = time() . '-' . uniqid() . '.' . $ekstensi_file;
            $path_tujuan_baru = 'uploads/donatur/' . $nama_foto_baru;
            
            // Pindahkan file baru
            if (move_uploaded_file($file['tmp_name'], $path_tujuan_baru)) {
                // HAPUS FOTO LAMA (JIKA ADA)
                if (!empty($foto_lama) && file_exists('uploads/donatur/' . $foto_lama)) {
                    unlink('uploads/donatur/' . $foto_lama);
                }
                $nama_foto = $nama_foto_baru; // Set nama foto ke yang baru
            } else {
                $error = "Gagal memindahkan file foto baru.";
            }
        }
    }
    
    // 5. UPDATE DATABASE (JIKA TIDAK ADA ERROR)
    if (empty($error)) {
        try {
            $sql = "UPDATE donatur 
                    SET nama = ?, jumlah_donasi = ?, pesan_singkat = ?, foto = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $jumlah_donasi, $pesan_singkat, $nama_foto, $id]);
            
            header("Location: kelola_donatur.php?message=Data+donatur+berhasil+diperbarui!");
            exit;

        } catch (PDOException $e) {
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
    
    // Jika ada error, data $donatur akan diambil dari $_POST
    $donatur = [
        'id' => $id, 'nama' => $_POST['nama'], 'jumlah_donasi' => $_POST['jumlah_donasi'],
        'pesan_singkat' => $_POST['pesan_singkat'], 'foto' => $foto_lama
    ];

} else {
    // 6. JIKA BUKAN POST (METHOD GET), AMBIL DATA LAMA DARI DB
    try {
        $sql = "SELECT * FROM donatur WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $donatur = $stmt->fetch();

        if (!$donatur) {
            header("Location: kelola_donatur.php?message=Data+tidak+ditemukan");
            exit;
        }
    } catch (PDOException $e) {
        die("Gagal mengambil data donatur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Donatur - GoDonate</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .form-container { max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-container h1 { color: #006cc; text-align: center; margin-bottom: 30px; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; min-height: 100px; }
        .message { padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .error-message { background: #f8d7da; color: #dc3545; }
    </style>
</head>
<body>

    <?php include 'navigation.php'; ?>

    <div class="form-container">
        <h1>Edit Donatur: <?= htmlspecialchars($donatur['nama']); ?></h1>
        
        <?php if (!empty($error)): ?>
            <p class="message error-message"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="edit_donatur.php?id=<?= $donatur['id']; ?>" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="nama">Nama Donatur:</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($donatur['nama']); ?>" required>
            </div>

            <div class="form-group">
                <label for="jumlah_donasi">Jumlah Donasi (Rp):</label>
                <input type="number" id="jumlah_donasi" name="jumlah_donasi" value="<?= htmlspecialchars($donatur['jumlah_donasi']); ?>" required>
            </div>

            <div class="form-group">
                <label for="pesan_singkat">Pesan Singkat (Opsional):</label>
                <textarea id="pesan_singkat" name="pesan_singkat"><?= htmlspecialchars($donatur['pesan_singkat']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="foto">Ganti Foto Donatur:</label>
                <input type="file" id="foto" name="foto" accept="image/jpeg, image/png">
                <small>Kosongkan jika tidak ingin mengganti foto. Maks 2MB.</small>
            </div>

            <?php if (!empty($donatur['foto'])): ?>
                <div class="form-group">
                    <label>Foto Saat Ini:</label>
                    <img src="uploads/donatur/<?= htmlspecialchars($donatur['foto']); ?>" alt="Foto saat ini" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                    <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($donatur['foto']); ?>">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Update Donatur</button>
            <a href="kelola_donatur.php" class="btn btn-secondary" style="width: 100%; padding: 12px; margin-top: 10px;">Batal</a>
        </form>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>