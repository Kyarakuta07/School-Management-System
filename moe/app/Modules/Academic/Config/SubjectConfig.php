<?php

namespace App\Modules\Academic\Config;

/**
 * SubjectConfig — Centralized subject definitions.
 * Replaces duplicated $validSubjects arrays across controllers.
 */
class SubjectConfig
{
    /**
     * Full subject definitions with metadata.
     */
    public static array $subjects = [
        'pop_culture' => ['icon' => 'fa-film', 'color' => '#e74c3c', 'name' => 'Pop Culture', 'desc' => 'Explore modern Egyptian influence in movies, games, music, and global media.'],
        'mythology' => ['icon' => 'fa-ankh', 'color' => '#9b59b6', 'name' => 'Mythology', 'desc' => 'Discover the stories of Ra, Osiris, Isis, and the ancient Egyptian pantheon.'],
        'history_of_egypt' => ['icon' => 'fa-landmark', 'color' => '#f39c12', 'name' => 'History of Egypt', 'desc' => 'Journey through the ages of Pharaohs, pyramids, and the rise of civilization.'],
        'oceanology' => ['icon' => 'fa-water', 'color' => '#00bcd4', 'name' => 'Oceanology', 'desc' => 'Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.'],
        'astronomy' => ['icon' => 'fa-star', 'color' => '#2ecc71', 'name' => 'Astronomy', 'desc' => 'Read the stars, navigate the desert sands, and predict the empire\'s fate.'],
    ];

    /**
     * Get all valid subject keys.
     * @return string[]
     */
    public static function getSubjectKeys(): array
    {
        return array_keys(self::$subjects);
    }

    /**
     * Check if a subject key is valid.
     */
    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::$subjects);
    }

    /**
     * Get a single subject's metadata.
     * @return array|null
     */
    public static function getSubject(string $key): ?array
    {
        return self::$subjects[$key] ?? null;
    }
}
