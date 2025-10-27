<?php
// 1. Sertakan file koneksi database
require 'config/database.php';

// Ambil program donasi yang "Utama" (is_featured = 1)
try {
    $stmt_featured = $pdo->query("SELECT * FROM program_donasi WHERE is_featured = 1 LIMIT 1");
    $featured_program = $stmt_featured->fetch(); // Ambil 1 data saja
} catch (PDOException $e) {
    $featured_program = false; // Set false jika error
}

// AMBIL DATA TESTIMONI (dari tabel donatur)
try {
    // Kita ambil 3 donatur terbaru yang punya FOTO dan PESAN
    // agar testimoninya tidak kosong
    $sql_donatur = "SELECT nama, foto, pesan_singkat 
                    FROM donatur 
                    WHERE foto IS NOT NULL 
                      AND pesan_singkat IS NOT NULL 
                      AND pesan_singkat != '' 
                    ORDER BY created_at DESC 
                    LIMIT 3";
                    
    $stmt_donatur = $pdo->query($sql_donatur);
    $donatur_list = $stmt_donatur->fetchAll();
    
} catch (PDOException $e) {
    $donatur_list = []; // Set array kosong jika error
}

// ==================================================
// == LOGIKA PAGINATION & PENCARIAN ==

// Tentukan data per halaman
$data_per_halaman = 2; // Wajib: Minimal 5 data per halaman

// Ambil halaman saat ini dari URL, default-nya halaman 1
$halaman_saat_ini = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_saat_ini < 1) {
    $halaman_saat_ini = 1;
}

// Hitung OFFSET untuk query SQL
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;

// Ambil keyword pencarian
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

