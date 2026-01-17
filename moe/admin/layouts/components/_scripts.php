<?php
/**
 * Admin Scripts Component
 * Common JavaScript includes for admin panel
 * 
 * Optional variable: $extraScripts (array) - additional JS files to include
 * Optional variable: $inlineScript (string) - inline JavaScript code
 */
require_once dirname(__DIR__, 2) . '/core/helpers.php';
$extraScripts = $extraScripts ?? [];
$inlineScript = $inlineScript ?? '';
?>
<!-- Common Scripts (with cache busting) -->
<script src="<?= asset('admin/js/sidebar-toggle.js', $jsPath ?? '') ?>"></script>

<!-- Extra Scripts -->
<?php foreach ($extraScripts as $script): ?>
    <script src="<?= asset('admin/' . $script, $jsPath ?? '') ?>"></script>
<?php endforeach; ?>

<!-- Inline Script -->
<?php if (!empty($inlineScript)): ?>
    <script>
        <?= $inlineScript ?>
    </script>
<?php endif; ?>