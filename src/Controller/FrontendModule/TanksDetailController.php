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
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(TanksDetailController::TYPE, category: 'dc_manager', template: 'frontend_module/mod_dc_tanks_listing')]
class TanksDetailController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_tanks_listing';

    protected ?PageModel $page;

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

        $tanks = DcTanksModel::findAll();

        // Daten vorbereiten und ans Template übergeben
        $tankList = [];
        if ($tanks) {
            foreach ($tanks as $tank) {
                $tankList[] = $tank->row();
            }
        }
        $template->tanks = $tankList;
        $template->items = $tankList;

        return $template->getResponse();
    }
}
