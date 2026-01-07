<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function is_array;

#[AsFrontendModule('dc_course_events_list', category: 'dc_manager', template: 'frontend_module/mod_dc_course_events_list')]
class CourseEventsListController extends AbstractFrontendModuleController
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
        }

        // Lade veröffentlichte Events
        $events = DcCourseEventModel::findBy(['published=?'], [1], ['order' => 'dateStart']);

        $dateFormat = Config::get('datimFormat');
        $useAutoItem = (bool)Config::get('useAutoItem');

        // Ziel-Seite (Reader) wie im Kalender über jumpTo auswählen
        $jumpTo = (int)($model->jumpTo ?? 0);
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
                    'dateStart' => $event->dateStart ? Date::parse($dateFormat, (int)$event->dateStart) : '',
                    'dateEnd' => $event->dateEnd ? Date::parse($dateFormat, (int)$event->dateEnd) : '',
                    'price' => (string)$event->price,
                    'url' => $detailUrl,
                ];
            }
        }

        $template->events = $list;
        $template->hasEvents = !empty($list);
        $template->hasJumpTo = (null !== $jumpToPage);

        return $template->getResponse();
    }
}
