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
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(EquipmentDetailController::TYPE, category: 'dc_manager', template: 'frontend_module/mod_dc_equipment_listing')]
class EquipmentDetailController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_equipment_listing';

    protected ?PageModel $page;

    private DcaTemplateHelper $helper;

    public function __construct(DcaTemplateHelper $helper)
    {
        $this->helper = $helper;
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';

        $equipmentTypes = $this->helper->getEquipmentTypes();
        $types = DcEquipmentModel::findAll(); // Alle Typ-Modelle laden

        $data = []; // Datenstruktur vorbereiten

        if ($types) {
            foreach ($types as $type) {
                // Haupttyp speichern (Subtypen werden ignoriert)
                $data[] = [
                    'types' => [
                        'id' => $type->id,
                        'title' => $type->title,
                        'type' => $equipmentTypes[$type->title] ?? $type->title,
                    ],
                ];
            }
        }

        $template->data = $data; // Daten dem Template übergeben
        return $template->getResponse();
    }
}
