<?php if ($canManage): ?>
    <!-- Add Material Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add Material</h2>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addMaterialForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="subject" value="<?= esc($subject) ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-input" required placeholder="Material title...">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="material_type" class="form-select" id="typeSelect" onchange="toggleContentField()">
                            <option value="text">Text / Article</option>
                            <option value="youtube">YouTube Video</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                    </div>
                    <div class="form-group" id="textContentGroup">
                        <label>Content</label>
                        <textarea name="text_content" class="form-textarea"
                            placeholder="Write your material content here..."></textarea>
                    </div>
                    <div class="form-group" id="youtubeContentGroup" style="display: none;">
                        <label>YouTube URL or Video ID</label>
                        <input type="text" name="youtube_content" class="form-input"
                            placeholder="https://youtube.com/watch?v=... or video ID">
                    </div>
                    <div class="form-group" id="pdfContentGroup" style="display: none;">
                        <label>Upload PDF (Max 5MB)</label>
                        <input type="file" name="pdf_file" id="pdfFileInput" class="form-input" accept=".pdf">
                        <small style="color: rgba(255,255,255,0.5);">Only PDF files are allowed</small>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Material</button>
                </form>
                <div id="formResult" style="margin-top: 16px; text-align: center;"></div>
            </div>
        </div>
    </div>

    <!-- Quiz Create Modal -->
    <div class="modal-overlay" id="quizModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-clipboard-list"></i> Create Quiz</h2>
                <button class="modal-close" onclick="closeQuizModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createQuizForm">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Quiz Title</label>
                        <input type="text" name="quiz_title" class="form-input" required placeholder="e.g., Chapter 1 Quiz">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="quiz_description" class="form-textarea" rows="3"
                            placeholder="Brief description of the quiz..."></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Time Limit (minutes)</label>
                            <input type="number" name="time_limit" class="form-input" value="30" min="5" max="120">
                        </div>
                        <div class="form-group">
                            <label>Passing Score (%)</label>
                            <input type="number" name="passing_score" class="form-input" value="70" min="0" max="100">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="fas fa-plus"></i> Create & Add Questions
                    </button>
                </form>
                <div id="quizFormResult" style="margin-top: 16px; text-align: center;"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Content Read More Modal -->
<div class="content-modal" id="contentModal">
    <div class="content-modal-inner">
        <div class="content-modal-header">
            <h3 id="contentModalTitle">Material</h3>
            <button class="content-modal-close" onclick="closeContentModal()">&times;</button>
        </div>
        <div class="content-modal-body" id="contentModalBody"></div>
        <div class="content-modal-footer">
            <button onclick="closeContentModal()">Close</button>
        </div>
    </div>
</div>

<!-- PDF Viewer Modal -->
<div class="content-modal" id="pdfViewerModal">
    <div class="content-modal-inner" style="max-width: 900px; height: 90vh;">
        <div class="content-modal-header">
            <h3 id="pdfViewerTitle">📄 PDF Viewer</h3>
            <button class="content-modal-close" onclick="closePdfModal()">&times;</button>
        </div>
        <div style="flex: 1; padding: 0; overflow: hidden; height: calc(100% - 120px);">
            <iframe id="pdfViewerFrame" src=""
                style="width: 100%; height: 100%; border: none; border-radius: 0 0 16px 16px;"></iframe>
        </div>
        <div class="content-modal-footer">
            <a id="pdfDownloadLink" href="#" class="pdf-btn download" style="text-decoration: none;">
                <i class="fas fa-download"></i> Download
            </a>
            <button onclick="closePdfModal()">Close</button>
        </div>
    </div>
</div>