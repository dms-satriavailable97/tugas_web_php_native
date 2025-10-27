<?php
session_start();
require 'config/database.php';

// 1. Cek Login
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

// 2. Ambil pesan dari URL (misal: "Data berhasil dihapus")
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// ==================================================
// == LOGIKA PAGINATION & PENCARIAN (PINDAHAN DARI MANAGE.PHP)
// ==================================================
$data_per_halaman = 5; // Tampilkan 5 data per halaman
$halaman_saat_ini = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_saat_ini < 1) $halaman_saat_ini = 1;
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

try {
    // --- Hitung TOTAL DATA ---
    $sql_count = "SELECT COUNT(*) FROM program_donasi";
    $params_count = [];
    if (!empty($keyword)) {
        $sql_count .= " WHERE judul LIKE ?";
        $params_count[] = "%{$keyword}%";
    }
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params_count);
    $total_data = $stmt_count->fetchColumn();
    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($total_halaman < 1) $total_halaman = 1;

    // --- Ambil DATA SESUAI HALAMAN ---
    $sql_data = "SELECT * FROM program_donasi";
    $params_data = [];
    if (!empty($keyword)) {
        $sql_data .= " WHERE judul LIKE ?";
        $params_data[] = "%{$keyword}%";
    }
    // HAPUS "LIMIT 4". Kita pakai LIMIT dari pagination
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
    
    $program_donasi = $stmt_data->fetchAll();

} catch (\PDOException $e) {
    $db_error = "Gagal mengambil data: " . $e->getMessage();
    $program_donasi = [];
    $total_halaman = 1;
    $total_data = 0; // set total data 0 jika error
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>dashboard Donasi - GoDonate</title>
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
  <style>
      /* Style untuk form pencarian */
      .search-form {
          margin: 20px 0;
          display: flex;
          gap: 10px;
      }
      .search-form input {
          flex: 1;
          padding: 12px;
          border: 2px solid #e0e0e0;
          border-radius: 8px;
          font-size: 16px;
      }
      /* Style untuk pagination */
      .pagination { text-align: center; margin-top: 40px; }

      /* Konten full-width baru */
      .dashboard-content-full {
          background: #fff; 
          padding: 30px; 
          border-radius: 12px; 
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          margin-top: 30px; /* Jarak dari header */
      }
  </style>
</head>
<body>
  
  <?php include 'navigation.php'; ?>

  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>dashboard Program Donasi</h1>
      
      <?php if ($message): ?>
        <div class="success-message"><?= $message; ?></div>
      <?php endif; ?>
    </div>

    <div class="dashboard-content-full">
      
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0; color: #0066cc;">
                Semua Program (<?= $total_data ?>)
            </h2>
            <a href="tambah.php" class="btn btn-primary" style="background-color: #28a745;">+ Tambah Program Baru</a>
        </div>
        
        <form method="GET" action="dashboard.php" class="search-form">
            <input type="text" 
                   name="keyword" 
                   placeholder="Cari program donasi..." 
                   value="<?= htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        
        <?php if (isset($db_error)): ?>
            <p class="error-message"><?= htmlspecialchars($db_error); ?></p>
        <?php endif; ?>

        <div class="program-list">
            <?php if (empty($program_donasi)): ?>
                <?php if (!empty($keyword)): ?>
                    <p>Program dengan kata kunci "<strong><?= htmlspecialchars($keyword); ?></strong>" tidak ditemukan.</p>
                <?php else: ?>
                    <p>Belum ada program donasi yang dibuat.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($program_donasi as $program): ?>
                    <?php
                        $persentase = 0;
                        if ($program['target_donasi'] > 0) {
                            $persentase = ($program['terkumpul'] / $program['target_donasi']) * 100;
                            $persentase = round(min($persentase, 100));
                        }
                    ?>
                    <div class="program-card">
                        <h4><?= htmlspecialchars($program['judul']); ?></h4>
                        <p>Target: Rp <?= number_format($program['target_donasi'], 0, ',', '.'); ?></p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $persentase; ?>%"></div>
                        </div>
                        <p>Terkumpul: Rp <?= number_format($program['terkumpul'], 0, ',', '.'); ?> (<?= $persentase; ?>%)</p>
                        <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                            <a href="edit.php?id=<?= $program['id']; ?>" class="btn btn-primary" style="background-color: #ffc107; color: #333; padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="hapus.php?id=<?= $program['id']; ?>" 
                            class="btn btn-secondary" 
                            style="background-color: #dc3545; color: white; padding: 5px 10px; font-size: 14px;"
                            onclick="return confirm('Anda yakin ingin menghapus program ini?');">
                            Hapus
                            </a>

                            <?php if ($program['is_featured'] == 0): ?>
                                <a href="feature.php?id=<?= $program['id']; ?>" 
                                class="btn btn-secondary" 
                                style="background-color: #17a2b8; color: white; padding: 5px 10px; font-size: 14px;"
                                onclick="return confirm('Jadikan program ini donasi utama di halaman depan?');">
                                Jadikan Utama
                                </a>
                            <?php else: ?>
                                <span style="background: #e0f7fa; color: #007bff; padding: 5px 10px; font-size: 14px; border-radius: 8px;">✨ Utama</span>
                            <?php endif; ?>
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

    <script src="script.js?v=<?php echo time(); ?>"></script>

</body>
</html>