<?php
session_start();

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['username'])) {
  header("Location: index.php");
  exit;
}

// ✅ TAMBAHKAN: Tangkap pesan dari URL (contoh: dari logout)
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// Cek jika form dikirim
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Autentikasi sederhana
  if ($username === "admin" && $password === "12345") {
    $_SESSION['username'] = $username;
    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - GoDonate</title>
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
  <div class="login-container">
    <div class="login-form">
      <h1>Login ke GoDonate</h1>

      <!-- Tampilkan pesan dari URL -->
      <?php if (!empty($message)): ?>
        <p class="success-message"><?= $message; ?></p>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <p class="error-message"><?= $error; ?></p>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label>Username:</label>
          <input type="text" name="username" required>
        </div>

        <div class="form-group">
          <label>Password:</label>
          <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-login">Login</button>
        <a href="index.php" class="btn btn-secondary btn-login">Kembali</a>
      </form>
    
</body>
</html>