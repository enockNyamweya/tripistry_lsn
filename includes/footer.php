</main>
<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Tripistry. All rights reserved.</p>
</footer>

<?php
$uiJs = __DIR__ . '/../assets/js/ui.js';
$mainJs = __DIR__ . '/../assets/js/main.js';
?>
<script src="<?php echo BASE_URL; ?>/assets/js/ui.js?v=<?php echo file_exists($uiJs) ? filemtime($uiJs) : '1'; ?>"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo file_exists($mainJs) ? filemtime($mainJs) : '1'; ?>"></script>

<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
<!-- SETTINGS BUTTON -->
<button id="settingsToggle" class="settings-fab">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings" style="display:block;margin:auto;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
</button>
<!-- SETTINGS PANEL -->
<div id="settingsPanel" class="settings-panel">
<div class="settings-header">
<h3>Settings</h3>
</div>
<div class="settings-section">
<label class="settings-label">Theme</label>
<button class="theme-btn" data-theme="light">
 Light
</button>
<button class="theme-btn" data-theme="dark">
 Dark
</button>
</div>
<div class="settings-section">
<label class="settings-label">Font Size</label>
<select id="fontSizeSelect">
<option value="small">Small</option>
<option value="medium" selected>Medium</option>
<option value="large">Large</option>
</select>
</div>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/settings.js"></script>

</body>
</html>
