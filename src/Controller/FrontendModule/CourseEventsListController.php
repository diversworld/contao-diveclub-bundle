<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\Date;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
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
                'tag_name' => $headline['unit'] ?? 'h1',
            ];
        } else {
            $template->headline = ['text' => '', 'tag_name' => 'h1'];
        }

        $dateFormat = (string)Config::get('datimFormat');
        $useAutoItem = (bool)Config::get('useAutoItem');

        /**
         * WICHTIG:
         * Wegen DCA-Relation (hasOne, lazy) kann $model->courseJumpTo bereits ein PageModel sein.
         * Daher JumpTo immer robust auflösen.
         */
        $jumpToPage = $this->resolveJumpToPage($model->courseJumpTo ?? null);

        $list = [];

        // Basis: Hat das Modul (für Kurs-Events) eine JumpTo-Seite?
        $hasJumpTo = (null !== $jumpToPage);

        // Lade veröffentlichte Events (Tauchkurse)
        if ($model->showCourseEvents) {
            $events = DcCourseEventModel::findBy(['published=?'], [1], ['order' => 'dateStart']);

            if ($events) {
                foreach ($events as $event) {
                    $detailUrl = '';
                    if (null !== $jumpToPage) {
                        $item = $event->alias ?: (string)$event->id;
                        $params = '/' . ($useAutoItem ? '' : 'items/') . $item;
                        $detailUrl = $jumpToPage->getFrontendUrl($params);
                    }

                    $dateStartTs = $event->dateStart ? (int)$event->dateStart : 0;
                    $dateEndTs = $event->dateEnd ? (int)$event->dateEnd : 0;

                    $list[] = [
                        'id' => (int)$event->id,
                        'title' => (string)$event->title,
                        'alias' => (string)$event->alias,

                        // für Anzeige
                        'dateStart' => $dateStartTs ? Date::parse($dateFormat, $dateStartTs) : '',
                        'dateEnd' => $dateEndTs ? Date::parse($dateFormat, $dateEndTs) : '',

                        // für Sortierung (intern)
                        '_dateStartTs' => $dateStartTs,

                        'instructor' => (string)$event->instructor,
                        'description' => (string)$event->description,
                        'location' => (string)$event->location,
                        'maxParticipants' => (int)$event->maxParticipants,
                        'price' => (string)$event->price,
                        'url' => $detailUrl,
                        'isTankCheck' => false,
                    ];
                }
            }
        }

        // TÜV-Prüfungen hinzufügen, falls aktiviert
        if ($model->showTankChecks) {
            $proposals = DcCheckProposalModel::findBy(['published=?'], [1], ['order' => 'proposalDate DESC']);

            $tankCheckJumpToPage = $this->resolveJumpToPage($model->tankCheckJumpTo ?? null);

            // Hat TankCheck eine JumpTo-Seite? -> dann stimmt's auf jeden Fall
            if (null !== $tankCheckJumpToPage) {
                $hasJumpTo = true;
            } else {
                // Fallback (alt): Existiert irgendwo ein dc_tank_check Modul, dann können Links prinzipiell erzeugt werden
                $tankCheckModule = Database::getInstance()
                    ->prepare("SELECT id FROM tl_module WHERE type=?")
                    ->limit(1)
                    ->execute('dc_tank_check');

                if ($tankCheckModule->numRows) {
                    $hasJumpTo = true;
                }
            }

            if ($proposals) {
                foreach ($proposals as $proposal) {
                    // Startdatum ermitteln (Proposal-Datum oder verknüpftes CalendarEvent-Datum)
                    $dateStartTs = $proposal->proposalDate ? (int)$proposal->proposalDate : 0;

                    if ($proposal->checkId) {
                        $event = CalendarEventsModel::findByPk($proposal->checkId);
                        if ($event && $event->startDate) {
                            $dateStartTs = (int)$event->startDate;
                        }
                    }

                    // URL für den Tank-Check ermitteln
                    $detailUrl = '';
                    if (null !== $tankCheckJumpToPage) {
                        $item = $proposal->alias ?: (string)$proposal->id;
                        $params = '/' . ($useAutoItem ? '' : 'items/') . $item;
                        $detailUrl = $tankCheckJumpToPage->getFrontendUrl($params);
                    } else {
                        // Fallback-Suche (alt), falls kein Sprungziel definiert ist
                        $tankCheckModule = Database::getInstance()
                            ->prepare("SELECT id FROM tl_module WHERE type=?")
                            ->limit(1)
                            ->execute('dc_tank_check');

                        if ($tankCheckModule->numRows) {
                            $page = Database::getInstance()
                                ->prepare("SELECT id, alias FROM tl_page WHERE id=(SELECT pid FROM tl_content WHERE type='module' AND module=? LIMIT 1)")
                                ->execute($tankCheckModule->id);

                            if ($page->numRows) {
                                $pageModel = PageModel::findByPk($page->id);
                                if (null !== $pageModel) {
                                    $item = $proposal->alias ?: (string)$proposal->id;
                                    $detailUrl = $pageModel->getFrontendUrl('/' . ($useAutoItem ? '' : 'items/') . $item);
                                }
                            }
                        }
                    }

                    $list[] = [
                        'id' => (int)$proposal->id,
                        'title' => '[TÜV] ' . (string)$proposal->title,

                        // Anzeige
                        'dateStart' => $dateStartTs ? Date::parse($dateFormat, $dateStartTs) : '',
                        'dateEnd' => '',

                        // Sortierung
                        '_dateStartTs' => $dateStartTs,

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
            }
        }

        // Sortieren nach Timestamp (stabil und unabhängig vom Datumsformat)
        usort($list, static function (array $a, array $b): int {
            return ($a['_dateStartTs'] ?? 0) <=> ($b['_dateStartTs'] ?? 0);
        });

        // Interne Sortierspalte wieder entfernen (Template soll sie nicht sehen müssen)
        foreach ($list as &$row) {
            unset($row['_dateStartTs']);
        }
        unset($row);

        $template->events = $list;
        $template->hasEvents = !empty($list);
        $template->hasJumpTo = $hasJumpTo;

        return $template->getResponse();
    }

    /**
     * Löst ein JumpTo-Feld robust auf:
     * - int/string (Seiten-ID)
     * - oder PageModel (durch DCA-Relation hasOne lazy)
     */
    private function resolveJumpToPage(mixed $jumpToValue): ?PageModel
    {
        if ($jumpToValue instanceof PageModel) {
            return $jumpToValue;
        }

        $id = (int)($jumpToValue ?? 0);
        if ($id <= 0) {
            return null;
        }

        return PageModel::findByPk($id);
    }
}
