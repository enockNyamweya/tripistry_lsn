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
 ⚙
</button>
<!-- SETTINGS PANEL -->
<div id="settingsPanel" class="settings-panel">
<div class="settings-header">
<h3>Settings</h3>
</div>
<div class="settings-section">
<label class="settings-label">Theme</label>
<button class="theme-btn" data-theme="light">
 ☀ Light
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
