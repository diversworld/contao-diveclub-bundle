<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('dc_course_event_calendar', category: 'dc_manager', template: 'frontend_module/mod_dc_course_event_calendar')]
class CourseEventCalendarController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->id = $model->id;
        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';

        // Headline
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        }

        // Aktuelles Event ermitteln (analog zum Reader)
        $identifier = Input::get('event') ?: Input::get('items');
        $event = null;

        if ($identifier) {
            if (is_numeric($identifier)) {
                $event = DcCourseEventModel::findByPk((int)$identifier);
            } else {
                $event = DcCourseEventModel::findOneBy(['alias=?', 'published=?'], [$identifier, 1]);
            }
        }

        // --- Zeitplan-Daten laden ---
        $db = System::getContainer()->get('database_connection');
        $schedule = [];

        if ($event && (int)$event->published === 1) {
            // Nur Zeitplan für DIESES Event
            $schedule = $db->fetchAllAssociative(
                'SELECT s.id, s.planned_at, s.location, s.instructor, s.notes, m.title AS module_title, e.title AS event_title
                 FROM tl_dc_course_event_schedule s
                 INNER JOIN tl_dc_course_modules m ON m.id = s.module_id
                 INNER JOIN tl_dc_course_event e ON e.id = s.pid
                 WHERE s.pid = ?
                 ORDER BY s.planned_at',
                [(int)$event->id]
            );
        } else {
            // Wenn kein spezifisches Event gewählt ist: Im Backend (Preview/Edit) Info anzeigen
            if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
                $template->hasEvents = false;
                $template->be_message = 'Dieses Modul zeigt den Zeitplan an, wenn es zusammen mit einem Reader auf einer Seite platziert wird.';
                return $template->getResponse();
            }

            // Wenn im Frontend kein Event gewählt ist, laden wir ALLE Zeitplan-Einträge
            // für VERÖFFENTLICHTE Events, um eine Gesamtübersicht im Kalender zu ermöglichen.
            $schedule = $db->fetchAllAssociative(
                'SELECT s.id, s.planned_at, s.location, s.instructor, s.notes, m.title AS module_title, e.title AS event_title
                 FROM tl_dc_course_event_schedule s
                 INNER JOIN tl_dc_course_modules m ON m.id = s.module_id
                 INNER JOIN tl_dc_course_event e ON e.id = s.pid
                 WHERE e.published = ?
                 ORDER BY s.planned_at',
                [1]
            );
        }

        // --- Contao Calendar Logic ---
        $month = Input::get('month');
        // Create current date object
        $objDate = new Date();

        try {
            if ($month) {
                $objDate = new Date((string)$month, 'Ym');
            }
        } catch (\Exception $e) {
            // Fallback to current date already set
        }

        $intYear = (int) date('Y', $objDate->tstamp);
        $intMonth = (int) date('m', $objDate->tstamp);
        $monthBegin = $objDate->monthBegin;
        $monthEnd = $objDate->monthEnd;

        // Group events by date (Ymd)
        $eventsByDate = [];
        $dateFormat = Config::get('dateFormat');
        $timeFormat = Config::get('timeFormat');

        foreach ($schedule as $row) {
            if (!$row['planned_at']) continue;
            $key = date('Ymd', (int)$row['planned_at']);
            $eventsByDate[$key][] = [
                'title' => $row['module_title'],
                'date' => Date::parse($dateFormat, (int)$row['planned_at']),
                'time' => Date::parse($timeFormat, (int)$row['planned_at']),
                'location' => $row['location'],
                'instructor' => $row['instructor'],
                'notes' => $row['notes'],
                'event_title' => $row['event_title'] ?? null
            ];
        }

        // Navigation
        $strUrl = $request->getPathInfo();
        $queryParams = $request->query->all();

        $prevMonth = ($intMonth == 1) ? 12 : ($intMonth - 1);
        $prevYear = ($intMonth == 1) ? ($intYear - 1) : $intYear;
        $intPrevYm = $prevYear . str_pad((string)$prevMonth, 2, '0', STR_PAD_LEFT);

        $nextMonth = ($intMonth == 12) ? 1 : ($intMonth + 1);
        $nextYear = ($intMonth == 12) ? ($intYear + 1) : $intYear;
        $intNextYm = $nextYear . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT);

        $template->prevHref = $strUrl . '?' . http_build_query(array_merge($queryParams, ['month' => $intPrevYm]));
        $template->prevTitle = $GLOBALS['TL_LANG']['MONTHS'][$prevMonth - 1] . ' ' . $prevYear;
        $template->prevLabel = $GLOBALS['TL_LANG']['MSC']['cal_previous'];

        $template->nextHref = $strUrl . '?' . http_build_query(array_merge($queryParams, ['month' => $intNextYm]));
        $template->nextTitle = $GLOBALS['TL_LANG']['MONTHS'][$nextMonth - 1] . ' ' . $nextYear;
        $template->nextLabel = $GLOBALS['TL_LANG']['MSC']['cal_next'];

        $template->currentMonth = $GLOBALS['TL_LANG']['MONTHS'][$intMonth - 1] . ' ' . $intYear;

        $template->id = $model->id; // Ensure ID is passed for potential JS or CSS targeting

        // Compile Days & Weeks
        $startDay = 0; // Sunday
        if (isset($GLOBALS['TL_LANG']['MSC']['weekStart'])) {
            $startDay = (int)$GLOBALS['TL_LANG']['MSC']['weekStart'];
        }

        $days = [];
        for ($i=0; $i<7; $i++) {
            $dayNum = ($i + $startDay) % 7;
            $days[$dayNum] = [
                'class' => ($dayNum == 0 || $dayNum == 6) ? ' weekend' : '',
                'name' => $GLOBALS['TL_LANG']['DAYS'][$dayNum]
            ];
        }
        $template->days = $days;

        $weeks = [];
        $intDaysInMonth = (int) date('t', $monthBegin);
        $intFirstDayOffset = (int) date('w', $monthBegin) - $startDay;
        if ($intFirstDayOffset < 0) $intFirstDayOffset += 7;

        $intNumberOfRows = (int) ceil(($intDaysInMonth + $intFirstDayOffset) / 7);
        $intColumnCount = -1;

        // Current month and year for mktime
        $intCurrentYear = (int)date('Y', $monthBegin);
        $intCurrentMonth = (int)date('m', $monthBegin);

        for ($i=1; $i<=($intNumberOfRows * 7); $i++) {
            $intWeek = (int) floor(++$intColumnCount / 7);
            $dayInMonth = $i - $intFirstDayOffset;
            $currentDayNum = ($i + $startDay) % 7;
            $class = ($currentDayNum == 0 || $currentDayNum == 6) ? ' weekend' : '';

            if ($dayInMonth < 1 || $dayInMonth > $intDaysInMonth) {
                $weeks[$intWeek][] = [
                    'label' => '&nbsp;',
                    'class' => 'empty' . $class,
                    'events' => []
                ];
                continue;
            }

            // Use the actual date for the key to correctly handle days from previous/next month
            $currentDayTimestamp = mktime(12, 0, 0, $intCurrentMonth, $dayInMonth, $intCurrentYear);
            $dateKey = date('Ymd', $currentDayTimestamp);

            if ($dateKey == date('Ymd')) $class .= ' today';

            $weeks[$intWeek][] = [
                'label' => (string)$dayInMonth,
                'class' => trim($class),
                'events' => $eventsByDate[$dateKey] ?? []
            ];
        }
        $template->weeks = $weeks;
        $template->hasEvents = !empty($schedule);

        return $template->getResponse();
    }
}
