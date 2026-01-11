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
use Contao\Frontend;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Contao\CalendarEventsModel;
use Contao\Database;
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
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        } else {
            $template->headline = ['text' => '', 'tag_name' => 'h1'];
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
                    'instructor' => (string)$event->instructor,
                    'description' => (string)$event->description,
                    'location' => (string)$event->location,
                    'maxParticipants' => (int)$event->maxParticipants,
                    'price' => (string)$event->price,
                    'url' => $detailUrl,
                ];
            }
        }

        $template->events = $list;
        $template->hasEvents = !empty($list);

        // TÜV-Prüfungen hinzufügen, falls aktiviert
        if ($model->showTankChecks) {
            $proposals = DcCheckProposalModel::findBy(['published=?'], [1], ['order' => 'proposalDate DESC']);

            $tankCheckJumpTo = (int)($model->tankCheckJumpTo ?? 0);
            $tankCheckJumpToPage = $tankCheckJumpTo > 0 ? PageModel::findByPk($tankCheckJumpTo) : null;

            if ($proposals) {
                foreach ($proposals as $proposal) {
                    $dateStart = $proposal->proposalDate;

                    if ($proposal->checkId) {
                        $event = CalendarEventsModel::findByPk($proposal->checkId);
                        if ($event) {
                            $dateStart = (string)$event->startDate;
                        }
                    }

                    // URL für den Tank-Check ermitteln
                    $detailUrl = '';
                    if (null !== $tankCheckJumpToPage) {
                        $item = $proposal->alias ?: (string)$proposal->id;
                        $params = '/' . ($useAutoItem ? '' : '/') . $item;
                        $detailUrl = $tankCheckJumpToPage->getFrontendUrl($params);
                    } else {
                        // Fallback-Suche (alt), falls kein Sprungziel definiert ist
                        $tankCheckModule = Database::getInstance()->prepare("SELECT id FROM tl_module WHERE type=?")->limit(1)->execute('dc_tank_check');
                        if ($tankCheckModule->numRows) {
                            $page = Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=(SELECT pid FROM tl_content WHERE type='module' AND module=? LIMIT 1)")
                                ->execute($tankCheckModule->id);
                            if ($page->numRows) {
                                $pageModel = PageModel::findByPk($page->id);
                                if ($pageModel) {
                                    $detailUrl = $pageModel->getFrontendUrl('/' . ($useAutoItem ? '' : 'items/') . ($proposal->alias ?: $proposal->id));
                                }
                            }
                        }
                    }
                    $list[] = [
                        'id' => (int)$proposal->id,
                        'title' => '[TÜV] ' . (string)$proposal->title,
                        'dateStart' => $dateStart ? Date::parse($dateFormat, (int)$dateStart) : '',
                        'dateEnd' => '',
                        'instructor' => '',
                        'location' => '',
                        'maxParticipants' => 0,
                        'price' => '',
                        'vendorName' => (string)$proposal->vendorName,
                        'description' => (string)$proposal->notes,
                        'isTankCheck' => true,
                        'url' => $detailUrl,
                    ];
                }

                // Erneut sortieren nach Datum
                usort($list, function($a, $b) {
                    return strtotime($a['dateStart']) <=> strtotime($b['dateStart']);
                });

                $template->events = $list;
                $template->hasEvents = true;
            }
        }

        $template->hasJumpTo = (null !== $jumpToPage);

        return $template->getResponse();
    }
}
