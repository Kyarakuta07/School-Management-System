<?php

namespace App\Config;

/**
 * PunishmentConfig — Centralized punishment-related static data.
 * Extracted from PunishmentPageController to keep controllers lean.
 */
class PunishmentConfig
{
    /**
     * Violation types with bilingual labels.
     */
    public static function getViolationTypes(): array
    {
        return [
            'Academic Dishonesty' => 'Ketidakjujuran Akademik',
            'Disrespect' => 'Tidak Hormat',
            'Attendance Issue' => 'Masalah Kehadiran',
            'Property Damage' => 'Kerusakan Properti',
            'Safety Violation' => 'Pelanggaran Keamanan',
            'Other' => 'Lainnya',
        ];
    }

    /**
     * Punishment types with bilingual labels.
     */
    public static function getPunishmentTypes(): array
    {
        return [
            'Warning' => 'Peringatan',
            'Feature Lock' => 'Penguncian Fitur',
            'Suspension' => 'Skorsing',
            'Probation' => 'Masa Percobaan',
        ];
    }

    /**
     * Code of Conduct categories with rules, icons, severity, and point ranges.
     */
    public static function getCodeOfConduct(): array
    {
        return [
            [
                'category' => 'Academic Integrity',
                'icon' => 'fa-book',
                'severity' => 'High',
                'points' => '10-20',
                'rules' => [
                    'No cheating during examinations or assignments',
                    'Properly cite all sources in academic work',
                    'Do not plagiarize or copy others\' work',
                ],
            ],
            [
                'category' => 'Respect & Conduct',
                'icon' => 'fa-handshake',
                'severity' => 'Medium',
                'points' => '5-15',
                'rules' => [
                    'Treat all members with respect and dignity',
                    'No bullying, harassment, or discrimination',
                    'Maintain appropriate language in all communications',
                ],
            ],
            [
                'category' => 'Attendance & Punctuality',
                'icon' => 'fa-clock',
                'severity' => 'Low',
                'points' => '2-5',
                'rules' => [
                    'Attend all scheduled classes and activities',
                    'Arrive on time for all sessions',
                    'Notify in advance if unable to attend',
                ],
            ],
            [
                'category' => 'Safety & Security',
                'icon' => 'fa-shield-alt',
                'severity' => 'High',
                'points' => '15-25',
                'rules' => [
                    'Follow all safety protocols and guidelines',
                    'Report any security concerns immediately',
                    'Do not bring prohibited items to sanctuary',
                ],
            ],
        ];
    }
}
