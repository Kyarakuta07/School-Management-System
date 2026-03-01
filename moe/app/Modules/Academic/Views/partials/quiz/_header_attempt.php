<?php
/**
 * quiz/_header_attempt.php
 * Displays quiz info and countdown timer for attempts.
 */
?>
<div class="quiz-header">
    <div class="quiz-icon"><i class="fas <?= esc($currentSubject['icon']) ?>"></i></div>
    <div class="quiz-info">
        <h1>
            <?= esc($quiz['title']) ?>
        </h1>
        <p>
            <?= esc($quiz['description']) ?>
        </p>
    </div>
    <div class="timer-box" id="timerBox">
        <div class="label">Time Remaining</div>
        <div class="time" id="timer">
            <?= esc($quiz['time_limit']) ?>:00
        </div>
    </div>
</div>