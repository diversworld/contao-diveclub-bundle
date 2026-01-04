<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCalendarEventsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Doctrine\DBAL\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(DcListingController::TYPE, category: 'dc_manager', template: 'mod_dc_listing')]
class DcListingController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_listing';

    protected ?PageModel $page;

    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $eventAlias = Input::get('auto_item');

        /** @var Result $eventStmt */
        $event = DcCalendarEventsModel::findByAlias($eventAlias);

        $proposal = DcCheckProposalModel::findBy('checkId', $event->id);

        if ($proposal !== false) {
            $articles = DcCheckArticlesModel::findBy('pid', $proposal->id);
        } else {
            $articles = [];
        }

        // Prüfen, ob ein Event gefunden wurde
        if ($event !== false) {
            // Daten vorbereiten und ans Template übergeben
            $template->event = $event ?: [];
            $template->proposal = $proposal ?: [];
            $template->articles = $articles ?: [];
        } else {
            $template->event = null; // Falls kein Event gefunden wurde
            $template->proposal = [];
            $template->articles = [];
        }

        return $template->getResponse();
    }
}
