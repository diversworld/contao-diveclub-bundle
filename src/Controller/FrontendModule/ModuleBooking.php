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
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FormCheckbox;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentSubTypeModel;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentTypeModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;


#[AsFrontendModule(ModuleBooking::TYPE, category: 'dc_modules', template: 'mod_dc_booking')]
class ModuleBooking extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

    protected ?PageModel $page;

    private DcaTemplateHelper $helper;
    private RequestStack $requestStack; // Dependency Injection für RequestStack
    private Connection $db;
    public function __construct(DcaTemplateHelper $helper, RequestStack $requestStack, Connection $db)
    {
        $this->helper = $helper;
        $this->requestStack = $requestStack;
        $this->db = $db;
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
        // Übergabe der Default-Werte an das Template
        $template->form = [
            'messages' => 'Hier könnte Ihre Nachricht stehen.',
        ];

        $sizes = $this->helper->getSizes();
        $manufacturers = $this->helper->getManufacturers();
        $types = DcEquipmentTypeModel::findAll(); // Alle Typ-Modelle laden
        $equipmentSubTypes = $this->helper->getTemplateOptions('subTypesFile'); // Hole SubType-Optione

        if ($request->isMethod(Request::METHOD_POST)) {
            if (null !== ($redirectPage = PageModel::findById($model->jumpTo))) {
                throw new RedirectResponseException($redirectPage->getAbsoluteUrl());
            }
        }

        // Prüfen, ob ein POST-Request vorliegt
        if ($request->isMethod('POST') && $request->request->get('FORM_SUBMIT') === 'reservationItems_submit')
        {
            // Alle Formulardaten abrufen und Assets filtern
            $selectedAssets = $request->request->all('selectedAssets');
            $selectedAssets = array_filter($selectedAssets, fn($value) => !empty($value));

            if (empty($selectedAssets)) {
                throw new \RuntimeException('Bitte mindestens ein Asset auswählen.');
            }

            // Benutzer-ID und Kategorie abrufen
            $userId = (int)$request->request->get('userId');
            $itemCategory = $request->request->get('category');
            $assetType = $request->request->get('assetType') ?? null;

            // Generiere Titel anhand der userId
            $reservationTitle = $this->generateReservationTitle($userId);

            // Prüfen, ob eine bestehende Reservierung mit demselben Titel vorhanden ist
            $reservation = DcReservationModel::findOneBy(['title=?'], [$reservationTitle]);


            if (null === $reservation) {
                // Nur speichern, wenn der Parent-Datensatz noch nicht existiert

                // Neues Eltern-Datensatz (Reservation) erstellen
                $reservation = new DcReservationModel();
                $reservation->title = $reservationTitle;            // Titel generieren
                $reservation->alias = 'id-' . $reservationTitle;    // alias generieren
                $reservation->tstamp = time();
                $reservation->member_id = $userId;
                $reservation->asset_type = $assetType;
                $reservation->reserved_at = time();
                $reservation->reservation_status = 'reserved';
                $reservation->published = 1;

                // Eltern-Datensatz in der Datenbank speichern
                $reservation->save();
            }

            // ID des gespeicherten Eltern-Datensatzes abrufen
            $reservationId = $reservation->id;

            if (!$reservationId) {
                throw new \RuntimeException('Die Reservierung konnte nicht gespeichert werden.');
            }

            // Kind-Datensätze (Reservation Items) anlegen
            foreach ($selectedAssets as $assetId) {
                // Überprüfen, ob Kind-Datensatz bereits existiert
                $existingItem = DcReservationItemsModel::findOneBy([
                    'pid=? AND id=?',
                ], [
                    $reservationId,
                    (int)$assetId,
                ]);
                if (null === $existingItem) {
                    $reservationItem = new DcReservationItemsModel(); // Hinweis: DcReservationItemsModel anpassen, falls Modell nicht existiert
                    $reservationItem->pid = $reservationId; // Parent-ID setzen
                    $reservationItem->tstamp = time();
                    $reservationItem->item_id = (int)$assetId; // ID des ausgewählten Assets
                    $reservationItem->item_type = $itemCategory;
                    $reservationItem->reserved_at = time();
                    $reservationItem->created_at = time();
                    $reservationItem->updated_at = time();
                    $reservationItem->reservation_status = 'reserved';
                    $reservationItem->published = 1;

                    // Spezielle Behandlung für "tl_dc_equipment_types"
                    if ($itemCategory === 'tl_dc_equipment_types') {
                        // Typ und Subtyp aus der Tabelle `tl_dc_equipment_types` abrufen
                        $query = $this->db->prepare('
                            SELECT title, subType, types
                            FROM tl_dc_equipment_types
                            WHERE id = ?
                        ');
                        $result = $query->executeQuery([(int) $assetId])->fetchAssociative();

                        if ($result) {
                            // Typ und Subtyp in den Kinddatensatz eintragen
                            $reservationItem->types = $result['title'];
                            $reservationItem->sub_type = $result['subType'];
                        } else {
                            // Fehler werfen, wenn kein Parent-Datensatz gefunden wurde
                            throw new \RuntimeException("Typ und Subtyp für Asset-ID {$assetId} konnten nicht abgerufen werden.");
                        }
                    }

                    // Kind-Datensatz speichern
                    $reservationItem->save();
                }
                // Asset-Status in der jeweiligen Tabelle aktualisieren
                switch ($itemCategory) {
                    case 'tl_dc_tanks':
                        //$connection->update(
                        $this->db->update(
                            'tl_dc_tanks',
                            ['status' => 'reserved'], // Zu aktualisierende Spalten
                            ['id' => (int)$assetId]  // Bedingung
                        );
                        break;

                    case 'tl_dc_regulators':
                        //$connection->update(
                        $this->db->update(
                            'tl_dc_regulators',
                            ['status' => 'reserved'], // Zu aktualisierende Spalten
                            ['id' => (int)$assetId]  // Bedingung
                        );
                        break;

                    case 'tl_dc_equipment_types':
                        //$connection->update(
                        $this->db->update(
                            'tl_dc_equipment_subtypes',
                            ['status' => 'reserved'], // Zu aktualisierende Spalten
                            ['id' => (int)$assetId]  // Bedingung
                        );
                        break;

                    default:
                        throw new \RuntimeException("Kategorie '{$itemCategory}' wird nicht unterstützt.");
                }
            }

            // Optional: Weiterleitung oder Erfolgsmeldung
            if (null !== ($redirectPage = PageModel::findById($model->jumpTo))) {
                throw new RedirectResponseException($redirectPage->getAbsoluteUrl());
            }
        }

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

        // Anwenden der Transformationen
        $updatedAssets = [];

        switch ($category) {
            case 'tl_dc_tanks':
                $assets = $this->getAvailableAssets($category);
                // Verarbeitung für Tanks
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'id'            => $asset['id'] ?? 'N/A',
                        'title'         => $asset['title'] ?? 'N/A', // Standardwert, falls 'title' fehlt
                        'manufacturer'  => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size'          => $asset['size']."L" ?? 'N/A',
                        'category'      => $category,
                        'o2clean'       => $asset['o2clean'] ?? 'N/A',
                        'owner'         => $asset['owner'] ?? 'Unknown',
                        'lastCheckDate' => $asset['lastCheckDate']
                            ? date($dateFormat, (int) $asset['lastCheckDate'])
                            : 'N/A',
                        'nextCheckDate' => $asset['nextCheckDate']
                            ? date($dateFormat, (int) $asset['nextCheckDate'])
                            : 'N/A',
                        'status'        => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            case 'tl_dc_regulators':
                $assets = $this->getAvailableAssets($category);
                // Verarbeitung für Regulators
                foreach ($assets as $asset) {
                    $regModel1st = $this->helper->getRegModels1st((int) $asset['manufacturer']);
                    $regModel2nd = $this->helper->getRegModels2nd((int) $asset['manufacturer']);

                    $updatedAssets[] = [
                        'id'                    => $asset['id'] ?? 'N/A',
                        'title'                 => $asset['title'] ?? 'N/A', // Standardwert setzen
                        'manufacturer'          => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'category'              => $category,
                        'serialNumber1st'       => $asset['serialNumber1st'] ?? 'Unknown',
                        'regModel1st'           => $regModel1st[$asset['regModel1st']] ?? 'Unknown',
                        'serialNumber2ndPri'    => $asset['serialNumber2ndPri'] ?? 'Unknown',
                        'regModel2ndPri'        => $regModel2nd[$asset['regModel2ndPri']] ?? 'Unknown',
                        'serialNumber2ndSec'    => $asset['serialNumber2ndSec'] ?? 'Unknown',
                        'regModel2ndSec'        => $regModel2nd[$asset['regModel2ndSec']] ?? 'Unknown',
                        'status'                => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            case 'tl_dc_equipment_types':
                $assets = $this->getAvailableAssets($category);

                // Verarbeitung für Equipment Types
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'id'            => $asset['id'],
                        'pid'           => $asset['pid'],
                        'typeId'        => $equipmentTypesMapping[$asset['pid']] ?? 'N/A', // Hier wird die Type-ID hinzugefügt
                        'type'          => $equipmentTypes[$equipmentTypesMapping[$asset['pid']]] ?? $asset['pid'],
                        'category'      => $category,
                        'title'         => $asset['title'] ?? 'N/A', // Mapping für Titel
                        'manufacturer'  => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size'          => $sizes[$asset['size']] ?? $asset['size'],
                        'buyDate'       => $asset['buyDate']
                            ? date($dateFormat, (int) $asset['buyDate'])
                            : 'N/A',
                        'model'         => $asset['model'] ?? 'N/A',
                        'color'         => $asset['color'] ?? 'N/A',
                        'serialNumber'  => $asset['serialNumber'] ?? 'N/A',
                        'status'        => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'Unknown',
                    ];
                }
                break;

            default:
                // Fallback: Falls keine Kategorie zutrifft, keine Verarbeitung
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'id' => $asset['id'] ?? 'N/A',
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
                //$groupedAssets[$asset['pid']][] = $asset;
                // Gruppierung nach Namen des Typs statt ID
                $groupName = $asset['type'] ?? 'Unbekannter Typ'; // Typ-Bezeichnung verwenden
                $groupedAssets[$groupName][] = $asset;
            }
            $template->groupedAssets = $groupedAssets;
        } else {
            $template->groupedAssets = $assets; // Keine Gruppierung für andere Kategorien
        }

        // Aktuell eingeloggter Benutzer
        $hasFrontendUser = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
        $userId = null;

        if ($hasFrontendUser) {
            $user = $this->getUser(); // Benutzer-ID aus der Eigenschaft abrufen
            $userId = $user->id;
        }

        // Benutzerinformation ans Template übergeben
        $template->currentUser = [
            'userId'       => $userId,
            'userFullName' => $user->firstname .' '. $user->lastname ?? 'Gast', // Beispiel: Nachname optional
        ];
        // Widgets generieren
        $reservationCheckboxes = $this->generateReservationCheckboxes($assets);

        // Übergabe ans Template
        $template->reservationCheckboxes = $reservationCheckboxes;

        // Weitergabe an Twig
        $template->assets = $assets;
        $template->action = $request->getUri();

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
                $query = "SELECT id, title, serialNumber, manufacturer, bazNumber, size, o2clean, owner, checkId, lastCheckDate, nextCheckDate, status FROM tl_dc_tanks WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_regulators':
                $query = "SELECT id, title, manufacturer, serialNumber1st, regModel1st, serialNumber2ndPri, regModel2ndPri, serialNumber2ndSec, regModel2ndSec, status FROM tl_dc_regulators WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_equipment_types':
                $query = "SELECT id, pid, title, status, manufacturer, model, color, size, serialNumber, buyDate, status FROM tl_dc_equipment_subtypes WHERE status = 'available' ORDER BY pid";
                $params = [];

                break;
            default:
                return [];
        }

        // Ergebnisse abrufen und zurückgeben
        return $connection->fetchAllAssociative($query, $params);
    }

    protected function generateReservationCheckboxes(array $assets): array
    {
        $checkboxes = [];

        foreach ($assets as $asset) {
            // Erzeuge eine Checkbox für jedes Asset
            $widget = new FormCheckbox([
                'inputType' => 'checkbox',
                'id'        => 'reserved_' . $asset['id'],  // Eindeutige ID
                'name'      => 'selectedAssets[]',         // Array-Name für Mehrfachauswahl
                'class'     => 'tl_checkbox',                 // CSS-Klasse
                // Wichtig: Optionen setzen
                'options'   => [
                    [
                        'value' => $asset['id'],
                        'label' => $GLOBALS['TL_LANG']['MSC']['reservationCheckbox'],
                    ]
                ],
                'checked'   => false,
            ]);

            // Widget als HTML zurückgeben
            $checkboxes[$asset['id']] = $widget->parse();
        }

        return $checkboxes;
    }

    protected function generateReservationTitle(int $userId): string
    {
        // MemberID formatieren
        $formattedMemberId = str_pad((string)$userId, 3, '0', STR_PAD_LEFT);
        // Datum hinzufügen
        $currentDate = date('Ymd');
        return $currentDate . $formattedMemberId;
    }
}
