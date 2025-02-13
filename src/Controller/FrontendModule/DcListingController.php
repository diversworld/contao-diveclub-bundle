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

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCalendarEventsModel;

#[AsFrontendModule(DcListingController::TYPE, category: 'dc_modules', template: 'mod_dc_listing')]
class DcListingController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_listing';

    protected ?PageModel $page;

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        $scopeMatcher = $this->container->get('contao.routing.scope_matcher');

        if ($this->page instanceof PageModel && $scopeMatcher->isFrontendRequest($request)) {
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Lazyload services.
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['database_connection'] = Connection::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $eventAlias = Input::get('auto_item');

        // Get the database connection
        $db = $this->container->get('database_connection');

        /** @var Result $eventStmt */
        $event = DcCalendarEventsModel::findByAlias($eventAlias);

        $proposal = DcCheckProposalModel::findById($event->addVendorInfo);

        $articles = DcCheckArticlesModel::findBy('pid', $proposal->id);

        $this->logger->info('articles: '.print_r($articles, true));

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
