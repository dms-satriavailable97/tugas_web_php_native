<?php
session_start();
require 'config/database.php';

// 1. Cek Login Admin
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

// 2. Ambil pesan dari URL (misal: "Data berhasil dihapus")
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// ==================================================
// == LOGIKA PAGINATION & PENCARIAN (untuk Donatur)
// ==================================================
$data_per_halaman = 5; // Tampilkan 5 donatur per halaman
$halaman_saat_ini = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_saat_ini < 1) $halaman_saat_ini = 1;
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : ''; // Cari berdasarkan nama

try {
    // --- Hitung TOTAL DATA Donatur ---
    $sql_count = "SELECT COUNT(*) FROM donatur";
    $params_count = [];
    if (!empty($keyword)) {
        $sql_count .= " WHERE nama LIKE ?"; // Cari berdasarkan nama
        $params_count[] = "%{$keyword}%";
    }
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params_count);
    $total_data = $stmt_count->fetchColumn();
    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($total_halaman < 1) $total_halaman = 1;

    // --- Ambil DATA SESUAI HALAMAN ---
    $sql_data = "SELECT * FROM donatur";
    $params_data = [];
    if (!empty($keyword)) {
        $sql_data .= " WHERE nama LIKE ?";
        $params_data[] = "%{$keyword}%";
    }
    $sql_data .= " ORDER BY created_at DESC LIMIT ? OFFSET ?"; 
    
    $stmt_data = $pdo->prepare($sql_data);
    
    if (!empty($keyword)) {
        $params_data[] = $data_per_halaman;
        $params_data[] = $offset;
        $stmt_data->execute($params_data);
    } else {
        $stmt_data->bindValue(1, $data_per_halaman, PDO::PARAM_INT);
        $stmt_data->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt_data->execute();
    }
    
    $donatur_list = $stmt_data->fetchAll();

} catch (\PDOException $e) {
    $db_error = "Gagal mengambil data donatur: " . $e->getMessage();
    $donatur_list = [];
    $total_halaman = 1;
    $total_data = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Donatur - GoDonate</title>
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
  <style>
      .search-form { margin: 20px 0; display: flex; gap: 10px; }
      .search-form input { flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; }
      .pagination { text-align: center; margin-top: 40px; }
      .dashboard-content-full { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 30px; }
      
      /* Style khusus untuk list donatur */
      .donatur-list { display: flex; flex-direction: column; gap: 15px; }
      .donatur-card {
          display: flex;
          gap: 20px;
          align-items: flex-start;
          background: #f8f9fa;
          padding: 20px;
          border-radius: 8px;
          border-left: 4px solid #0066cc;
      }
      .donatur-card img {
          width: 80px;
          height: 80px;
          border-radius: 50%; /* Foto bulat */
          object-fit: cover;
          border: 2px solid #ddd;
      }
      .donatur-info { flex: 1; }
      .donatur-info h4 { margin: 0 0 5px 0; color: #0066cc; }
      .donatur-info .jumlah { font-size: 1.1em; font-weight: bold; color: #333; }
      .donatur-info .pesan { font-style: italic; color: #555; margin-top: 10px; }
      .donatur-actions { margin-top: 15px; display: flex; gap: 10px; }

      /* Dark mode untuk donatur card */
      body.dark-mode .donatur-card {
          background: #374151;
          border-left-color: #3B82F6;
      }
      body.dark-mode .donatur-card img { border-color: #4B5563; }
      body.dark-mode .donatur-info .jumlah { color: #F9FAFB; }
      body.dark-mode .donatur-info .pesan { color: #D1D5DB; }

  </style>
</head>
<body>
  
  <?php include 'navigation.php'; ?>

  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>Kelola Donatur</h1>
      
      <?php if ($message): ?>
        <div class="success-message"><?= $message; ?></div>
      <?php endif; ?>
    </div>

    <div class="dashboard-content-full">
      
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0; color: #0066cc;">
                Semua Donatur (<?= $total_data ?>)
            </h2>
            <a href="tambah_donatur.php" class="btn btn-primary" style="background-color: #28a745;">+ Tambah Donatur</a>
        </div>
        
        <form method="GET" action="kelola_donatur.php" class="search-form">
            <input type="text" 
                   name="keyword" 
                   placeholder="Cari donatur berdasarkan nama..." 
                   value="<?= htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        
        <?php if (isset($db_error)): ?>
            <p class="error-message"><?= htmlspecialchars($db_error); ?></p>
        <?php endif; ?>

        <div class="donatur-list">
            <?php if (empty($donatur_list)): ?>
                <?php if (!empty($keyword)): ?>
                    <p>Donatur dengan nama "<strong><?= htmlspecialchars($keyword); ?></strong>" tidak ditemukan.</p>
                <?php else: ?>
                    <p>Belum ada data donatur yang ditambahkan.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($donatur_list as $donatur): ?>
                    <div class="donatur-card">
                        <?php if (!empty($donatur['foto'])): ?>
                            <img src="uploads/donatur/<?= htmlspecialchars($donatur['foto']); ?>" alt="<?= htmlspecialchars($donatur['nama']); ?>">
                        <?php else: ?>
                            <img src="assets/testi1.png" alt="Placeholder">
                        <?php endif; ?>

                        <div class="donatur-info">
                            <h4><?= htmlspecialchars($donatur['nama']); ?></h4>
                            <p class="jumlah">Donasi: Rp <?= number_format($donatur['jumlah_donasi'], 0, ',', '.'); ?></p>
                            
                            <?php if (!empty($donatur['pesan_singkat'])): ?>
                                <p class="pesan">"<?= htmlspecialchars($donatur['pesan_singkat']); ?>"</p>
                            <?php endif; ?>

                            <div class="donatur-actions">
                                <a href="edit_donatur.php?id=<?= $donatur['id']; ?>" class="btn btn-primary" style="background-color: #ffc107; color: #333; padding: 5px 10px; font-size: 14px;">Edit</a>
                                <a href="hapus_donatur.php?id=<?= $donatur['id']; ?>" 
                                   class="btn btn-secondary" 
                                   style="background-color: #dc3545; color: white; padding: 5px 10px; font-size: 14px;"
                                   onclick="return confirm('Anda yakin ingin menghapus donatur ini?');">
                                   Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php if ($halaman_saat_ini > 1): ?>
                <a href="?page=<?= $halaman_saat_ini - 1 ?>&keyword=<?= htmlspecialchars($keyword) ?>" class="btn btn-secondary">&laquo; Sebelumnya</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <a href="?page=<?= $i ?>&keyword=<?= htmlspecialchars($keyword) ?>"
                   class="btn <?= ($i == $halaman_saat_ini) ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($halaman_saat_ini < $total_halaman): ?>
                <a href="?page=<?= $halaman_saat_ini + 1 ?>&keyword=<?= htmlspecialchars($keyword) ?>" class="btn btn-secondary">Selanjutnya &raquo;</a>
            <?php endif; ?>
        </div>

    </div> </div> <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>