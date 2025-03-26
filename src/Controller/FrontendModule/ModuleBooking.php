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

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentSubTypeModel;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentTypeModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(ModuleBooking::TYPE, category: 'dc_modules', template: 'mod_dc_booking')]
class ModuleBooking extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

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
        $sizes = $this->helper->getSizes();
        $manufacturers = $this->helper->getManufacturers();
        $types = DcEquipmentTypeModel::findAll(); // Alle Typ-Modelle laden
        $equipmentSubTypes = $this->helper->getTemplateOptions('subTypesFile'); // Hole SubType-Optione

        // Datum global abrufen
        $dateFormat = Config::get('dateFormat');

        $data = []; // Datenstruktur vorbereiten

        // Aktuell eingeloggter Benutzer
        $user = $this->container->get('security.helper')->getUser();

        $template->typeSelection = []; // Standardwert, falls keine Typen vorhanden sind

        if (null !== $types) {
            foreach ($types as $type) {
                $template->typeSelection[] = [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            }
        }

        if ($types) {
            foreach ($types as $type) {
                // Subtypen abrufen, die diesem Typ zugeordnet sind
                $subTypesCollection = DcEquipmentSubTypeModel::findBy('pid', $type->id);
                $subTypes = [];

                if ($subTypesCollection) {
                    foreach ($subTypesCollection as $subType) {
                        $subTypes[] = [
                            'manufacturer'  => $manufacturers[$subType->manufacturer] ?? $subType->manufacturer,
                            'model'         => $subType->model,
                            'color'         => $subType->color,
                            'size'          => $sizes[$subType->size] ?? $subType->size,
                            'title'         => $subType->title,
                            'buyDate'       => $subType->buyDate ? date('d.m.Y', (int) $subType->buyDate) : 'N/A',
                        ];
                    }
                }
                // Haupttyp mit zugehörigen Subtypen speichern
                $data[] = [
                    'types' => [
                        'id' => $type->id,
                        'title' => $type->title,
                        'subType' => $equipmentSubTypes[$type->types][$type->subType],
                    ],
                    'subTypes' => $subTypes,
                ];
            }
        }

        $template->currentUser = $user;
        $template->sizes = $sizes;
        $template->manufacturers = $manufacturers;
        $template->types = $types;
        $template->subTypes = $equipmentSubTypes;

        System::loadLanguageFile('tl_dc_reservation_items');

        // Kategorien-Auswahl (Dropdown)
        $categories = [
            'tl_dc_tanks' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_tanks'],// ?? 'Tanks', // Fallback zu "Tanks"
            'tl_dc_regulators' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_regulators'],// ?? 'Atemregler', // Fallback zu "Regulatoren"
            'tl_dc_equipment_types' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_equipment_types']// ?? 'Equipment Typen' // Fallback
        ];
        $template->categories = $categories;

        // Kategorie und Subtyp auswählen
        $category = $request->get('category');
        $template->selectedCategory = $category;
        $template->data = $data;

        // Für `tl_dc_equipment_types`: Dynamische Subtypen laden
        if ('tl_dc_equipment_types' === $category) {
            $subTypes = $this->helper->getSubTypes(); // Hilfsmethode für die Subtypen
            $template->subTypes = $subTypes;
        }

        // Verfügbare Assets laden
        // Fetch mappings from the Helpers
        $manufacturers = $this->helper->getManufacturers();
        $sizes = $this->helper->getSizes();
        $equipmentTypes = $this->helper->getEquipmentTypes();
        // Fetch the pid-to-title mapping from the `tl_dc_equipment_types` table directly
        $equipmentTypesMapping = $this->getEquipmentTypeTitles(); // Custom method, explained below

        $assets = []; // Standard-Wert setzen

        if ($category) {
            $assets = $this->getAvailableAssets($category);
        }

        // Anwenden der Transformationen
        $updatedAssets = [];

        switch ($category) {
            case 'tl_dc_tanks':
                // Verarbeitung für Tanks
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'title' => $asset['title'] ?? 'N/A', // Standardwert, falls 'title' fehlt
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size' => $asset['size']."L" ?? 'N/A',
                        'o2clean' => $asset['o2clean'] ?? 'N/A',
                        'owner' => $asset['owner'] ?? 'Unknown',
                        'lastCheckDate' => $asset['lastCheckDate']
                            ? date($dateFormat, (int) $asset['lastCheckDate'])
                            : 'N/A',
                        'nextCheckDate' => $asset['nextCheckDate']
                            ? date($dateFormat, (int) $asset['nextCheckDate'])
                            : 'N/A',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            case 'tl_dc_regulators':
                // Verarbeitung für Regulators
                foreach ($assets as $asset) {
                    $regModel1st = $this->helper->getRegModels1st((int) $asset['manufacturer']);
                    $regModel2nd = $this->helper->getRegModels2nd((int) $asset['manufacturer']);

                    $updatedAssets[] = [
                        'title' => $asset['title'] ?? 'N/A', // Standardwert setzen
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'serialNumber1st' => $asset['serialNumber1st'] ?? 'Unknown',
                        'regModel1st' => $regModel1st[$asset['regModel1st']] ?? 'Unknown',
                        'serialNumber2ndPri' => $asset['serialNumber2ndPri'] ?? 'Unknown',
                        'regModel2ndPri' => $regModel2nd[$asset['regModel2ndPri']] ?? 'Unknown',
                        'serialNumber2ndSec' => $asset['serialNumber2ndSec'] ?? 'Unknown',
                        'regModel2ndSec' => $regModel2nd[$asset['regModel2ndSec']] ?? 'Unknown',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            case 'tl_dc_equipment_types':
                // Verarbeitung für Equipment Types
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'pid' => $equipmentTypes[$equipmentTypesMapping[$asset['pid']]] ?? $asset['pid'],
                        'title' => $asset['title'] ?? 'N/A', // Mapping für Titel
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size' => $sizes[$asset['size']] ?? $asset['size'],
                        'buyDate' => $asset['buyDate']
                            ? date($dateFormat, (int) $asset['buyDate'])
                            : 'N/A',
                        'model' => $asset['model'] ?? 'N/A',
                        'color' => $asset['color'] ?? 'N/A',
                        'serialNumber' => $asset['serialNumber'] ?? 'N/A',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            default:
                // Fallback: Falls keine Kategorie zutrifft, keine Verarbeitung
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'title' => $asset['title'] ?? 'N/A',
                        'manufacturer' => $asset['manufacturer'] ?? 'N/A',
                        'size' => $asset['size'] ?? 'N/A',
                    ];
                }
                break;
        }

        // Zuweisung der transformierten Assets
        $assets = $updatedAssets;

        // Optional: Gruppieren nach `pid` nur für Equipment Types
        if ('tl_dc_equipment_types' === $category) {
            $groupedAssets = [];
            foreach ($assets as $asset) {
                $groupedAssets[$asset['pid']][] = $asset;
            }
            $template->groupedAssets = $groupedAssets;
        } else {
            $template->groupedAssets = $assets; // Keine Gruppierung für andere Kategorien
        }

        // Weitergabe an Twig
        $template->assets = $assets;

        // Frontend-Template zurückgeben
        return $template->getResponse();
    }

    /**
     * Get the mapping of `pid` to `title` in the `tl_dc_equipment_types` table.
     */
    private function getEquipmentTypeTitles(): array
    {
        $connection = $this->container->get('database_connection');

        // Fetch the `pid` and `title` from the equipment types table
        $results = $connection->fetchAllAssociative(
            'SELECT id AS pid, title FROM tl_dc_equipment_types'
        );

        // Transform the results into a pid-to-title mapping
        $mapping = [];
        foreach ($results as $row) {
            $mapping[$row['pid']] = $row['title'];
        }

        return $mapping;
    }

    private function getAvailableAssets(string $category): array
    {
        // Datenbankverbindung abrufen
        $connection = $this->container->get('database_connection');

        // Unterschiedliche Queries für verschiedene Kategorien
        switch ($category) {
            case 'tl_dc_tanks':
                $query = "SELECT title, serialNumber, manufacturer, bazNumber, size, o2clean, owner, checkId, lastCheckDate, nextCheckDate, status FROM tl_dc_tanks WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_regulators':
                $query = "SELECT title, manufacturer, serialNumber1st, regModel1st, serialNumber2ndPri, regModel2ndPri, serialNumber2ndSec, regModel2ndSec, status FROM tl_dc_regulators WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_equipment_types':
                $query = "SELECT pid, title, status, manufacturer, model, color, size, serialNumber, buyDate, status FROM tl_dc_equipment_subtypes WHERE status = 'available' ORDER BY pid";
                $params = [];

                break;
            default:
                return [];
        }

        // Ergebnisse abrufen und zurückgeben
        return $connection->fetchAllAssociative($query, $params);
    }
}
