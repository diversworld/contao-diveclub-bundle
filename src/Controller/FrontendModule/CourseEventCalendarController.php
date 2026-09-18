<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


#[AsFrontendModule(CourseEventCalendarController::TYPE, category: 'dc_manager', template: 'frontend_module/dc_course_event_calendar')]
class CourseEventCalendarController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_course_event_calendar';

    public function __construct()
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $templateData = [
            'id' => $model->id,
            'element_html_id' => 'mod_' . $model->id,
            'element_css_classes' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'class' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'cssID' => $model->cssID[0] ?? '',
            'type' => $model->type,
        ];

        // Headline
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $templateData['headline'] = [
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
                $templateData['hasEvents'] = false;
                $templateData['be_message'] = 'Dieses Modul zeigt den Zeitplan an, wenn es zusammen mit einem Reader auf einer Seite platziert wird.';

                foreach ($templateData as $key => $value) {
                    $template->set($key, $value);
                }
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

        $calendarView = (string)($model->dc_calendar_view ?: 'dayGridMonth');
        if (!in_array($calendarView, ['dayGridMonth', 'timeGridWeek', 'listYear'], true)) {
            $calendarView = 'dayGridMonth';
        }

        $templateData['calendarView'] = $calendarView;

        // Group events by date (Ymd)
        $eventsByDate = [];
        $dateFormat = Config::get('dateFormat');
        $timeFormat = Config::get('timeFormat');

        foreach ($schedule as $row) {
            if (!$row['planned_at']) continue;
            $key = date('Ymd', (int)$row['planned_at']);
            $eventsByDate[$key][] = [
                'timestamp' => (int)$row['planned_at'],
                'title' => $row['module_title'],
                'date' => Date::parse($dateFormat, (int)$row['planned_at']),
                'time' => Date::parse($timeFormat, (int)$row['planned_at']),
                'location' => $row['location'],
                'instructor' => $row['instructor'],
                'notes' => $row['notes'],
                'event_title' => $row['event_title'] ?? null
            ];
        }

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
        $templateData['days'] = $days;

        if ($calendarView === 'timeGridWeek') {
            $this->buildWeekView($templateData, $eventsByDate, $request, $startDay);
        } elseif ($calendarView === 'listYear') {
            $this->buildYearView($templateData, $schedule, $request, (string)$dateFormat, (string)$timeFormat);
        } else {
            $this->buildMonthView($templateData, $eventsByDate, $request, $startDay);
        }

        if ($calendarView !== 'listYear') {
            $templateData['hasEvents'] = !empty($schedule);
        }

        foreach ($templateData as $key => $value) {
            $template->set($key, $value);
        }

        return $template->getResponse();
    }

    private function buildMonthView(array &$templateData, array $eventsByDate, Request $request, int $startDay): void
    {
        $month = Input::get('month');
        $objDate = new Date();

        try {
            if ($month) {
                $objDate = new Date((string)$month, 'Ym');
            }
        } catch (\Exception) {
            $objDate = new Date();
        }

        $intYear = (int)date('Y', $objDate->tstamp);
        $intMonth = (int)date('m', $objDate->tstamp);
        $monthBegin = $objDate->monthBegin;

        $prevMonth = ($intMonth === 1) ? 12 : ($intMonth - 1);
        $prevYear = ($intMonth === 1) ? ($intYear - 1) : $intYear;
        $nextMonth = ($intMonth === 12) ? 1 : ($intMonth + 1);
        $nextYear = ($intMonth === 12) ? ($intYear + 1) : $intYear;

        $this->setNavigation(
            $templateData,
            $request,
            'month',
            $prevYear . str_pad((string)$prevMonth, 2, '0', STR_PAD_LEFT),
            $nextYear . str_pad((string)$nextMonth, 2, '0', STR_PAD_LEFT),
            $GLOBALS['TL_LANG']['MONTHS'][$prevMonth - 1] . ' ' . $prevYear,
            $GLOBALS['TL_LANG']['MONTHS'][$nextMonth - 1] . ' ' . $nextYear,
            $GLOBALS['TL_LANG']['MONTHS'][$intMonth - 1] . ' ' . $intYear
        );

        $weeks = [];
        $intDaysInMonth = (int)date('t', $monthBegin);
        $intFirstDayOffset = (int)date('w', $monthBegin) - $startDay;
        if ($intFirstDayOffset < 0) {
            $intFirstDayOffset += 7;
        }

        $intNumberOfRows = (int)ceil(($intDaysInMonth + $intFirstDayOffset) / 7);
        $intColumnCount = -1;
        $intCurrentYear = (int)date('Y', $monthBegin);
        $intCurrentMonth = (int)date('m', $monthBegin);

        for ($i = 1; $i <= ($intNumberOfRows * 7); $i++) {
            $intWeek = (int)floor(++$intColumnCount / 7);
            $dayInMonth = $i - $intFirstDayOffset;
            $currentDayNum = ($i + $startDay) % 7;
            $class = ($currentDayNum === 0 || $currentDayNum === 6) ? ' weekend' : '';

            if ($dayInMonth < 1 || $dayInMonth > $intDaysInMonth) {
                $weeks[$intWeek][] = [
                    'label' => '&nbsp;',
                    'class' => 'empty' . $class,
                    'events' => [],
                ];
                continue;
            }

            $currentDayTimestamp = mktime(12, 0, 0, $intCurrentMonth, $dayInMonth, $intCurrentYear);
            $dateKey = date('Ymd', $currentDayTimestamp);

            if ($dateKey === date('Ymd')) {
                $class .= ' today';
            }

            $weeks[$intWeek][] = [
                'label' => (string)$dayInMonth,
                'class' => trim($class),
                'events' => $eventsByDate[$dateKey] ?? [],
            ];
        }

        $templateData['weeks'] = $weeks;
    }

    private function buildWeekView(array &$templateData, array $eventsByDate, Request $request, int $startDay): void
    {
        $weekValue = (string)Input::get('week');
        $weekStart = $this->getCurrentWeekStart($weekValue, $startDay);
        $weekEnd = $weekStart + (6 * 86400);

        $prevWeek = $weekStart - (7 * 86400);
        $nextWeek = $weekStart + (7 * 86400);

        $this->setNavigation(
            $templateData,
            $request,
            'week',
            date('o-\WW', $prevWeek),
            date('o-\WW', $nextWeek),
            'KW ' . date('W', $prevWeek),
            'KW ' . date('W', $nextWeek),
            'KW ' . date('W', $weekStart) . ' ' . date('Y', $weekStart)
        );

        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $timestamp = $weekStart + ($i * 86400);
            $dayNum = (int)date('w', $timestamp);
            $dateKey = date('Ymd', $timestamp);
            $class = ($dayNum === 0 || $dayNum === 6) ? ' weekend' : '';

            if ($dateKey === date('Ymd')) {
                $class .= ' today';
            }

            $week[] = [
                'label' => date('d.m.', $timestamp),
                'class' => trim($class),
                'events' => $eventsByDate[$dateKey] ?? [],
            ];
        }

        $templateData['weeks'] = [$week];
        $templateData['weekRange'] = Date::parse(Config::get('dateFormat'), $weekStart) . ' - ' . Date::parse(Config::get('dateFormat'), $weekEnd);
    }

    private function buildYearView(array &$templateData, array $schedule, Request $request, string $dateFormat, string $timeFormat): void
    {
        $year = (int)(Input::get('year') ?: date('Y'));
        if ($year < 1970 || $year > 2100) {
            $year = (int)date('Y');
        }

        $this->setNavigation(
            $templateData,
            $request,
            'year',
            (string)($year - 1),
            (string)($year + 1),
            (string)($year - 1),
            (string)($year + 1),
            (string)$year
        );

        $months = [];
        foreach ($schedule as $row) {
            $timestamp = (int)($row['planned_at'] ?? 0);
            if (!$timestamp || (int)date('Y', $timestamp) !== $year) {
                continue;
            }

            $monthKey = (int)date('n', $timestamp);
            $months[$monthKey]['label'] = $GLOBALS['TL_LANG']['MONTHS'][$monthKey - 1] . ' ' . $year;
            $months[$monthKey]['events'][] = [
                'timestamp' => $timestamp,
                'title' => $row['module_title'],
                'date' => Date::parse($dateFormat, $timestamp),
                'time' => Date::parse($timeFormat, $timestamp),
                'location' => $row['location'],
                'instructor' => $row['instructor'],
                'notes' => $row['notes'],
                'event_title' => $row['event_title'] ?? null,
            ];
        }

        ksort($months);
        $templateData['yearMonths'] = $months;
        $templateData['hasEvents'] = !empty($months);
    }

    private function getCurrentWeekStart(string $weekValue, int $startDay): int
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $weekValue, $matches)) {
            $date = new \DateTimeImmutable();
            $timestamp = $date->setISODate((int)$matches[1], (int)$matches[2])->setTime(12, 0)->getTimestamp();
        } else {
            $timestamp = time();
        }

        $dayOffset = ((int)date('w', $timestamp) - $startDay + 7) % 7;

        return strtotime('-' . $dayOffset . ' days', $timestamp) ?: $timestamp;
    }

    private function setNavigation(
        array   &$templateData,
        Request $request,
        string  $parameter,
        string  $previousValue,
        string  $nextValue,
        string  $previousTitle,
        string  $nextTitle,
        string  $currentLabel
    ): void
    {
        $queryParams = $request->query->all();
        unset($queryParams['month'], $queryParams['week'], $queryParams['year']);

        $templateData['prevHref'] = $request->getPathInfo() . '?' . http_build_query(array_merge($queryParams, [$parameter => $previousValue]));
        $templateData['prevTitle'] = $previousTitle;
        $templateData['prevLabel'] = $GLOBALS['TL_LANG']['MSC']['cal_previous'];
        $templateData['nextHref'] = $request->getPathInfo() . '?' . http_build_query(array_merge($queryParams, [$parameter => $nextValue]));
        $templateData['nextTitle'] = $nextTitle;
        $templateData['nextLabel'] = $GLOBALS['TL_LANG']['MSC']['cal_next'];
        $templateData['currentMonth'] = $currentLabel;
        $templateData['currentLabel'] = $currentLabel;
    }
}