try {
    // --- Langkah 1: Hitung TOTAL DATA (untuk pagination) ---
    // Query ini harus sesuai dengan filter pencarian
    $sql_count = "SELECT COUNT(*) FROM program_donasi WHERE is_featured = 0";
    $params_count = [];

    if (!empty($keyword)) {
        $sql_data .= " AND judul LIKE ?";
        $params_data[] = "%{$keyword}%";
    }

    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params_count);
    $total_data = $stmt_count->fetchColumn();

    // Hitung total halaman
    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($total_halaman < 1) {
        $total_halaman = 1; // Pastikan minimal ada 1 halaman
    }

    // --- Langkah 2: Ambil DATA SESUAI HALAMAN (LIMIT/OFFSET) ---
    // Query ini MENGGABUNGKAN pencarian DAN pagination
    $sql_data = "SELECT * FROM program_donasi WHERE is_featured = 0";
    $params_data = [];

    if (!empty($keyword)) {
        $sql_count .= " AND judul LIKE ?"; 
        $params_count[] = "%{$keyword}%";
    }

    // Tambahkan ORDER BY dan LIMIT/OFFSET
    $sql_data .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params_data[] = $data_per_halaman;
    $params_data[] = $offset;

    /* Catatan: Karena LIMIT/OFFSET butuh integer, kita harus binding
     secara eksplisit menggunakan bindValue() dengan tipe PDO::PARAM_INT
    */
    $stmt_data = $pdo->prepare($sql_data);
    
    // Binding parameter LIKE (jika ada)
    $param_index = 1;
    if (!empty($keyword)) {
        $stmt_data->bindValue($param_index, $params_data[0], PDO::PARAM_STR);
        $param_index++;
    }
    
    // Binding LIMIT dan OFFSET
    $stmt_data->bindValue($param_index, $data_per_halaman, PDO::PARAM_INT);
    $param_index++;
    $stmt_data->bindValue($param_index, $offset, PDO::PARAM_INT);

    $stmt_data->execute();
    $program_donasi = $stmt_data->fetchAll();

} catch (\PDOException $e) {
    echo "Gagal mengambil data program donasi: " . $e->getMessage();
    $program_donasi = [];
    $total_halaman = 1; // Set default jika error
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GoDonate - Donasi Online</title>
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

</head>
<body>
  <?php include 'navigation.php'; ?>
  <main>
    <!-- Video Intro -->
    <section id="beranda" class="intro-section">
      <video controls>
        <source src="assets/video.mp4" type="video/mp4">
        Browser Anda tidak mendukung video.
      </video>
      <p class="intro">
        Setiap donasi Anda membawa perubahan besar bagi masa depan mereka.
      </p>
      <br>
    </section>

    <!-- Mengapa Donasi -->
    <section class="section" id="tentang">
      <div class="section-header">
        <h2>Mengapa Donasi Ini Penting?</h2>
        <p>Bersama kita wujudkan kepedulian sosial yang nyata</p>
      </div>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🤝</div>
          <h3>Kemudahan Berdonasi</h3>
          <p>GoDonate menawarkan kemudahan bagi Anda untuk berdonasi secara online, tanpa perlu turun ke lokasi langsung.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🎯</div>
          <h3>Tepat Sasaran</h3>
          <p>Bantuan langsung disalurkan kepada mereka yang benar-benar membutuhkan dengan proses yang transparan.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">❤️</div>
          <h3>Dampak Nyata</h3>
          <p>Setiap kontribusi Anda memberikan dampak langsung untuk pendidikan, kesehatan, dan bantuan bencana.</p>
        </div>
      </div>
    </section>

    <!-- Donasi Utama -->
    <section id="donasi-utama" style="text-align: center; margin-bottom: -10px;">
        <h2 style="color: #0066cc; font-size: 1.8em;">Donasi Dibutuhkan Segera:</h2>
    </section>

    <?php if ($featured_program): ?>
    
    <section class="card card-featured">
    
        <?php if (!empty($featured_program['gambar'])): ?>
            <img src="uploads/<?= htmlspecialchars($featured_program['gambar']); ?>" 
                 alt="<?= htmlspecialchars($featured_program['judul']); ?>" 
                 class="featured-image"> <?php else: ?>
            <img src="assets/donasi.jpg" 
                 alt="Placeholder Donasi" 
                 class="featured-image">
        <?php endif; ?>
        
        <div class="featured-content" style="padding-top: 20px;">
            <h2><?= htmlspecialchars(strtoupper($featured_program['judul'])); ?></h2>
          
            <h3>Target Donasi</h3>
            <?php
              // Hitung persentase
              $persentase = 0;
              if ($featured_program['target_donasi'] > 0) {
                  $persentase = ($featured_program['terkumpul'] / $featured_program['target_donasi']) * 100;
                  $persentase = round(min($persentase, 100));
              }
            ?>
            <p>Target: Rp <?= number_format($featured_program['target_donasi'], 0, ',', '.'); ?></p>
            <p>Terkumpul: Rp <?= number_format($featured_program['terkumpul'], 0, ',', '.'); ?> (<?= $persentase; ?>%)</p>
    
            <h3>Cara Berdonasi</h3>
            <p>Anda dapat berdonasi dengan cara klik tombol di bawah ini:</p>
            
            <a href="https://wa.me/+628997800507" class="btn btn-primary">Donasi Sekarang via WhatsApp</a>
            <a href="detail.php?id=<?= $featured_program['id']; ?>" class="btn btn-secondary" style="margin-left: 10px;">Lihat Detail</a>
            
            <p style="margin-top: 15px;">
              Atau transfer langsung ke rekening berikut:  
              <br>Bank ABC - 1234567890 a.n GoDonate  
              <br>OVO / GoPay / Dana: 0899-7800-507
            </p>
        </div>
    </section>
    
    <?php endif; ?>


    <!-- Donasi Lain -->
    <section id="donasi">
      <h2>Donasi Lain Yang Bisa Anda Lakukan :</h2>

      <form method="GET" action="index.php#donasi" style="margin: 20px auto; max-width: 700px; display: flex; gap: 10px;">
          <input type="text" 
                name="keyword" 
                placeholder="Cari program donasi berdasarkan judul..." 
                style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;"
                value="<?= htmlspecialchars($keyword); ?>">
          <button type="submit" class="btn btn-primary" style="padding: 12px 20px;">Cari</button>
      </form>
      <div class="donasi-list">
        <?php if (empty($program_donasi)): ?>
    
            <?php if (!empty($keyword)): ?>
                <p style="text-align: center; font-size: 1.1em;">
                    Program donasi dengan kata kunci "<strong><?= htmlspecialchars($keyword); ?></strong>" tidak ditemukan.
                </p>
            <?php else: ?>
                <p style="text-align: center;">Belum ada program donasi yang tersedia saat ini.</p>
            <?php endif; ?>

        <?php else: ?>

            <?php foreach ($program_donasi as $program): ?>
                <article class="card">
                    
                    <h3><?= htmlspecialchars($program['judul']); ?></h3>
                    
                    <?php if (!empty($program['gambar'])): ?>
                        <img src="uploads/<?= htmlspecialchars($program['gambar']); ?>" alt="<?= htmlspecialchars($program['judul']); ?>">
                    <?php else: ?>
                        <img src="assets/donasi.jpg" alt="Placeholder Donasi">
                    <?php endif; ?>
                    
                    <p><?= htmlspecialchars($program['deskripsi']); ?></p>
                    
                    <p style="text-align: left; margin-top: 10px;">
                        <strong>Terkumpul:</strong> Rp <?= number_format($program['terkumpul'], 0, ',', '.'); ?>
                        <br>
                        <strong>Target:</strong> Rp <?= number_format($program['target_donasi'], 0, ',', '.'); ?>
                    </p>

                    <a href="detail.php?id=<?= $program['id']; ?>" class="btn btn-primary">Lihat Detail</a>
                    
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
      </div>

      </div> <div class="pagination" style="text-align: center; margin-top: 40px;">
        
        <?php // Tombol "Sebelumnya" ?>
        <?php if ($halaman_saat_ini > 1): ?>
            <a href="?page=<?= $halaman_saat_ini - 1 ?>&keyword=<?= htmlspecialchars($keyword) ?>" class="btn btn-secondary">
                &laquo; Sebelumnya
            </a>
        <?php endif; ?>

        <?php // Tampilkan link halaman ?>
        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
            <a href="?page=<?= $i ?>&keyword=<?= htmlspecialchars($keyword) ?>"
              class="btn <?= ($i == $halaman_saat_ini) ? 'btn-primary' : 'btn-secondary' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php // Tombol "Selanjutnya" ?>
        <?php if ($halaman_saat_ini < $total_halaman): ?>
            <a href="?page=<?= $halaman_saat_ini + 1 ?>&keyword=<?= htmlspecialchars($keyword) ?>" class="btn btn-secondary">
                Selanjutnya &raquo;
            </a>
        <?php endif; ?>
    </div>
    </section>

  
  <!-- Testimonni -->
  <section id="testimoni">
    <h2>Testimoni</h2>
    <div class="testimoni">
      
      <?php if (empty($donatur_list)): ?>
          
          <p style="text-align: center; width: 100%;">Belum ada testimoni untuk ditampilkan.</p>
      
      <?php else: ?>
          
          <?php foreach ($donatur_list as $donatur): ?>
          <article class="testi-card">
              <img src="uploads/donatur/<?= htmlspecialchars($donatur['foto']); ?>" 
                   alt="Foto <?= htmlspecialchars($donatur['nama']); ?>" 
                   class="profile">
                   
              <h3><?= htmlspecialchars($donatur['nama']); ?></h3>
              
              <p>"<?= htmlspecialchars($donatur['pesan_singkat']); ?>"</p>
          </article>
          <?php endforeach; ?>
          
      <?php endif; ?>
      </div>
  </section>

  <br>


    <!-- Ajakan Donasi -->
    <section class="cta-section">
      <div class="cta-content">
        <h2>Ayo Jadi Bagian Dari Perubahan</h2>
        <p>Setiap Rp10.000 yang Anda donasikan berarti satu langkah lebih dekat menuju masa depan yang lebih cerah bagi mereka yang membutuhkan.</p>
        <p class="cta-highlight">Jangan tunda kebaikan, karena bantuan Anda sangat berarti.</p>
        <a href="https://wa.me/+628997800507" class="btn btn-primary btn-large">
          ✨ Mulai Berdonasi Sekarang
        </a>
      </div>
    </section>
  </main>

    <!-- Footer -->
    <footer class="footer" id="kontak">
      <div class="footer-content">
        <div class="footer-section">
          <h3>GoDonate</h3>
          <p>Platform donasi online terpercaya yang menghubungkan para donatur dengan mereka yang membutuhkan.</p>
        </div>
        <div class="footer-section">
          <h3>Kontak Kami</h3>
          <p>📞 Telepon/WA: 0899-7800-507</p>
          <p>✉️ Email: info@godonate.id</p>
        </div>
        <div class="footer-section">
          <h3>Referensi</h3>
          <p><a href="https://wecare.id/" target="_blank">WeCare.id</a></p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2025 GoDonate. All rights reserved.</p>
      </div>
    </footer>
  
    <script src="script.js?v=<?php echo time(); ?>"></script>
  </body>
</html>
