<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Module;

use Contao\Config;
use Contao\Controller;
use Contao\Date;
use Contao\Module;
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

        // Ziel-Artikel aus Modulkonfiguration
        $articleId = (int)($this->dc_reader_article ?? 0);
        $articleUrl = '';
        if ($articleId > 0) {
            // Insert-Tag nutzen, um die Artikel-URL zu generieren (sprach-/seitenabhängig korrekt)
            $articleUrl = (string)Controller::replaceInsertTags('{{article_url::' . $articleId . '}}');
        }

        $list = [];
        if ($events) {
            foreach ($events as $event) {
                // Wenn kein Ziel-Artikel gesetzt ist, kann keine Detail-URL erzeugt werden
                $detailUrl = '';
                if ($articleUrl !== '') {
                    $item = $event->alias ?: (string)$event->id;
                    $detailUrl = rtrim($articleUrl, '/');
                    $detailUrl .= '/' . ($useAutoItem ? '' : 'items/') . $item;
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
        $this->Template->hasReaderArticle = ($articleUrl !== '');
    }
}
