// theme_switch.js
document.addEventListener('DOMContentLoaded', function() {
  // Get saved theme or default to dark
  const savedTheme = localStorage.getItem('theme') || 'dark';
  
  // Apply theme immediately
  applyTheme(savedTheme);
  
  // Setup theme switcher buttons
  setupThemeSwitcher();
  
  // Set navbar color based on theme
  updateNavbarTheme(savedTheme === 'dark' ? 'dark' : 'light');
});

function applyTheme(theme) {
  const root = document.documentElement;
  
  // If theme is 'auto', detect system preference
  if (theme === 'auto') {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
    localStorage.setItem('theme', 'auto');
    updateThemeIcon(prefersDark ? 'dark' : 'light');
  } else {
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);
    updateThemeIcon(theme);
  }
  
  // Update active state in dropdown
  document.querySelectorAll('.theme-option').forEach(btn => {
    btn.classList.remove('active');
    if (btn.getAttribute('data-theme') === theme) {
      btn.classList.add('active');
    }
  });
}

function setupThemeSwitcher() {
  document.querySelectorAll('.theme-option').forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const selectedTheme = this.getAttribute('data-theme');
      applyTheme(selectedTheme);
      updateNavbarTheme(selectedTheme === 'dark' ? 'dark' : 'light');
    });
  });
}

function updateThemeIcon(theme) {
  const themeIcon = document.getElementById('theme-icon');
  if (themeIcon) {
    themeIcon.textContent = theme === 'dark' ? '' : '';
  }
}

function updateNavbarTheme(theme) {
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    if (theme === 'dark') {
      navbar.classList.remove('navbar-light', 'bg-light');
      navbar.classList.add('navbar-dark', 'bg-dark');
    } else {
      navbar.classList.remove('navbar-dark', 'bg-dark');
      navbar.classList.add('navbar-light', 'bg-light');
      // For light theme, we're using custom color, so override
      navbar.style.backgroundColor = 'var(--bs-navbar-bg)';
    }
  }
}

// Listen for system theme changes if using auto
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
  if (localStorage.getItem('theme') === 'auto') {
    applyTheme('auto');
  }
});