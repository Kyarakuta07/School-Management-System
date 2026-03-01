<div class="class-card subjects-card">
    <h3 class="card-title"><i class="fa-solid fa-book"></i> SUBJECTS</h3>

    <div class="subjects-grid">
        <?php
        $subjectDescriptions = [
            'pop_culture' => 'Explore modern Egyptian influence in movies, games, music, and global media.',
            'mythology' => 'Discover the stories of Ra, Osiris, Isis, and the ancient Egyptian pantheon.',
            'history_of_egypt' => 'Journey through the ages of Pharaohs, pyramids, and the rise of civilization.',
            'oceanology' => 'Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.',
            'astronomy' => 'Read the stars, navigate the desert sands, and predict the empire\'s fate.',
        ];
        foreach ($subjects as $key => $subject): ?>
            <a href="<?= base_url('subject?subject=' . $key) ?>" class="subject-card <?= $key ?>">
                <div class="subject-icon"><i class="fa-solid <?= $subject['icon'] ?>"></i></div>
                <h4>
                    <?= $subject['name'] ?>
                </h4>
                <p>
                    <?= $subjectDescriptions[$key] ?? '' ?>
                </p>
            </a>
        <?php endforeach; ?>
    </div>
</div>