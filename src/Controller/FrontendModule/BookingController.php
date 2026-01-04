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
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Email;
use Contao\FormCheckbox;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Diversworld\ContaoDiveclubBundle\Session\Attribute\ArrayAttributeBag;
use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;


#[AsFrontendModule(BookingController::TYPE, category: 'dc_manager', template: 'mod_dc_booking')]
class BookingController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

    protected ?PageModel $page;
    private DcaTemplateHelper $helper;
    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private Connection $db;

    public function __construct(DcaTemplateHelper $helper, Connection $db, RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->helper = $helper;
        $this->db = $db;
        $this->framework = $framework;
        $this->requestStack = $requestStack;
    }

    /**
     * Haupt-Methode, die die Logik des Moduls steuert.
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        System::loadLanguageFile('tl_dc_reservation_items');

        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $sessionData = $this->getSessionData();
        $equipmentTypes = $this->helper->getEquipmentTypes(); // Typen/Subtypen laden
        $template->equipmentTypes = $equipmentTypes;

        $category = $request->get('category');

        // NEU: Gesamtpreis berechnen und ans Template übergeben
        $totalPrice = $this->calculateTotalPrice($sessionData);
        $template->totalPrice = $totalPrice;

        // NEW: Vorgemerkte Reservierungen abrufen
        $template->storedAssets = $this->loadStoredAssets($sessionData);

        // Member Liste an das Template übergeben
        $memberResult = $this->db->prepare('SELECT id, firstname, lastname FROM tl_member ORDER BY lastname, firstname')->executeQuery();
        // Ergebnisse in ein assoziatives Array umwandeln
        $template->memberList = $memberResult->fetchAllAssociative();

        // Session-Daten und ausgewählte Kategorie behandeln
        $totalPrice = $this->calculateTotalPrice($sessionData);
        $template->totalPrice = $totalPrice;
        $template->totalRentalFee = $totalPrice; // Konsistenz für das Template
        $template->selectedCategory = $category;
        $template->currentUser = $this->getCurrentUser();

        // Verfügbarkeit und Kategorienauswahl
        if ($category) {
            $availableAssets = $this->updateAssets($category);

            $template->reservationCheckboxes = $this->generateReservationCheckboxes($availableAssets);
            $availableAssets = $this->combineAssetsWithSession($category, $availableAssets);

            // Zugehörige Assets finden und gruppieren
            if ($category === 'tl_dc_equipment') {
                //$availableAssets = $this->updateAssets($category); // Assets für die Kategorie abrufen
                $groupedAssets = $this->groupAssetsByType($availableAssets, $equipmentTypes); // Assets nach Typ gruppieren
                $template->groupedAssets = $groupedAssets; // Gruppierte Assets ans Template übergeben
            } else {
                $template->assets = $availableAssets; // Leeres Array, falls die Kategorie nicht zutrifft
            }
        }

        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $selectedMember = $bag->get('selectedMember') ?? null; // Hole den von der Session gespeicherten Benutzer

        if ($selectedMember === null) {
            $selectedMember = $this->getCurrentUser()['userId'] ?? null;
            $bag->set('selectedMember', $selectedMember);
        }

        $template->selectedMember = $selectedMember; // Ins Template laden

        // Verarbeitung von POST-Daten
        if ($request->isMethod('POST')) {
            $response = $this->handlePostRequest($request, $template, $sessionData, (int)$selectedMember);
            if ($response instanceof Response) {
                // Wenn handlePostRequest eine RedirectResponse oder ähnliches zurückgibt, wird dies ausgeführt
                return $response;
            }
        }

        $result = $this->db->fetchAssociative('SELECT rentalConditions FROM tl_dc_config LIMIT 1');
        $template->rentalConditions = $result['rentalConditions'] ?? null;

        // Kategorienauswahl und weiterleiten
        $template->categories = $this->getCategories();
        $template->action = $request->getUri();

        // Vorgemerkte Assets immer laden
        $storedAssets = $this->loadStoredAssets($this->getSessionData());

        $template->storedAssets = $storedAssets;
        $template->totalPrice = $this->calculateTotalPrice($this->getSessionData());
        $template->totalRentalFee = $template->totalPrice;

        return $template->getResponse();
    }

    /**
     * Session-Daten abrufen.
     */
    private function getSessionData(): array
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        return $bag->get('reservation_items', []);
    }

    /**
     * Berechnet den Gesamtpreis der reservierten Assets aus der Session.
     */
    private function calculateTotalPrice(array $sessionData): float
    {
        $totalPrice = 0.0;

        foreach ($sessionData as $entry) {
            $category = $entry['category'] ?? null;
            $selectedAssets = $entry['selectedAssets'] ?? [];

            if (!$category || empty($selectedAssets)) {
                continue;
            }

            // Preise der Assets summieren
            foreach ($selectedAssets as $asset) {
                // Asset-Details abrufen
                $assetDetails = $this->getAssetDetails($category, (int)$asset['assetId']);

                if ($assetDetails) {
                    // Mietgebühr extrahieren
                    preg_match('/([0-9]+(\.[0-9]{2})?) €/i', $assetDetails, $matches);

                    if (!empty($matches[1])) {
                        $totalPrice += (float)$matches[1];
                    }
                }
            }
        }
        return $totalPrice;
    }

    private function getAssetDetails(string $category, int $assetId): ?string
    {
        switch ($category) {
            case 'tl_dc_tanks':
                $asset = DcTanksModel::findByPk($assetId);

                if (!$asset) {
                    return null;
                }
                $sizeText = $asset->size . 'L';
                return sprintf(
                    'Tank: %s (Größe: %s), %.2f €',
                    $asset->title ?? 'Unbekannt',
                    $sizeText,
                    (float)$asset->rentalFee
                );

            case 'tl_dc_regulators':
                $asset = DcRegulatorsModel::findByPk($assetId);
                if (!$asset) {
                    return null;
                }
                $helper = new DcaTemplateHelper();
                $manufacturerMapping = $helper->getManufacturers();
                $manufacturerText = $manufacturerMapping[$asset->manufacturer] ?? 'Unbekannter Hersteller';

                $regModel1stMapping = $helper->getRegModels1st((int)$asset->manufacturer);
                $regModel1stText = $regModel1stMapping[$asset->regModel1st] ?? 'N/A';

                $regModel2ndMapping = $helper->getRegModels2nd((int)$asset->manufacturer);
                $regModel2ndPriText = $regModel2ndMapping[$asset->regModel2ndPri] ?? 'N/A';
                $regModel2ndSecText = $regModel2ndMapping[$asset->regModel2ndSec] ?? 'N/A';

                return sprintf(
                    'Regulator: %s (Hersteller: %s, 1st Stage: %s, 2nd Stage (Pri): %s, 2nd Stage (Sec): %s), %.2f €',
                    $asset->title ?? 'Unbekannt',
                    $manufacturerText,
                    $regModel1stText,
                    $regModel2ndPriText,
                    $regModel2ndSecText,
                    (float)$asset->rentalFee
                );

            case 'tl_dc_equipment':
                // Prüfung, ob das Objekt bereits existiert
                $asset = DcEquipmentModel::findByPk($assetId);

                if (!$asset) {
                    return null;
                }

                $helper = new DcaTemplateHelper();
                $sizeMapping = $helper->getSizes();
                $types = $helper->getEquipmentTypes();
                $subTypes = $helper->getSubTypes($asset->type);
                $sizeText = $sizeMapping[$asset->size] ?? 'Unbekannte Größe';

                $info = sprintf(
                    'Ausrüstung: %s %s - %s (Modell: %s, Größe: %s, Farbe: %s), %.2f €',
                    $types[$asset->type]['name'] ?? 'Unbekannt',
                    $subTypes[$asset->subType] ?? 'Unbekannt',
                    $asset->title ?? 'Unbekannt',
                    $asset->model ?? 'Kein Modell angegeben',
                    $sizeText,
                    $asset->color ?? 'Keine Farbe angegeben',
                    (float)$asset->rentalFee
                );
                return $info;

            default:
                return null;
        }
    }

    /**
     * Lädt die vorgemerkten Assets aus den Session-Daten.
     */
    private function loadStoredAssets(array $sessionData): array
    {
        $storedAssets = [];

        foreach ($sessionData as $entry) {
            $category = $entry['category'] ?? null;
            $selectedAssets = $entry['selectedAssets'] ?? [];
            if (!$category || empty($selectedAssets)) {
                continue;
            }

            foreach ($selectedAssets as $asset) {
                $assetDetails = $this->getAssetDetails($category, (int)$asset['assetId']);
                if ($assetDetails) {
                    $storedAssets[] = $assetDetails;
                }
            }
        }
        return $storedAssets;
    }

    /**
     * Benutzerinformationen abrufen.
     */
    private function getCurrentUser(): array
    {
        $hasFrontendUser = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();

        if ($hasFrontendUser) {
            $user = $this->getUser();
            return [
                'userId' => $user->id,
                'userFullName' => trim($user->firstname . ' ' . $user->lastname) ?: 'Gast',
            ];
        }

        return ['userId' => null, 'userFullName' => 'Gast'];
    }

    /**
     * @param string $category
     * @return array
     */
    function updateAssets(string $category): array
    {
        // Verfügbare Assets laden
        // Fetch mappings from the Helpers
        $manufacturers = $this->helper->getManufacturers();
        $sizes = $this->helper->getSizes();
        $equipmentTypes = $this->helper->getEquipmentTypes();

        // Datum global abrufen
        $dateFormat = Config::get('dateFormat');

        // Fetch the id-to-title mapping from the `tl_dc_equipment` table directly
        //$equipmentTypesMapping = $this->getEquipmentTypeTitles(); // Custom method, explained below
        $updatedAssets = [];
        $assets = $this->getAvailableAssets($category);

        switch ($category) {
            case 'tl_dc_tanks':
                // Verarbeitung für Tanks
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'id' => $asset['id'] ?? 'N/A',
                        'title' => $asset['title'] ?? 'N/A', // Standardwert, falls 'title' fehlt
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size' => $asset['size'] . "L" ?? 'N/A',
                        'category' => $category,
                        'o2clean' => $asset['o2clean'] ?? 'N/A',
                        'owner' => $asset['owner'] ?? '-',
                        'lastCheckDate' => $asset['lastCheckDate']
                            ? date($dateFormat, (int)$asset['lastCheckDate'])
                            : 'N/A',
                        'nextCheckDate' => $asset['nextCheckDate']
                            ? date($dateFormat, (int)$asset['nextCheckDate'])
                            : 'N/A',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? '-',
                        'price' => $asset['rentalFee'] ?? 'N/A',
                    ];
                }
                break;

            case 'tl_dc_regulators':
                // Verarbeitung für Regulators
                foreach ($assets as $asset) {
                    $regModel1st = $this->helper->getRegModels1st((int)$asset['manufacturer']);
                    $regModel2nd = $this->helper->getRegModels2nd((int)$asset['manufacturer']);

                    $updatedAssets[] = [
                        'id' => $asset['id'] ?? 'N/A',
                        'title' => $asset['title'] ?? 'N/A', // Standardwert setzen
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'category' => $category,
                        'serialNumber1st' => $asset['serialNumber1st'] ?? ' ',
                        'regModel1st' => $regModel1st[$asset['regModel1st']] ?? ' ',
                        'serialNumber2ndPri' => $asset['serialNumber2ndPri'] ?? ' ',
                        'regModel2ndPri' => $regModel2nd[$asset['regModel2ndPri']] ?? ' ',
                        'serialNumber2ndSec' => $asset['serialNumber2ndSec'] ?? ' ',
                        'regModel2ndSec' => $regModel2nd[$asset['regModel2ndSec']] ?? ' ',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'N/A',
                        'price' => $asset['rentalFee'] ?? 'N/A',
                    ];
                }
                break;

            case 'tl_dc_equipment':
                // Verarbeitung für Equipment Types
                foreach ($assets as $asset) {
                    $equipmentSubTypes = $this->helper->getSubTypes($asset['id']);
                    $updatedAssets[] = [
                        'id' => $asset['id'],
                        'type' => $equipmentTypes[$asset['type']] ?? $asset['type'],
                        'subType' => $equipmentSubTypes[$asset['subType']] ?? $asset['subType'],
                        'typeId' => $asset['type'], // Indexwert behalten
                        'subTypeId' => $asset['subType'], // Indexwert behalten
                        'category' => $category,
                        'title' => $asset['title'] ?? 'N/A', // Mapping für Titel
                        'manufacturer' => $manufacturers[$asset['manufacturer']] ?? $asset['manufacturer'],
                        'size' => $sizes[$asset['size']] ?? $asset['size'],
                        'buyDate' => $asset['buyDate']
                            ? date($dateFormat, (int)$asset['buyDate'])
                            : 'N/A',
                        'model' => $asset['model'] ?? 'N/A',
                        'color' => $asset['color'] ?? 'N/A',
                        'serialNumber' => $asset['serialNumber'] ?? 'N/A',
                        'status' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$asset['status']] ?? 'N/A',
                        'price' => $asset['rentalFee'] ?? 'N/A',
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
        return $updatedAssets;
    }

    private function getAvailableAssets(string $category): array
    {
        $allowedCategories = ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'];
        if (!in_array($category, $allowedCategories, true)) {
            throw new InvalidArgumentException('Ungültige Kategorie: ' . htmlspecialchars($category));
        }

        // Unterschiedliche Queries für verschiedene Kategorien
        switch ($category) {
            case 'tl_dc_tanks':
                $result = DcTanksModel::findAvailable();
                return $result ? $result->fetchAll() : [];
            case 'tl_dc_regulators':
                $result = DcRegulatorsModel::findAvailable();
                return $result ? $result->fetchAll() : [];
            case 'tl_dc_equipment':
                $result = DcEquipmentModel::findAvailable() ?? [];
                return $result ? $result->fetchAll() : [];
            default:
                return [];
        }
    }

    protected function generateReservationCheckboxes(array $assets): array
    {
        $checkboxes = [];

        foreach ($assets as $asset) {
            // Erzeuge eine Checkbox für jedes Asset
            $widget = new FormCheckbox([
                'inputType' => 'checkbox',
                'id' => 'reserved_' . $asset['id'],  // Eindeutige ID
                'name' => 'selectedAssets[]',         // Array-Name für Mehrfachauswahl
                'class' => 'tl_checkbox',                 // CSS-Klasse
                // Wichtig: Optionen setzen
                'options' => [
                    [
                        'value' => $asset['id'],
                        'label' => $GLOBALS['TL_LANG']['MSC']['reservationCheckbox'],
                    ]
                ],
                'checked' => false,
            ]);

            // Widget als HTML zurückgeben
            $checkboxes[$asset['id']] = $widget->parse();
        }

        return $checkboxes;
    }

    /**
     * Kombiniert Session-Daten mit Assets der aktuell gewählten Kategorie.
     */
    private function combineAssetsWithSession(string $category, array $assets): array
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []);

        $selectedAssetIds = [];
        foreach ($sessionData as $entry) {
            // Prüfen, ob 'selectedAssets' ein Array ist
            $selectedAssets = is_array($entry['selectedAssets'] ?? null) ? $entry['selectedAssets'] : [];

            // Extrahiere nur die `assetId`-Werte aus jedem Eintrag in `selectedAssets`
            $assetIds = array_map(static function ($asset) {
                return $asset['assetId'];
            }, $selectedAssets);

            $selectedAssetIds = array_merge($selectedAssetIds, $assetIds);
        }
        $selectedAssetIds = array_unique($selectedAssetIds); // Doppelte entfernen

        // Markiere Assets als "ausgewählt", falls sie in der Session sind
        foreach ($assets as &$asset) {
            $asset['selected'] = in_array($asset['id'], $selectedAssetIds, true); // Vergleicht IDs
        }

        return $assets;
    }

    /**
     * Gruppiert Assets nach Typ.
     */
    private function groupAssetsByType(array $assets, array $equipmentTypes): array
    {
        $groupedAssets = [];
        $types = $this->helper->getEquipmentTypes();

        foreach ($assets as $asset) {
            $typeId = $asset['typeId'] ?? 'unknown';
            $subTypeId = $asset['subTypeId'] ?? 'unknown';

            // Typnamen und Subtypen aus den Equipment-Typen extrahieren
            foreach ($equipmentTypes as $typeKey => $typeData) {
                if ($typeKey == $typeId) {
                    $typeName = $typeData['name'] ?? 'unknown_type';
                    $subtypes = $typeData['subtypes'];

                    // Subtypen-Name ermitteln
                    $subTypeName = $subtypes[$subTypeId] ?? 'unknown_subtype';

                    if (!isset($groupedAssets[$typeName])) {
                        $groupedAssets[$typeName] = [];
                    }

                    if (!isset($groupedAssets[$typeName][$subTypeName])) {
                        $groupedAssets[$typeName][$subTypeName] = [];
                    }

                    // Asset zur entsprechenden Gruppe hinzufügen
                    $groupedAssets[$typeName][$subTypeName][] = $asset;
                }
            }
        }

        return $groupedAssets;
    }

    /**
     * POST-Anfrage verarbeiten.
     */
    private function handlePostRequest(Request $request, FragmentTemplate $template, array $sessionData, int $selectedMember): RedirectResponse
    {
        $formType = $request->request->get('FORM_SUBMIT', null);
        $action = $request->request->get('action', ''); // Der Wert des gedrückten Buttons
        $seite = $request->getUri();
        $urlParts = parse_url($seite);

        // 1. Speichere den Benutzer, für den reserviert werden soll
        if ($formType === 'reservation_select_member') {
            $selectedMember = $request->request->get('reservedFor');

            // Validierung: Überprüfen, ob der ausgewählte Benutzer existiert
            if ($selectedMember) {
                $member = $this->db->fetchAssociative('SELECT id FROM tl_member WHERE id = ?', [$selectedMember]);
                if ($member) {
                    $session = $this->requestStack->getSession();
                    $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);

                    // Benutzer speichern
                    $bag->set('selectedMember', $selectedMember);

                    // Sicherstelllen, dass dieser auch in den Session-Daten (reservation_items) ist
                    $sessionData = $bag->get('reservation_items', []);
                    foreach ($sessionData as &$reservation) {
                        $reservation['selectedMember'] = $selectedMember;
                    }
                    $bag->set('reservation_items', $sessionData);
                } else {
                    Message::addError('Der ausgewählte Benutzer wurde nicht gefunden.');
                }
            }

            // Zurück zur aktuellen Seite
            return new RedirectResponse($seite);
        }


        switch ($action) {
            case 'save':
                // Logik für "Speichern"
                $this->saveReservationsToDatabase();
                $this->sendReservationNotification($sessionData);
                Message::addConfirmation('Die Reservierung gespeichert und die Session-Daten wurden gelöscht.');
                $this->resetSession();

                // Seite neu laden, ohne Query-Parameter
                $cleanUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];

                return new RedirectResponse($cleanUrl);

            case 'cancel':
                // Abbrechen und Session zurücksetzen
                $this->resetSession();
                Message::addConfirmation('Die Reservierung wurde abgebrochen und die Session-Daten wurden gelöscht.');

                $cleanUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];

                return new RedirectResponse($cleanUrl); // Rückgabe des RedirectResponse-Objekts

            case 'reserve':
                // Logik für "Reservieren"
                $this->saveSessionData($request->request->all(), $template);
                Message::addConfirmation('Ausrüstung vorgemerkt.');
                return new RedirectResponse($seite);  // Zurück zum Template
        }

        $template->messages = Message::generate();

        // 3. Standardverarbeitung: Falls keine Aktion erkannt wurde
        Message::addError('Ungültige Aktion.');

        // Default-Fall, falls keine gültige Aktion erkannt wurde
        return new RedirectResponse($seite);

    }

    /**
     * Speichert Reservierungen in die Datenbank.
     */
    private function saveReservationsToDatabase(): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []);

        if (empty($sessionData)) {
            Message::addInfo('Keine Reservierungen in der Session gefunden.');
            return;
        }

        try {
            $saveMessage = $this->saveDataToDb();
            Message::addConfirmation(htmlspecialchars($saveMessage));
        } catch (Exception $e) {
            Message::addError('Fehler beim Speichern der Reservierungen: ' . $e->getMessage());
            System::getContainer()->get('monolog.logger.contao.general')->error('Reservation save error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * Save Data to Database
     */
    function saveDataToDb(): string
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []);

        if (empty($sessionData)) {
            throw new RuntimeException('Es sind keine Reservierungsdaten in der Session gespeichert.');
        }

        $logger = System::getContainer()->get('monolog.logger.contao.general');

        // Extrahiere Basis-Informationen (User, für wen reserviert wird)
        $firstEntry = reset($sessionData);
        $userId = (int)($firstEntry['userId'] ?? 0);
        $reservedFor = (int)($firstEntry['selectedMember'] ?? 0);

        // Gesamtpreis über ALLE Kategorien berechnen
        $totalFee = $this->calculateTotalPrice($sessionData);

        if ($totalFee <= 0) {
            $logger->warning('Reservation has zero total fee.');
        }

        $reservationTitle = $this->generateReservationTitle($userId);

        // Erstelle EINE Haupt-Reservierung für alle Gegenstände
        $reservation = new DcReservationModel();
        $reservation->title = $reservationTitle;
        $reservation->tstamp = time();
        $reservation->member_id = $userId;
        $reservation->reservedFor = $reservedFor;
        $reservation->asset_type = 'multiple'; // Markiere als gemischte Reservierung
        $reservation->reserved_at = (string)time();
        $reservation->reservation_status = 'reserved';
        $reservation->rentalFee = $totalFee;
        $reservation->published = '1';
        $reservation->alias = 'res-' . $reservationTitle . '-' . bin2hex(random_bytes(4));

        if (!$reservation->save()) {
            $logger->error('Failed to save DcReservationModel for title: ' . $reservationTitle);
            throw new RuntimeException('Haupt-Reservierung konnte nicht gespeichert werden (Titel: ' . $reservationTitle . ')');
        }

        $reservationId = (int)$reservation->id;
        $logger->info('Saved unified reservation header ID: ' . $reservationId . ' for member: ' . $userId);

        $sorting = 128; // Startwert für Sorting
        $itemCount = 0;

        // Loop durch alle Kategorien in der Session
        foreach ($sessionData as $entry) {
            $category = $entry['category'] ?? null;
            $selectedAssets = $entry['selectedAssets'] ?? [];

            if (empty($selectedAssets) || !$category) {
                continue;
            }

            foreach ($selectedAssets as $asset) {
                $assetId = (int)($asset['assetId'] ?? 0);
                $type = $asset['type'] ?? null;
                $subType = $asset['subType'] ?? null;

                if ($assetId === 0) {
                    $logger->warning('Asset entry missing assetId, skipping.');
                    continue;
                }

                // Speichern der Items
                try {
                    $this->db->insert('tl_dc_reservation_items', [
                        'pid' => $reservationId,
                        'tstamp' => time(),
                        'sorting' => $sorting,
                        'item_id' => $assetId,
                        'item_type' => $category,
                        'types' => (string)($type ?? ''),
                        'sub_type' => (string)($subType ?? ''),
                        'reserved_at' => (string)time(),
                        'created_at' => (string)time(),
                        'updated_at' => (string)time(),
                        'reservation_status' => 'reserved',
                        'published' => '1'
                    ]);
                    $itemCount++;
                } catch (Exception $e) {
                    $logger->error('Failed to insert reservation item: ' . $e->getMessage());
                    throw new RuntimeException('Reservierungs-Item konnte nicht gespeichert werden für Asset ID ' . $assetId);
                }

                // Status des Assets auf 'reserved' setzen
                $this->updateAssetStatus($category, $assetId);
                $sorting += 128;
            }
        }

        return sprintf('Eine Reservierung mit %d Position(en) wurde erfolgreich gespeichert.', $itemCount);
    }

    protected function generateReservationTitle(int $userId): string
    {
        // MemberID formatieren. Führende Nullen hinzufügen, um die member_id dreistellig zu machen
        $formattedMemberId = str_pad((string)$userId, 3, '0', STR_PAD_LEFT);

        // Datum im Format jjjjmmtt
        $currentDate = date('dmHi');
        $currentYear = date('Y');

        // Neues Title-Format
        return $currentYear . '-' . $formattedMemberId . '-' . $currentDate;
    }

    /**
     * Aktualisiert den Status eines Assets nach der Reservierung.
     */
    private function updateAssetStatus(string $category, int $assetId): void
    {
        $logger = System::getContainer()->get('monolog.logger.contao.general');

        try {
            switch ($category) {
                case 'tl_dc_tanks':
                case 'tl_dc_regulators':
                case 'tl_dc_equipment':
                    // Zuerst prüfen, ob das Asset existiert
                    $exists = $this->db->fetchOne("SELECT id FROM $category WHERE id = ?", [$assetId]);
                    if (!$exists) {
                        $logger->error(sprintf('Asset not found in table %s, ID %d', $category, $assetId));
                        return;
                    }

                    $affected = $this->db->update($category, ['status' => 'reserved', 'tstamp' => time()], ['id' => $assetId]);
                    if ($affected > 0) {
                        $logger->info(sprintf('Updated asset status to "reserved" for table %s, ID %d', $category, $assetId));
                    } else {
                        // Vielleicht ist der Status schon 'reserved'
                        $currentStatus = $this->db->fetchOne("SELECT status FROM $category WHERE id = ?", [$assetId]);
                        $logger->info(sprintf('No rows affected when updating asset status for table %s, ID %d. Current status: %s', $category, $assetId, $currentStatus));
                    }
                    break;
                default:
                    $logger->error('Invalid category for asset status update: ' . $category);
            }
        } catch (Exception $e) {
            $logger->error(sprintf('Error updating asset status for %s ID %d: %s', $category, $assetId, $e->getMessage()));
        }
    }

    /**
     * Sends reservation notification via email.
     */
    private function sendReservationNotification(array $sessionData): void
    {
        if (empty($sessionData)) {
            return;
        }

        $totalPrice = $this->calculateTotalPrice($sessionData);

        // Details aus den Session-Daten extrahieren
        $firstEntry = reset($sessionData);
        $userId = (int)($firstEntry['userId'] ?? 0);
        $reservationNumber = $this->generateReservationTitle($userId);
        $memberName = $this->getCurrentUser()['userFullName'] ?? 'Unbekannt';
        $selectedMemberId = $firstEntry['selectedMember'] ?? null;

        if ($selectedMemberId !== null) {
            $member = $this->db->fetchAssociative('SELECT firstname, lastname FROM tl_member WHERE id = ?', [(int)$selectedMemberId]);
            $reservedFor = $member ? trim($member['firstname'] . ' ' . $member['lastname']) : 'Unbekannt';
        } else {
            $reservedFor = 'Unbekannt';
        }

        // Reservierte Items aus Session-Daten
        $reservedItems = $this->loadStoredAssets($sessionData);
        if (empty($reservedItems)) {
            throw new RuntimeException('Es wurden keine Assets für die Benachrichtigung reserviert.');
        }

        // E-Mail senden
        $this->sendReservationEmail($reservationNumber, $memberName, $reservedFor, $reservedItems, $totalPrice);
    }

    /**
     * @param int $reservationId
     * @param string $reservationNumber
     * @param string $memberName
     * @param array $reservedItems
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    function sendReservationEmail(string $reservationNumber, string $memberName, string $reservedFor, array $reservedItems, float $totalFee): void
    {
        $configAdapter = $this->framework->getAdapter(Config::class);

        // Währungsformatierung für den Gesamtbetrag
        $formattedTotalFee = number_format($totalFee, 2, ',', '.'); // Beispiel: "1234.56" wird zu "1.234,56 €"

        // E-Mail-Adresse aus der Tabelle `tl_dc_config` abrufen
        $result = $this->db->fetchAssociative('SELECT reservationInfo, reservationInfoText FROM tl_dc_config LIMIT 1');
        $recipientEmail = $result['reservationInfo'] ?? null;
        $informationText = html_entity_decode($result['reservationInfoText'], ENT_QUOTES, 'UTF-8') ?? '<p>Hallo,</p><p>es wurde eine neue Reservierung erstellt.</p>';

        if (empty($recipientEmail)) {
            throw new RuntimeException('Keine Empfänger-E-Mail-Adresse in der Konfiguration gefunden.');
        }

        // Liste der reservierten Assets als HTML formatieren
        $assetsHtml = '<ul>';
        foreach ($reservedItems as $item) {
            $assetsHtml .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $assetsHtml .= '</ul>';

        $informationText = str_replace(
            ['#memberName#', '#reservationNumber#', '#assetsHtml#', '#totalFee#', '#reservedFor#'],
            [$memberName, $reservationNumber, $assetsHtml, $formattedTotalFee, $reservedFor],
            $informationText
        );

        // Erstellen der E-Mail
        $email = new Email();

        $email->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? $configAdapter->get('adminEmail') ?? 'reservierung@diversworld.eu';
        $email->subject = 'Neue Reservierung: ' . $reservationNumber;
        $email->html = $informationText;

        // Versenden der E-Mail
        $emailSuccess = $email->sendTo($recipientEmail); // Empfänger

        if (!$emailSuccess) {
            throw new Exception('Something went wrong while trying to send the reservation Mail.');
        }
    }

    /**
     * Session zurücksetzen.
     */
    private function resetSession(): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $bag->set('reservation_items', []);
        $bag->set('selectedMember', null);
    }

    /**
     * Speichert Reservierungsdaten in der Session.
     */
    private function saveSessionData(array $data, FragmentTemplate $template): void
    {
        try {
            $this->saveDataToSession($data);
            $storedAssets = $this->loadStoredAssets($this->getSessionData());
            $this->displaySuccessMessage($storedAssets, $template);
        } catch (Exception $e) {
            Message::addError('Es gab ein Problem beim Speichern der Reservierungsdaten in der Session.');
            System::getContainer()->get('monolog.logger.contao.general')->error($e->getMessage());
        }
    }

    /**
     * Speichert die Daten in der Session.
     */
    private function saveDataToSession(array $data): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $selectedMember = $bag->get('selectedMember');

        // Bestehende Session-Daten abrufen
        $sessionData = $bag->get('reservation_items', []);

        // Sicherstellen, dass 'selectedAssets' ein Array ist
        $selectedAssets = $data['selectedAssets'] ?? [];
        if (!is_array($selectedAssets)) {
            $selectedAssets = [];
        }

        // Entferne leere Einträge aus dem 'selectedAssets'-Array
        $selectedAssets = array_filter($selectedAssets, function ($assetId) {
            return !empty($assetId);
        });

        // Wenn keine Assets mehr übrig sind, nichts speichern
        if (empty($selectedAssets)) {
            return;
        }

        // Falls übergeben, `type` und `subType` aus dem Formular abrufen
        $type = $data['type'] ?? null;
        $subType = $data['subType'] ?? null;

        // Gesamtpreis dieser Auswahl berechnen
        $totalRentalFee = array_reduce($selectedAssets, function ($carry, $assetId) use ($data) {
            $assetDetails = $this->getAssetDetails($data['category'], (int)$assetId);
            if ($assetDetails) {
                if (preg_match('/([0-9]+\.[0-9]{2}) €/i', $assetDetails, $matches)) {
                    $carry += (float)$matches[1];
                }
            }
            return $carry;
        }, 0.0);

        // Prüfen, ob ein Eintrag für die aktuelle Kategorie existiert
        //$existingCategoryIndex = array_search($data['category'], array_column($sessionData, 'category'));

        // Prüfe, ob ein Eintrag für die aktuelle Kategorie bereits existiert
        $existingCategoryIndex = null;
        foreach ($sessionData as $index => $entry) {
            if (($entry['category'] ?? null) === $data['category']) {
                $existingCategoryIndex = $index;
                break;
            }
        }

        if ($existingCategoryIndex !== null) {

            // Bestehenden Eintrag aktualisieren (alte Assets beibehalten, neue hinzufügen)
            $existingAssets = $sessionData[$existingCategoryIndex]['selectedAssets'] ?? [];

            $existingAssetIds = [];
            // 1. IDs aus `$existingAssets` extrahieren
            foreach ($existingAssets as $asset) {
                if (isset($asset['assetId'])) {
                    $existingAssetIds[] = $asset['assetId'];
                }
            }

            // 2. Assets zusammenführen und Duplikate entfernen
            $mergedAssets = array_unique(array_merge($existingAssetIds, $selectedAssets)); // ["37", "39"]

            // Gesamtpreis für ALLE Assets dieser Kategorie neu berechnen
            $totalRentalFee = array_reduce($mergedAssets, function ($carry, $assetId) use ($data) {
                $assetDetails = $this->getAssetDetails($data['category'], (int)$assetId);
                if ($assetDetails) {
                    if (preg_match('/([0-9]+(\.[0-9]{2})?) €/i', $assetDetails, $matches)) {
                        $carry += (float)$matches[1];
                    }
                }
                return $carry;
            }, 0.0);

            // Assets-Knotenstruktur erweitern (type und subType hinzufügen)
            $assetDetails = [];
            foreach ($mergedAssets as $assetId) {
                // Asset-Details laden, um type und subType für Equipment zu erhalten
                $assetType = null;
                $assetSubType = null;

                if ($data['category'] === 'tl_dc_equipment') {
                    $equipmentAsset = DcEquipmentModel::findByPk((int)$assetId);
                    if ($equipmentAsset) {
                        $assetType = $equipmentAsset->type;
                        $assetSubType = $equipmentAsset->subType;
                    }
                }

                $assetDetails[] = [
                    'assetId' => $assetId,
                    'type' => $assetType,
                    'subType' => $assetSubType,
                ];
            }

            $sessionData[$existingCategoryIndex]['selectedAssets'] = $assetDetails;
            $sessionData[$existingCategoryIndex]['totalRentalFee'] = $totalRentalFee;
            $sessionData[$existingCategoryIndex]['userId'] = $data['userId'] ?? $sessionData[$existingCategoryIndex]['userId'];
            $sessionData[$existingCategoryIndex]['selectedMember'] = $selectedMember ?? $sessionData[$existingCategoryIndex]['selectedMember'];
        } else {
            // Neuer Eintrag für die Kategorie erstellen
            // Gesamtpreis für die neuen Assets berechnen
            $totalRentalFee = array_reduce($selectedAssets, function ($carry, $assetId) use ($data) {
                $assetDetails = $this->getAssetDetails($data['category'], (int)$assetId);
                if ($assetDetails) {
                    if (preg_match('/([0-9]+(\.[0-9]{2})?) €/i', $assetDetails, $matches)) {
                        $carry += (float)$matches[1];
                    }
                }
                return $carry;
            }, 0.0);

            $assetDetails = [];
            foreach ($selectedAssets as $assetId) {
                // Asset-Details laden, um type und subType für Equipment zu erhalten
                $assetType = null;
                $assetSubType = null;

                if ($data['category'] === 'tl_dc_equipment') {
                    $equipmentAsset = DcEquipmentModel::findByPk((int)$assetId);
                    if ($equipmentAsset) {
                        $assetType = $equipmentAsset->type;
                        $assetSubType = $equipmentAsset->subType;
                    }
                }

                $assetDetails[] = [
                    'assetId' => $assetId,
                    'type' => $assetType,
                    'subType' => $assetSubType,
                ];
            }

            $sessionData[] = [
                'userId' => $data['userId'] ?? $this->getCurrentUser()['userId'],
                'category' => $data['category'],
                'selectedAssets' => $assetDetails,
                'totalRentalFee' => $totalRentalFee,
                'selectedMember' => $selectedMember, // Füge den Benutzer hinzu
            ];
        }

        // Aktualisierte Daten speichern
        $bag->set('reservation_items', $sessionData);
    }

    /**
     * Erfolgsmeldung für gespeicherte Assets anzeigen.
     */
    private function displaySuccessMessage(array $storedAssets, FragmentTemplate $template): void
    {
        if (!empty($storedAssets)) {
            $message = sprintf(
                'Die reservierten Gegenstände wurden gespeichert: Gesamtsumme <strong>%.2f €</strong>',
                $this->calculateTotalRentalFee($this->getSessionData())
            );
            Message::addConfirmation($message);
        } else {
            Message::addError('Keine Auswahl getroffen.');
        }

        $template->messages = Message::generate();
    }

    /**
     * Berechnet die Gesamtsumme der Mietkosten.
     */
    private function calculateTotalRentalFee(array $sessionData): float
    {
        $totalRentalFee = 0.0;
        foreach ($sessionData as $entry) {
            $totalRentalFee += (float)($entry['totalRentalFee'] ?? 0);
        }
        return $totalRentalFee;
    }

    /**
     * Optionale Kategorien abrufen.
     */
    private function getCategories(): array
    {
        return [
            'tl_dc_tanks' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_tanks'],
            'tl_dc_regulators' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_regulators'],
            'tl_dc_equipment' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_equipment'],
        ];
    }
}
