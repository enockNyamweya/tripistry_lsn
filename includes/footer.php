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
</body>
</html>
