const settingsToggle = document.getElementById('settingsToggle');
const settingsPanel = document.getElementById('settingsPanel');
const fontSizeSelect = document.getElementById('fontSizeSelect');
const themeButtons = document.querySelectorAll('.theme-btn');
// OPEN/CLOSE PANEL
settingsToggle?.addEventListener('click', () => {
settingsPanel.classList.toggle('open');
});
// APPLY THEME
function applyTheme(theme) {
document.body.classList.remove('theme-light', 'theme-dark');
document.body.classList.add(`theme-${theme}`);
localStorage.setItem('theme', theme);
}
// APPLY FONT SIZE
function applyFontSize(size) {
document.body.classList.remove('font-small', 'font-medium', 'font-large');
document.body.classList.add(`font-${size}`);
localStorage.setItem('fontSize', size);
}
// THEME BUTTONS
themeButtons.forEach(btn => {
btn.addEventListener('click', () => {
applyTheme(btn.dataset.theme);
});
});

// FONT SIZE SELECT
fontSizeSelect?.addEventListener('change', (e) => {
applyFontSize(e.target.value);
});
// LOAD SAVED SETTINGS
window.addEventListener('DOMContentLoaded', () => {
const savedTheme = localStorage.getItem('theme') || 'light';
const savedFont = localStorage.getItem('fontSize') || 'medium';
applyTheme(savedTheme);
applyFontSize(savedFont);
if (fontSizeSelect) {
fontSizeSelect.value = savedFont;
}
});