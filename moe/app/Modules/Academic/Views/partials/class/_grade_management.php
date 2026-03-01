<?php if ($canManageGrades): ?>
    <!-- HAKAES: Grade Management Panel -->
    <div class="class-card hakaes-panel">
        <h3 class="card-title"><i class="fa-solid fa-chalkboard-teacher"></i> GRADE MANAGEMENT</h3>

        <div class="hakaes-form">
            <div class="form-group">
                <label for="student-select">Select Student</label>
                <select id="student-select" class="form-control">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($allStudents as $student): ?>
                        <option value="<?= $student['id_nethera'] ?>" data-name="<?= esc($student['nama_lengkap']) ?>"
                            data-sanctuary="<?= esc($student['nama_sanctuary'] ?? '-') ?>">
                            <?= esc($student['nama_lengkap']) ?> (@
                            <?= esc($student['username']) ?>) -
                            <?= $student['total_pp'] ?> PP
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="grade-form" class="grade-form" style="display:none;">
                <div class="student-info-display">
                    <span id="selected-student-name">-</span>
                    <span id="selected-student-sanctuary" class="sanctuary-tag">-</span>
                </div>

                <div class="grade-inputs">
                    <?php foreach ($subjects as $key => $subject): ?>
                        <?php
                        if (!$isVasiki && $hakaesSub !== null && $key !== $hakaesSub)
                            continue;
                        ?>
                        <div class="grade-input-item" style="--subject-color: <?= $subject['color'] ?>">
                            <label><i class="fa-solid <?= $subject['icon'] ?>"></i>
                                <?= $subject['name'] ?>
                            </label>
                            <input type="number" id="grade-<?= $key ?>" name="<?= $key ?>" min="0" max="100" placeholder="PP"
                                class="grade-input">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-preview">
                    <span>Total PP:</span>
                    <span id="total-pp-preview" class="pp-value">0</span>
                </div>

                <button type="button" id="save-grades-btn" class="btn-hakaes">
                    <i class="fa-solid fa-save"></i> Save Grades
                </button>
            </div>

            <div id="grade-result" class="grade-result" style="display:none;"></div>
        </div>
    </div>

    <?php if (!empty($allGrades)): ?>
        <!-- ALL CLASS GRADES TABLE -->
        <div class="class-card grades-table-card">
            <div class="table-header-actions">
                <i class="fa-solid fa-table"></i>
                <?= $hakaesSubName ? strtoupper($hakaesSubName) . ' GRADES' : 'ALL CLASS GRADES' ?>

                <div class="table-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="grade-search" placeholder="Cari nama siswa...">
                </div>
            </div>

            <div class="grades-table-wrapper">
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Sanctuary</th>
                            <?php if ($isVasiki || $hakaesSub === 'pop_culture'): ?>
                                <th>Pop Culture</th>
                            <?php endif; ?>
                            <?php if ($isVasiki || $hakaesSub === 'mythology'): ?>
                                <th>Mythology</th>
                            <?php endif; ?>
                            <?php if ($isVasiki || $hakaesSub === 'history_of_egypt'): ?>
                                <th>History of Egypt</th>
                            <?php endif; ?>
                            <?php if ($isVasiki || $hakaesSub === 'oceanology'): ?>
                                <th>Oceanology</th>
                            <?php endif; ?>
                            <?php if ($isVasiki || $hakaesSub === 'astronomy'): ?>
                                <th>Astronomy</th>
                            <?php endif; ?>
                            <th>Total PP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allGrades as $grade): ?>
                            <tr>
                                <td data-label="Nama">
                                    <?= esc($grade['nama_lengkap']) ?>
                                </td>
                                <td data-label="Sanctuary">
                                    <?= esc($grade['nama_sanctuary'] ?? '-') ?>
                                </td>
                                <?php if ($isVasiki || $hakaesSub === 'pop_culture'): ?>
                                    <td data-label="Pop Culture">
                                        <?= $grade['pop_culture'] ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($isVasiki || $hakaesSub === 'mythology'): ?>
                                    <td data-label="Mythology">
                                        <?= $grade['mythology'] ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($isVasiki || $hakaesSub === 'history_of_egypt'): ?>
                                    <td data-label="History of Egypt">
                                        <?= $grade['history_of_egypt'] ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($isVasiki || $hakaesSub === 'oceanology'): ?>
                                    <td data-label="Oceanology">
                                        <?= $grade['oceanology'] ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($isVasiki || $hakaesSub === 'astronomy'): ?>
                                    <td data-label="Astronomy">
                                        <?= $grade['astronomy'] ?>
                                    </td>
                                <?php endif; ?>
                                <td data-label="Total PP"><strong>
                                        <?= $grade['total_pp'] ?>
                                    </strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="grades-count">
                <?= count($allGrades) ?> siswa
            </p>
        </div>
    <?php endif; ?>
<?php endif; ?>