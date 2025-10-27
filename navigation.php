<?php
// Cek jika sesi belum dimulai, maka mulai sesi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar">
    <div class="nav-logo">GoDonate</div>
    
    <ul class="nav-menu">
        <li><a href="index.php#beranda">Beranda</a></li>
        <li><a href="index.php#tentang">Tentang Kami</a></li>
        <li><a href="index.php#donasi">Program Donasi</a></li>
        <li><a href="index.php#testimoni">Donatur</a></li>
        <li><a href="index.php#kontak">Kontak</a></li>
    </ul>
    
    <div class="nav-buttons">
        <button id="darkModeToggle" class="btn btn-secondary">🌙 Dark Mode</button>
        
        <?php // 1. JIKA SUDAH LOGIN (SESI ADA) ?>
        <?php if(isset($_SESSION['username'])): ?>
            
            <a href="dashboard.php" class="btn btn-primary">Kelola Program</a>
            
            <a href="kelola_donatur.php" class="btn btn-primary">Kelola Donatur</a>
            
            <a href="logout.php" class="btn btn-primary">Logout</a>

        <?php // 2. JIKA BELUM LOGIN (SESI TIDAK ADA) ?>
        <?php else: ?>
            
            <a href="login.php" class="btn btn-primary">Login</a>

        <?php endif; ?>
    </div>
</nav>