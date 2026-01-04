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
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Doctrine\DBAL\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(TanksDetailController::TYPE, category: 'dc_manager', template: 'mod_dc_tanks_listing')]
class TanksDetailController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_tanks_listing';

    protected ?PageModel $page;

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        /** @var Result $eventStmt */
        $tanks = DcTanksModel::findAll();

        // Prüfen, ob ein Event gefunden wurde
        if ($tanks !== false) {
            // Daten vorbereiten und ans Template übergeben
            $template->tanks = $tanks ?: []; // Falls keine Tanks gefunden wurden
        } else {
            $template->tanks = [];
        }

        return $template->getResponse();
    }
}
