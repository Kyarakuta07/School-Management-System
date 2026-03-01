<div class="class-card schedule-card">
    <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> CLASS SCHEDULE</h3>

    <?php if (!empty($schedules)): ?>
        <div class="schedule-grid">
            <?php foreach ($schedules as $schedule): ?>
                <div class="schedule-item">
                    <div class="schedule-day">
                        <span class="day-name">
                            <?= esc($schedule['schedule_day']) ?>
                        </span>
                        <span class="day-time">
                            <?= esc($schedule['schedule_time']) ?>
                        </span>
                    </div>
                    <div class="schedule-details">
                        <span class="class-name">
                            <?= esc($schedule['class_name']) ?>
                        </span>
                        <span class="teacher-name">
                            <i class="fa-solid fa-user-graduate"></i>
                            <?= esc($schedule['hakaes_name'] ?? '-') ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-schedule">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p>Jadwal kelas belum tersedia.</p>
        </div>
    <?php endif; ?>
</div>