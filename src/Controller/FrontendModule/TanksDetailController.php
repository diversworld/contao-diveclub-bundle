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
use Twig\Environment as Twig;

#[AsFrontendModule(TanksDetailController::TYPE, category: 'dc_manager', template: 'mod_dc_tanks_listing')]
class TanksDetailController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_tanks_listing';

    protected ?PageModel $page;

    public function __construct(
        private readonly Twig $twig,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $templateData = [
            'element_html_id' => 'mod_' . $model->id,
            'element_css_classes' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'class' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'cssID' => $model->cssID[0] ?? '',
            'type' => $model->type,
        ];

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $templateData['headline'] = [
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
        $templateData['tanks'] = $tankList;
        $templateData['items'] = $tankList;

        return new Response($this->twig->render(
            '@DiversworldContaoDiveclub/frontend_module/mod_dc_tanks_listing.html.twig',
            $templateData
        ));
    }
}
