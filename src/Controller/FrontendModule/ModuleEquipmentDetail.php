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
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(ModuleEquipmentDetail::TYPE, category: 'dc_modules', template: 'mod_dc_equipment_listing')]
class ModuleEquipmentDetail extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_equipment_listing';

    protected ?PageModel $page;

    private DcaTemplateHelper $helper;

    public function __construct(DcaTemplateHelper $helper)
    {
        $this->helper = $helper;
    }

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

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
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
