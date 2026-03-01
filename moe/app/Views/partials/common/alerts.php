<?php
/**
 * Centralized Flash Message Alerts
 * Automatically renders session flashdata for: success, error, warning, info
 * Included by layouts/base.php — no need to manually check in each page.
 */
?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="toast-alert toast-success"
        style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; padding:12px 24px; border-radius:8px; animation: slideDown 0.3s ease-out;">
        <i class="fa-solid fa-check-circle"></i>
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
    <script>setTimeout(() => document.querySelector('.toast-success')?.remove(), 4000);</script>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="toast-alert toast-error"
        style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; padding:12px 24px; border-radius:8px; animation: slideDown 0.3s ease-out;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
    <script>setTimeout(() => document.querySelector('.toast-error')?.remove(), 5000);</script>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="toast-alert toast-warning"
        style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; padding:12px 24px; border-radius:8px; animation: slideDown 0.3s ease-out;">
        <i class="fa-solid fa-exclamation-circle"></i>
        <?= esc(session()->getFlashdata('warning')) ?>
    </div>
    <script>setTimeout(() => document.querySelector('.toast-warning')?.remove(), 4000);</script>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
    <div class="toast-alert toast-warning"
        style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; padding:12px 24px; border-radius:8px; animation: slideDown 0.3s ease-out;">
        <i class="fa-solid fa-info-circle"></i>
        <?= esc(session()->getFlashdata('info')) ?>
    </div>
    <script>setTimeout(() => document.querySelector('.toast-warning')?.remove(), 4000);</script>
<?php endif; ?>