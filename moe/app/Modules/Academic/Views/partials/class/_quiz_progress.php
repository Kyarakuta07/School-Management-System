<?php if ($userRole === 'Nethera' && !empty($studentProgress)): ?>
    <!-- STUDENT QUIZ PROGRESS -->
    <div class="class-card" style="border-color: rgba(52, 152, 219, 0.4);">
        <h3 class="card-title" style="color: #3498db;">
            <i class="fa-solid fa-tasks"></i> MY QUIZ PROGRESS
        </h3>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($subjects as $key => $subject):
                $progress = $studentProgress[$key] ?? ['total' => 0, 'completed' => 0, 'percentage' => 0];
                ?>
                <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid <?= $subject['icon'] ?>" style="color: <?= $subject['color'] ?>;"></i>
                            <span style="font-weight: 500; color: #fff;">
                                <?= $subject['name'] ?>
                            </span>
                        </div>
                        <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                            <?= $progress['completed'] ?>/
                            <?= $progress['total'] ?> quizzes
                        </span>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                        <div
                            style="width: <?= $progress['percentage'] ?>%; height: 100%; background: <?= $subject['color'] ?>; transition: width 0.5s;">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>