<?php
/**
 * quiz/_form_question.php
 * Form for adding new questions to a quiz.
 */
?>
<div class="add-question-card">
    <h2><i class="fas fa-plus-circle"></i> Add New Question</h2>
    <form id="addQuestionForm">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Question</label>
                <textarea name="question" class="form-textarea" required
                    placeholder="Enter your question..."></textarea>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group"><label>Option A</label><input type="text" name="option_a" class="form-input"
                    required placeholder="Option A"></div>
            <div class="form-group"><label>Option B</label><input type="text" name="option_b" class="form-input"
                    required placeholder="Option B"></div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group"><label>Option C</label><input type="text" name="option_c" class="form-input"
                    required placeholder="Option C"></div>
            <div class="form-group"><label>Option D</label><input type="text" name="option_d" class="form-input"
                    required placeholder="Option D"></div>
        </div>
        <div class="form-row cols-4">
            <label class="correct-label"><input type="radio" name="correct_answer" value="a" required> A is
                correct</label>
            <label class="correct-label"><input type="radio" name="correct_answer" value="b"> B is
                correct</label>
            <label class="correct-label"><input type="radio" name="correct_answer" value="c"> C is
                correct</label>
            <label class="correct-label"><input type="radio" name="correct_answer" value="d"> D is
                correct</label>
        </div>
        <div class="form-row" style="margin-top: 16px;">
            <div class="form-group" style="max-width: 150px;">
                <label>Points</label>
                <input type="number" name="points" class="form-input" value="10" min="1" max="100">
            </div>
        </div>
        <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add Question</button>
        <div id="formResult"></div>
    </form>
</div>