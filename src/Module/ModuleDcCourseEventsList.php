<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Module;

use Contao\Config;
use Contao\Date;
use Contao\Module;
use Contao\PageModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;

class ModuleDcCourseEventsList extends Module
{
    protected $strTemplate = 'mod_dc_course_events_list';

    protected function compile(): void
    {
        // Lade veröffentlichte Events
        $events = DcCourseEventModel::findBy(['published=?'], [1], ['order' => 'dateStart']);

        $dateFormat = Config::get('datimFormat');
        $useAutoItem = (bool)Config::get('useAutoItem');

        // Ziel-Seite (Reader) wie im Kalender über jumpTo auswählen
        $jumpTo = (int)($this->jumpTo ?? 0);
        $jumpToPage = $jumpTo > 0 ? PageModel::findByPk($jumpTo) : null;

        $list = [];
        if ($events) {
            foreach ($events as $event) {
                // Wenn keine Ziel-Seite gesetzt ist, kann keine Detail-URL erzeugt werden
                $detailUrl = '';
                if (null !== $jumpToPage) {
                    $item = $event->alias ?: (string)$event->id;
                    $params = '/' . ($useAutoItem ? '' : 'items/') . $item;
                    // Contao 5: generate URL via PageModel helper
                    $detailUrl = $jumpToPage->getFrontendUrl($params);
                }
                $list[] = [
                    'id' => (int)$event->id,
                    'title' => (string)$event->title,
                    'alias' => (string)$event->alias,
                    'dateStart' => $event->dateStart ? Date::parse($dateFormat, (int)strtotime((string)$event->dateStart)) : '',
                    'dateEnd' => $event->dateEnd ? Date::parse($dateFormat, (int)strtotime((string)$event->dateEnd)) : '',
                    'price' => (string)$event->price,
                    'url' => $detailUrl,
                ];
            }
        }

        $this->Template->events = $list;
        $this->Template->hasEvents = !empty($list);
        $this->Template->hasJumpTo = (null !== $jumpToPage);
    }
}
