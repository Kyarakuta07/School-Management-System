<?php
/**
 * Admin Scripts Component
 * Common JavaScript includes for admin panel
 * 
 * Optional variable: $extraScripts (array) - additional JS files to include
 * Optional variable: $inlineScript (string) - inline JavaScript code
 */
$extraScripts = $extraScripts ?? [];
$inlineScript = $inlineScript ?? '';
?>
<!-- Common Scripts -->
<script src="<?= $jsPath ?? '' ?>js/sidebar-toggle.js"></script>

<!-- Extra Scripts -->
<?php foreach ($extraScripts as $script): ?>
    <script src="<?= $jsPath ?? '' ?><?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

<!-- Inline Script -->
<?php if (!empty($inlineScript)): ?>
    <script>
        <?= $inlineScript ?>
    </script>
<?php endif; ?>