<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Module;

use Contao\Config;
use Contao\Database;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Module;
use Contao\System;

class ModuleDcStudentCourses extends Module
{
    protected $strTemplate = 'mod_dc_student_courses';

    protected function compile(): void
    {
        /** @var FrontendUser|null $user */
        $user = FrontendUser::getInstance();

        if (!$user || !$user->id) {
            $this->Template->isLoggedIn = false;
            $this->Template->courses = [];
            return;
        }

        $this->Template->isLoggedIn = true;

        $db = Database::getInstance();

        // 1) Finde den verknüpften Tauchschüler über memberId
        $student = $db
            ->prepare('SELECT id, firstname, lastname FROM tl_dc_students WHERE memberId=?')
            ->execute((int)$user->id);

        if ($student->numRows < 1) {
            $this->Template->studentFound = false;
            $this->Template->courses = [];
            return;
        }

        $this->Template->studentFound = true;
        $this->Template->student = [
            'id' => (int)$student->id,
            'firstname' => (string)$student->firstname,
            'lastname' => (string)$student->lastname,
        ];

        // 2) Lade Kurszuweisungen inkl. Kursdetails
        $assignments = $db->prepare(
            'SELECT cs.id AS assignment_id, cs.status, cs.registered_on, cs.payed, cs.brevet, cs.dateBrevet,
                    c.id AS course_id, c.title AS course_title, c.course_type,  c.category, c.dateStart, c.dateEnd
             FROM tl_dc_course_students cs
             INNER JOIN tl_dc_dive_course c ON c.id = cs.course_id
             WHERE cs.pid = ? AND cs.published = 1 AND (c.published = 1)'
        )->execute((int)$student->id);

        // Systemweite Datums-/Zeitformate aus Contao-Konfiguration
        $dateFormat = Config::get('dateFormat');
        $datimFormat = Config::get('datimFormat');

        $formatTs = static function ($value, string $format): string {
            if ($value === null || $value === '') {
                return '';
            }

            // numerischer Timestamp oder String
            if (is_numeric($value)) {
                $ts = (int)$value;
            } else {
                $parsed = strtotime((string)$value);
                if ($parsed === false) {
                    return (string)$value; // Fallback: Originalwert
                }
                $ts = $parsed;
            }

            return Date::parse($format, $ts);
        };

        // WICHTIG: Lade die Sprachdateien explizit für das Frontend
        System::loadLanguageFile('tl_dc_course_students');
        System::loadLanguageFile('tl_dc_dive_course');

        $courses = [];
        while ($assignments->next()) {
            // Werte vorformatieren gemäß Systemformaten
            $registeredOn = $formatTs($assignments->registered_on, $dateFormat);
            $dateBrevet = $formatTs($assignments->dateBrevet, $dateFormat);
            $dateStart = $formatTs($assignments->dateStart, $datimFormat);
            $dateEnd = $formatTs($assignments->dateEnd, $datimFormat);

            $courses[] = [
                'assignment_id' => (int)$assignments->assignment_id,
                // Status-Label aus Sprachdatei (ohne Referenzen), Fallback auf Rohwert
                'status' => $GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'][(string)$assignments->status]
                    ?? (string)$assignments->status,
                'registered_on' => $registeredOn,
                'payed' => (bool)$assignments->payed,
                'brevet' => (bool)$assignments->brevet,
                'dateBrevet' => $dateBrevet,
                'course' => [
                    'id' => (int)$assignments->course_id,
                    'title' => (string)$assignments->course_title,
                    // Kurstyp: Korrekte Sprach-Namespace verwenden und Fallback auf Rohwert
                    'type' => $GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCourseType'][(string)$assignments->course_type]
                        ?? (string)$assignments->course_type,
                    'category' => $GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCategory'][(string)$assignments->category] ?? (string)$assignments->category,
                    'dateStart' => $dateStart,
                    'dateEnd' => $dateEnd,
                ],
            ];
            dump($courses);
        }

        $this->Template->courses = $courses;
        $this->Template->hasCourses = !empty($courses);

        // Sprachtexte verfügbar machen
        $this->Template->labels = $GLOBALS['TL_LANG']['FMD']['dc_student_courses_labels'] ?? [
            'headline' => 'Meine Tauchkurse',
            'noStudent' => 'Kein verknüpfter Tauchschüler gefunden.',
            'noCourses' => 'Für Sie sind derzeit keine Tauchkurse gespeichert.',
            'course' => 'Kurs',
            'status' => 'Status',
            'registered_on' => 'Angemeldet am',
            'payed' => 'Bezahlt',
            'brevet' => 'Brevet erteilt',
            'dateBrevet' => 'Brevet am',
            'dateStart' => 'Beginn',
            'dateEnd' => 'Ende',
        ];
    }
}
