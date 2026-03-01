<!-- Materials List -->
<?php if (!empty($materials)): ?>
    <div class="materials-grid">
        <?php foreach ($materials as $material): ?>
            <div class="material-card">
                <div class="material-header">
                    <div class="material-type-icon <?= esc($material['material_type']) ?>">
                        <?php if ($material['material_type'] === 'text'): ?>
                            <i class="fas fa-file-alt"></i>
                        <?php elseif ($material['material_type'] === 'youtube'): ?>
                            <i class="fab fa-youtube"></i>
                        <?php else: ?>
                            <i class="fas fa-file-pdf"></i>
                        <?php endif; ?>
                    </div>
                    <span class="material-title">
                        <?= esc($material['title']) ?>
                    </span>
                    <?php if ($canManage): ?>
                        <button type="button" class="material-delete-btn"
                            onclick="confirmDelete(this, <?= $material['id_material'] ?>)" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="material-meta">
                    <i class="fas fa-user"></i>
                    <?= esc($material['creator_name']) ?> ·
                    <i class="fas fa-clock"></i>
                    <?= date('d M Y', strtotime($material['created_at'])) ?>
                </div>
                <div class="material-content">
                    <?php if ($material['material_type'] === 'text'): ?>
                        <div class="text-content-wrapper" data-title="<?= esc($material['title']) ?>">
                            <div class="text-content" id="content-<?= $material['id_material'] ?>">
                                <?= $material['content'] ?>
                            </div>
                            <button class="read-more-btn" onclick="openContentModal(this)">
                                <i class="fas fa-expand"></i> Read More
                            </button>
                        </div>
                    <?php elseif ($material['material_type'] === 'youtube'): ?>
                        <?php
                        $videoId = $material['content'];
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $material['content'], $matches)) {
                            $videoId = $matches[1];
                        }
                        ?>
                        <div class="youtube-container">
                            <iframe
                                src="https://www.youtube-nocookie.com/embed/<?= esc($videoId) ?>?rel=0&origin=<?= urlencode(base_url()) ?>"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    <?php elseif ($material['material_type'] === 'pdf'): ?>
                        <div class="pdf-actions">
                            <button type="button" class="pdf-btn view"
                                onclick="openPdfModal('<?= base_url('api/materials/download?id=' . $material['id_material']) ?>', '<?= esc($material['title']) ?>', '<?= base_url('api/materials/download?id=' . $material['id_material'] . '&dl=1') ?>')">
                                <i class="fas fa-eye"></i> View PDF
                            </button>
                            <a href="<?= base_url('api/materials/download?id=' . $material['id_material'] . '&dl=1') ?>"
                                class="pdf-btn download">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-materials">
        <i class="fas fa-book-open"></i>
        <h3>No Materials Yet</h3>
        <p>Materials will appear here once the teacher adds them.</p>
    </div>
<?php endif; ?>