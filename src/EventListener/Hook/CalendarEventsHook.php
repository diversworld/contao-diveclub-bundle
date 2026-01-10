<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Model\Collection;

#[AsHook('getAllEvents')]
class CalendarEventsHook
{
    /**
     * @param array $allEvents
     * @param array $calendars
     * @param int $startTime
     * @param int $endTime
     * @param Collection|null $events
     * @return array
     */
    public function __invoke(array $allEvents, array $calendars, int $startTime, int $endTime, ?Collection $events): array
    {
        // System::getContainer()->get('monolog.logger.contao.general')->info('CalendarEventsHook called');

        foreach ($allEvents as $day => $times) {
            foreach ($times as $time => $eventsInTime) {
                foreach ($eventsInTime as $key => $event) {
                    // Filter out events that are tank checks or courses in the standard calendar
                    // but allow them if it's explicitly desired.
                    // This hook affects the standard getAllEvents which is used by many modules.
                    // If we want to show them in our special CourseEventsList, we don't use the standard calendar.
                    if (!empty($event['addCheckInfo']) || !empty($event['addCourseInfo'])) {
                        // unset($allEvents[$day][$time][$key]);
                    }
                }

                if (empty($allEvents[$day][$time])) {
                    unset($allEvents[$day][$time]);
                }
            }

            if (empty($allEvents[$day])) {
                unset($allEvents[$day]);
            }
        }

        return $allEvents;
    }
}
