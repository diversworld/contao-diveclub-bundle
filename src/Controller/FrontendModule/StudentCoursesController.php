<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\Date;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function is_array;

#[AsFrontendModule('dc_student_courses', category: 'dc_manager', template: 'frontend_module/mod_dc_student_courses')]
class StudentCoursesController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'unit' => $headline['unit'] ?? 'h1'
            ];
        } else {
            $template->headline = null;
        }

        /** @var FrontendUser|null $user */
        $user = System::getContainer()->get('security.helper')->getUser();

        if (!$user instanceof FrontendUser) {
            $template->isLoggedIn = false;
            $template->courses = [];
            return $template->getResponse();
        }

        $template->isLoggedIn = true;

        $db = Database::getInstance();

        // 1) Finde den verknüpften Tauchschüler über memberId
        $student = $db
            ->prepare('SELECT id, firstname, lastname FROM tl_dc_students WHERE memberId=?')
            ->execute((int)$user->id);

        if ($student->numRows < 1) {
            $template->studentFound = false;
            $template->courses = [];
            return $template->getResponse();
        }

        $template->studentFound = true;
        $template->student = [
            'id' => (int)$student->id,
            'firstname' => (string)$student->firstname,
            'lastname' => (string)$student->lastname,
        ];

        // 2) Lade Kurszuweisungen inkl. Kursdetails (nur aktive Kurse: Status nicht 'Absolviert' oder 'Nicht erreicht')
        $assignments = $db->prepare(
            "SELECT cs.id AS assignment_id, cs.status, cs.registered_on, cs.payed, cs.brevet, cs.dateBrevet,
                    c.id AS course_id, c.title AS course_title, c.course_type,  c.category, c.dateStart, c.dateEnd
             FROM tl_dc_course_students cs
             INNER JOIN tl_dc_dive_course c ON c.id = cs.course_id
             WHERE cs.pid = ?
               AND cs.published = 1
               AND c.published = 1
               AND cs.status NOT IN ('completed', 'failed')
             ORDER BY c.dateStart DESC"
        )->execute((int)$student->id);

        // Fortschritt-Seite (Reader)
        $jumpToPage = null;
        if ($model->jumpTo > 0) {
            $jumpToPage = PageModel::findByPk($model->jumpTo);
        }

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
                'progress_url' => $jumpToPage ? $jumpToPage->getFrontendUrl() . '?assignment=' . $assignments->assignment_id : '',
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
        }

        $template->courses = $courses;
        $template->hasCourses = !empty($courses);

        // Sprachtexte verfügbar machen (aus MSC)
        $labels = $GLOBALS['TL_LANG']['MSC']['dc_student_courses'] ?? null;
        $template->labels = $labels ?? [
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
            'view_progress' => 'Kursfortschritt anzeigen',
        ];

        return $template->getResponse();
    }
}
