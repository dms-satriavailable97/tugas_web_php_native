

// === Alert saat klik tombol donasi ===
const donateButtons = document.querySelectorAll('a.btn-primary[href*="wa.me"], a.btn-primary[href*="donasi"]');
if (donateButtons.length > 0) {
  donateButtons.forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.preventDefault(); 
      if (confirm("Terimakasih atas niat baik anda. Lanjutkan ke WhatsApp?")) {
        window.location.href = btn.href; 
      }
    });
  });
}

// === Alert untuk Logout ===
const logoutButtons = document.querySelectorAll('a[href="logout.php"]');
if (logoutButtons.length > 0) {
  logoutButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault(); 
      if (confirm('Anda yakin ingin logout?')) {
        window.location.href = this.href; 
      }
    });
  });
}


// === Fetch API (Quote Inspirasi) ===
const footerElement = document.querySelector("footer.footer");
if (footerElement) {
  fetch("https://api.adviceslip.com/advice")
    .then(res => res.json())
    .then(data => {
      const quoteBox = document.createElement("p");
      quoteBox.textContent = `"${data.slip.advice}"`;
      quoteBox.style.marginTop = "20px";
      quoteBox.style.fontStyle = "italic";
      footerElement.appendChild(quoteBox);
    })
    .catch(err => console.error("Gagal memuat quote:", err));
}


// === Smooth Scrolling untuk Navigasi ===
const navLinks = document.querySelectorAll('.nav-menu a');
if (navLinks.length > 0) {
  navLinks.forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      

      if (this.hash) {

        
        if (this.pathname === window.location.pathname) {
          
          // HANYA hentikan link jika kita ada di halaman yang SAMA
          e.preventDefault(); 
          
          const targetId = this.hash.substring(1); // Ambil 'beranda' dari '#beranda'
          const targetElement = document.getElementById(targetId);
          
          if (targetElement) {
            window.scrollTo({
              top: targetElement.offsetTop - 100, // Offset -100 untuk navbar
              behavior: 'smooth'
            });
          }
        }
      }
    });
  });
}


// === Load Dark Mode Preference ===
document.addEventListener('DOMContentLoaded', function() {
  const darkModePreference = localStorage.getItem('darkMode');
  const navDarkModeToggle = document.getElementById('darkModeToggle');
  
  if (darkModePreference === 'true') {
    document.body.classList.add('dark-mode');
    if(navDarkModeToggle) {
      navDarkModeToggle.textContent = '☀️ Light Mode';
    }
  } else {
    if(navDarkModeToggle) {
      navDarkModeToggle.textContent = '🌙 Dark Mode';
    }
  }
});

// === Dark Mode Toggle untuk Navigasi ===
const navDarkModeToggle = document.getElementById('darkModeToggle');
if(navDarkModeToggle) {
  navDarkModeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    
    const isDarkMode = document.body.classList.contains('dark-mode');
    navDarkModeToggle.textContent = isDarkMode ? '☀️ Light Mode' : '🌙 Dark Mode';
    
    localStorage.setItem('darkMode', isDarkMode);
  });
}