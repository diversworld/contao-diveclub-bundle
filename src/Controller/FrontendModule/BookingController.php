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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Email;
use Contao\FormCheckbox;
use Contao\FrontendUser;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Diversworld\ContaoDiveclubBundle\Session\Attribute\ArrayAttributeBag;
use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use function is_array;


#[AsFrontendModule(BookingController::TYPE, category: 'dc_manager')]
class BookingController extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

    protected ?PageModel $page;
    private DcaTemplateHelper $helper;
    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private Connection $db;
    private ContaoCsrfTokenManager $csrfTokenManager;

    public function __construct(
        DcaTemplateHelper $helper,
        Connection        $db,
        RequestStack      $requestStack,
        ContaoFramework   $framework,
        ContaoCsrfTokenManager           $csrfTokenManager,
        private readonly Security        $security,
        #[Autowire(service: 'monolog.logger.contao.general')]
        private readonly LoggerInterface $logger,
    )
    {
        $this->helper = $helper;
        $this->db = $db;
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Haupt-Methode, die die Logik des Moduls steuert.
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $templateData = [
            'element_html_id' => 'mod_' . $model->id,
            'element_css_classes' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'class' => trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? '')),
            'cssID' => $model->cssID[0] ?? '',
        ];

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $templateData['headline'] = [
                'text' => $headline['value'],
                'unit' => $headline['unit'] ?? 'h1'
            ];
        }

        System::loadLanguageFile('tl_dc_reservation_items');

        // Request Token für Twig bereitstellen
        $templateData['request_token'] = $this->csrfTokenManager->getDefaultTokenValue();

        $sessionData = $this->getSessionData();
        $equipmentTypes = $this->helper->getEquipmentTypes(); // Typen/Subtypen laden
        $templateData['equipmentTypes'] = $equipmentTypes;

        // Die allgemeine Ausrüstung ist die Standardansicht des Verleihmoduls.
        // Dadurch werden vorhandene Artikel bereits beim ersten Seitenaufruf angezeigt.
        $category = $request->query->get('category') ?: 'tl_dc_equipment';

        // NEW: Vorgemerkte Reservierungen abrufen
        $templateData['storedAssets'] = $this->loadStoredAssets($sessionData);

        // Member Liste an das Template übergeben
        $memberResult = $this->db->executeQuery('SELECT id, firstname, lastname FROM tl_member ORDER BY lastname, firstname');
        // Ergebnisse in ein assoziatives Array umwandeln
        $templateData['memberList'] = $memberResult->fetchAllAssociative();

        // Session-Daten und ausgewählte Kategorie behandeln
        $totalPrice = $this->calculateTotalPrice($sessionData);
        $templateData['totalPrice'] = $totalPrice;
        $templateData['totalRentalFee'] = $totalPrice; // Konsistenz für das Template
        $templateData['selectedCategory'] = $category;
        $templateData['currentUser'] = $this->getCurrentUser();

        // Verfügbarkeit und Kategorienauswahl
        if ($category) {
            $availableAssets = $this->updateAssets($category);

            $templateData['reservationCheckboxes'] = $this->generateReservationCheckboxes($availableAssets);
            $availableAssets = $this->combineAssetsWithSession($category, $availableAssets);

            // Zugehörige Assets finden und gruppieren
            if ($category === 'tl_dc_equipment') {
                //$availableAssets = $this->updateAssets($category); // Assets für die Kategorie abrufen
                $groupedAssets = $this->groupAssetsByType($availableAssets, $equipmentTypes); // Assets nach Typ gruppieren
                $templateData['groupedAssets'] = $groupedAssets; // Gruppierte Assets ans Template übergeben
            } else {
                $templateData['assets'] = $availableAssets; // Leeres Array, falls die Kategorie nicht zutrifft
            }
        }

        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $selectedMember = $bag->get('selectedMember') ?? null; // Hole den von der Session gespeicherten Benutzer

        if ($selectedMember === null) {
            $selectedMember = $this->getCurrentUser()['userId'] ?? null;
            $bag->set('selectedMember', $selectedMember);
        }

        $templateData['selectedMember'] = $selectedMember; // Ins Template laden

        // Verarbeitung von POST-Daten
        if ($request->isMethod('POST')) {
            $response = $this->handlePostRequest($request, $templateData);
            if ($response instanceof Response) {
                // Wenn handlePostRequest eine RedirectResponse oder ähnliches zurückgibt, wird dies ausgeführt
                return $response;
            }
        }

        $result = $this->db->fetchAssociative('SELECT rentalConditions FROM tl_dc_config LIMIT 1');
        $templateData['rentalConditions'] = $result['rentalConditions'] ?? null;

        // Kategorienauswahl und weiterleiten
        $templateData['categories'] = $this->getCategories();
        $templateData['action'] = $request->getUri();

        // Vorgemerkte Assets immer laden
        $storedAssets = $this->loadStoredAssets($this->getSessionData());

        $templateData['storedAssets'] = $storedAssets;
        $templateData['totalPrice'] = $this->calculateTotalPrice($this->getSessionData());
        $templateData['totalRentalFee'] = $templateData['totalPrice'];

        foreach ($templateData as $key => $value) {
            $template->set($key, $value);
        }

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
                $manufacturerMapping = $this->helper->getManufacturers();
                $manufacturerText = $manufacturerMapping[$asset->manufacturer] ?? 'Unbekannter Hersteller';

                $regModel1stMapping = $this->helper->getRegModels1st((int)$asset->manufacturer);
                $regModel1stText = $regModel1stMapping[$asset->regModel1st] ?? 'N/A';

                $regModel2ndMapping = $this->helper->getRegModels2nd((int)$asset->manufacturer);
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

                $sizeMapping = $this->helper->getSizes();
                $types = $this->helper->getEquipmentTypes();
                $subTypes = $this->helper->getSubTypes($asset->type);
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
        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            return [
                'userId' => (int)$user->id,
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
                    $equipmentSubTypes = $this->helper->getSubTypes((int)$asset['type']);
                    $updatedAssets[] = [
                        'id' => $asset['id'],
                        'type' => $equipmentTypes[$asset['type']]['name'] ?? $asset['type'],
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
//        $types = $this->helper->getEquipmentTypes();

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
    private function handlePostRequest(Request $request, array &$templateData): RedirectResponse
    {
        $formType = $request->request->get('FORM_SUBMIT', null);
        $action = $request->request->get('action', ''); // Der Wert des gedrückten Buttons
        $seite = $request->getUri();
        $urlParts = parse_url($seite);

        // 1. Speichere den Benutzer, für den reserviert werden soll
        if ($formType === 'reservation_select_member') {
            $selectedMember = (int)$request->request->get('reservedFor');

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
                try {
                    $result = $this->saveReservationsToDatabase();
                } catch (\Throwable $exception) {
                    $this->logger->error('Reservation save error: ' . $exception->getMessage(), [
                        'exception' => $exception,
                    ]);
                    Message::addError('Die Reservierung konnte nicht gespeichert werden: ' . $exception->getMessage());

                    return new RedirectResponse($seite);
                }

                try {
                    $this->sendReservationNotification($this->getSessionData(), $result['title']);
                } catch (\Throwable $exception) {
                    // Eine fehlgeschlagene Benachrichtigung darf die bereits vollständig
                    // gespeicherte Reservierung nicht rückgängig machen.
                    $this->logger->error('Reservation notification error: ' . $exception->getMessage(), [
                        'exception' => $exception,
                        'reservationId' => $result['id'],
                    ]);
                    Message::addInfo('Die Reservierung wurde gespeichert, die Benachrichtigung konnte jedoch nicht versendet werden.');
                }

                $this->resetSession();
                Message::addConfirmation(sprintf(
                    'Reservierung %s mit %d Position(en) wurde gespeichert.',
                    $result['title'],
                    $result['itemCount'],
                ));

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
                try {
                    $this->saveDataToSession($request->request->all());
                    Message::addConfirmation('Ausrüstung vorgemerkt.');
                } catch (\Throwable $exception) {
                    $this->logger->error('Could not add assets to reservation session: ' . $exception->getMessage(), [
                        'exception' => $exception,
                    ]);
                    Message::addError($exception->getMessage());
                }

                return new RedirectResponse($seite);  // Zurück zum Template
        }

        $templateData['messages'] = Message::generate();

        // 3. Standardverarbeitung: Falls keine Aktion erkannt wurde
        Message::addError('Ungültige Aktion.');

        // Default-Fall, falls keine gültige Aktion erkannt wurde
        return new RedirectResponse($seite);

    }

    /**
     * Speichert Reservierungen in die Datenbank.
     */
    /**
     * @return array{id: int, title: string, itemCount: int, totalFee: float}
     */
    private function saveReservationsToDatabase(): array
    {
        $sessionData = $this->getSessionData();

        if (empty($sessionData)) {
            throw new RuntimeException('Es wurden keine Ausrüstungsgegenstände vorgemerkt.');
        }

        return $this->saveDataToDb($sessionData);
    }

    /**
     * Speichert Reservierungskopf, Positionen und Asset-Status atomar.
     *
     * @param array<int, array<string, mixed>> $sessionData
     *
     * @return array{id: int, title: string, itemCount: int, totalFee: float}
     */
    private function saveDataToDb(array $sessionData): array
    {
        $firstEntry = reset($sessionData);
        $userId = (int)($firstEntry['userId'] ?? 0);
        $reservedFor = (int)($firstEntry['selectedMember'] ?? 0);
        $reservationTitle = $this->generateReservationTitle($userId);
        $timestamp = time();

        return $this->db->transactional(function (Connection $connection) use (
            $sessionData,
            $userId,
            $reservedFor,
            $reservationTitle,
            $timestamp,
        ): array {
            $items = [];
            $seenAssets = [];
            $categories = [];
            $totalFee = 0.0;

            foreach ($sessionData as $entry) {
                $category = (string)($entry['category'] ?? '');
                $this->assertAllowedCategory($category);

                foreach (($entry['selectedAssets'] ?? []) as $asset) {
                    $assetId = (int)($asset['assetId'] ?? 0);
                    $assetKey = $category . ':' . $assetId;

                    if ($assetId <= 0 || isset($seenAssets[$assetKey])) {
                        continue;
                    }

                    $row = $connection->fetchAssociative(
                        sprintf(
                            'SELECT id, status, published, rentalFee%s FROM %s WHERE id = ? FOR UPDATE',
                            'tl_dc_equipment' === $category ? ', type, subType' : '',
                            $category,
                        ),
                        [$assetId],
                    );

                    if (!$row) {
                        throw new RuntimeException(sprintf('Der Ausrüstungsgegenstand %s/%d wurde nicht gefunden.', $category, $assetId));
                    }

                    if ('1' !== (string)$row['published'] || 'available' !== (string)$row['status']) {
                        throw new RuntimeException(sprintf('Der Ausrüstungsgegenstand %s/%d ist nicht mehr verfügbar.', $category, $assetId));
                    }

                    $fee = (float)$row['rentalFee'];
                    $totalFee += $fee;
                    $categories[$category] = true;
                    $seenAssets[$assetKey] = true;
                    $items[] = [
                        'category' => $category,
                        'assetId' => $assetId,
                        'type' => 'tl_dc_equipment' === $category ? (string)$row['type'] : '',
                        'subType' => 'tl_dc_equipment' === $category ? (string)$row['subType'] : '',
                    ];
                }
            }

            if (!$items) {
                throw new RuntimeException('Die Vormerkliste enthält keine gültigen Ausrüstungsgegenstände.');
            }

            $connection->insert('tl_dc_reservation', [
                'tstamp' => $timestamp,
                'title' => $reservationTitle,
                'alias' => 'res-' . $reservationTitle . '-' . bin2hex(random_bytes(4)),
                'reservation_status' => 'reserved',
                'member_id' => $userId,
                'reservedFor' => $reservedFor,
                'rentalFee' => number_format($totalFee, 2, '.', ''),
                'asset_type' => 1 === count($categories) ? (string)array_key_first($categories) : 'multiple',
                'reserved_at' => $timestamp,
                'published' => 1,
            ]);

            $reservationId = (int)$connection->lastInsertId();
            $sorting = 128;

            foreach ($items as $item) {
                $connection->insert('tl_dc_reservation_items', [
                    'pid' => $reservationId,
                    'tstamp' => $timestamp,
                    'sorting' => $sorting,
                    'item_id' => $item['assetId'],
                    'item_type' => $item['category'],
                    'types' => $item['type'],
                    'sub_type' => $item['subType'],
                    'reserved_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                    'reservation_status' => 'reserved',
                    'published' => 1,
                ]);

                $affected = $connection->update(
                    $item['category'],
                    ['status' => 'reserved', 'tstamp' => $timestamp],
                    ['id' => $item['assetId'], 'status' => 'available'],
                );

                if (1 !== $affected) {
                    throw new RuntimeException(sprintf(
                        'Der Status des Ausrüstungsgegenstands %s/%d konnte nicht aktualisiert werden.',
                        $item['category'],
                        $item['assetId'],
                    ));
                }

                $sorting += 128;
            }

            return [
                'id' => $reservationId,
                'title' => $reservationTitle,
                'itemCount' => count($items),
                'totalFee' => $totalFee,
            ];
        });
    }

    private function assertAllowedCategory(string $category): void
    {
        if (!in_array($category, ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment'], true)) {
            throw new RuntimeException('Ungültige Ausrüstungskategorie.');
        }
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
     * Sends reservation notification via email.
     */
    private function sendReservationNotification(array $sessionData, string $reservationNumber): void
    {
        if (empty($sessionData)) {
            return;
        }

        $totalPrice = $this->calculateTotalPrice($sessionData);

        // Details aus den Session-Daten extrahieren
        $firstEntry = reset($sessionData);
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
        $category = (string)($data['category'] ?? '');
        $this->assertAllowedCategory($category);

        $selectedAssets = $data['selectedAssets'] ?? [];
        if (!is_array($selectedAssets)) {
            $selectedAssets = [];
        }

        $selectedAssets = array_values(array_unique(array_filter(
            array_map('intval', $selectedAssets),
            static fn(int $assetId): bool => $assetId > 0,
        )));

        if (empty($selectedAssets)) {
            throw new RuntimeException('Bitte wählen Sie mindestens einen Ausrüstungsgegenstand aus.');
        }

        // Falls übergeben, `type` und `subType` aus dem Formular abrufen
        //$type = $data['type'] ?? null;
        //$subType = $data['subType'] ?? null;

        // Gesamtpreis dieser Auswahl berechnen
        $totalRentalFee = array_reduce($selectedAssets, function ($carry, $assetId) use ($category) {
            $assetDetails = $this->getAssetDetails($category, (int)$assetId);
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
            if (($entry['category'] ?? null) === $category) {
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
            $totalRentalFee = array_reduce($mergedAssets, function ($carry, $assetId) use ($category) {
                $assetDetails = $this->getAssetDetails($category, (int)$assetId);
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

                if ($category === 'tl_dc_equipment') {
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
            $sessionData[$existingCategoryIndex]['userId'] = $this->getCurrentUser()['userId'];
            $sessionData[$existingCategoryIndex]['selectedMember'] = $selectedMember ?? $sessionData[$existingCategoryIndex]['selectedMember'];
        } else {
            // Neuer Eintrag für die Kategorie erstellen
            // Gesamtpreis für die neuen Assets berechnen
            $totalRentalFee = array_reduce($selectedAssets, function ($carry, $assetId) use ($category) {
                $assetDetails = $this->getAssetDetails($category, (int)$assetId);
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

                if ($category === 'tl_dc_equipment') {
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
                'userId' => $this->getCurrentUser()['userId'],
                'category' => $category,
                'selectedAssets' => $assetDetails,
                'totalRentalFee' => $totalRentalFee,
                'selectedMember' => $selectedMember, // Füge den Benutzer hinzu
            ];
        }

        // Aktualisierte Daten speichern
        $bag->set('reservation_items', $sessionData);
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
