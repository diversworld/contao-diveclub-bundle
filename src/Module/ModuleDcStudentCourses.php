<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Module;

use Contao\Database;
use Contao\FrontendUser;
use Contao\Module;

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
                    c.id AS course_id, c.title AS course_title, c.course_type, c.dateStart, c.dateEnd
             FROM tl_dc_course_students cs
             INNER JOIN tl_dc_dive_course c ON c.id = cs.course_id
             WHERE cs.pid = ? AND cs.published = 1 AND (c.published = 1)'
        )->execute((int)$student->id);

        $courses = [];
        while ($assignments->next()) {
            $courses[] = [
                'assignment_id' => (int)$assignments->assignment_id,
                'status' => (string)$assignments->status,
                'registered_on' => (string)$assignments->registered_on,
                'payed' => (bool)$assignments->payed,
                'brevet' => (bool)$assignments->brevet,
                'dateBrevet' => (string)$assignments->dateBrevet,
                'course' => [
                    'id' => (int)$assignments->course_id,
                    'title' => (string)$assignments->course_title,
                    'type' => (string)$assignments->course_type,
                    'dateStart' => (string)$assignments->dateStart,
                    'dateEnd' => (string)$assignments->dateEnd,
                ],
            ];
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
