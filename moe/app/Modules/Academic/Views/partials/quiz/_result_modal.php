<?php
/**
 * quiz/_result_modal.php
 * Displays the quiz result (score, message) in a modal.
 */
?>
<div class="result-modal" id="resultModal">
    <div class="result-content">
        <div class="result-icon" id="resultIcon"><i class="fas fa-check"></i></div>
        <div class="result-score" id="resultScore">0%</div>
        <div class="result-message" id="resultMessage">Loading...</div>
        <div class="result-details" id="resultDetails"></div>
        <a href="<?= base_url('subject?subject=' . esc($quiz['subject'])) ?>" class="result-btn">Back to Subject</a>
    </div>
</div>