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
use Contao\Email;
use Contao\FormCheckbox;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationModel;
use Diversworld\ContaoDiveclubBundle\Session\Attribute\ArrayAttributeBag;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;


#[AsFrontendModule(ModuleBooking::TYPE, category: 'dc_modules', template: 'mod_dc_booking')]
class ModuleBooking extends AbstractFrontendModuleController
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
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        System::loadLanguageFile('tl_dc_reservation_items');

        $sessionData = $this->getSessionData();
        $category = $request->get('category');

        // NEU: Gesamtpreis berechnen und ans Template übergeben
        $totalPrice = $this->calculateTotalPrice($sessionData);
        $template->totalPrice = $totalPrice;

        // NEW: Vorgemerkte Reservierungen abrufen
        $storedAssets = $this->loadStoredAssets($sessionData);
        $template->storedAssets = $storedAssets;

        // Session-Daten und ausgewählte Kategorie behandeln
        $template->totalRentalFee = $this->calculateTotalRentalFee($sessionData);
        $template->selectedCategory = $category;
        $template->currentUser = $this->getCurrentUser();

        // Verfügbarkeit und Kategorienauswahl
        if ($category) {
            $availableAssets = $this->updateAssets($category);
            $template->reservationCheckboxes = $this->generateReservationCheckboxes($availableAssets);
            $availableAssets = $this->combineAssetsWithSession($category, $availableAssets);

            // Zugehörige Assets finden und gruppieren
            if ($category === 'tl_dc_equipment_types') {
                $availableAssets = $this->updateAssets($category); // Assets für die Kategorie abrufen
                $groupedAssets = $this->groupAssetsByType($availableAssets); // Assets nach Typ gruppieren
                $template->groupedAssets = $groupedAssets; // Gruppierte Assets ans Template übergeben
            } else {
                $template->assets = $availableAssets; // Leeres Array, falls die Kategorie nicht zutrifft
            }


        }

        // Verarbeitung von POST-Daten
        if ($request->isMethod('POST')) {
            $this->handlePostRequest($request, $template, $sessionData, $category);
        }

        // Kategorienauswahl und weiterleiten
        $template->categories = $this->getCategories();
        $template->action = $request->getUri();

        // Vorgemerkte Assets immer laden
        $storedAssets = $this->loadStoredAssets($this->getSessionData());
        $template->storedAssets = $storedAssets;
        $template->totalRentalFee = $this->calculateTotalRentalFee($this->getSessionData());

        return $template->getResponse();
    }

    /**
     * POST-Anfrage verarbeiten.
     */
    private function handlePostRequest(Request $request, Template $template, array $sessionData, ?string $category): void
    {
        $formType = $request->request->get('FORM_SUBMIT');
        $action = $request->request->get('action');

        // Abbrechen und Session zurücksetzen
        if ('cancel' === $action) {
            $this->resetSession();
            Message::addConfirmation('Die Reservierung wurde abgebrochen und die Session-Daten wurden gelöscht.');
            $this->redirect($request->getUri());
        }

        // Session-Daten speichern
        if ('reservationItems_submit' === $formType) {
            $this->saveSessionData($request->request->all(), $template);
        }

        // Reservierungen in der Datenbank speichern
        if ('reservationSubmit' === $formType) {
            $this->saveReservationsToDatabase($template, $sessionData);
            $this->resetSession();
        }

        $template->messages = Message::generate();
    }

    /**
     * Speichert Reservierungsdaten in der Session.
     */
    private function saveSessionData(array $data, Template $template): void
    {
        try {
            $this->saveDataToSession($data);
            $storedAssets = $this->loadStoredAssets($this->getSessionData());
            $this->displaySuccessMessage($storedAssets, $template);
        } catch (\Exception $e) {
            Message::addError('Es gab ein Problem beim Speichern der Reservierungsdaten in der Session.');
            System::getContainer()->get('monolog.logger.contao.general')->error($e->getMessage());
        }
    }

    /**
     * Speichert Reservierungen in die Datenbank.
     */
    private function saveReservationsToDatabase(Template $template, array $sessionData): void
    {
        try {
            $saveMessage = $this->saveDataToDb();
            Message::addConfirmation(htmlspecialchars($saveMessage));
            $this->sendReservationNotification($sessionData);
        } catch (\Exception $e) {
            Message::addError('Fehler beim Speichern der Reservierungen in der Datenbank.');
            System::getContainer()->get('monolog.logger.contao.general')->error($e->getMessage());
        }
    }

    /**
     * Sends reservation notification via email.
     */
    private function sendReservationNotification(array $sessionData): void
    {
        if (empty($sessionData)) {
            throw new \RuntimeException('Keine Reservierungsdaten für die Benachrichtigung vorhanden.');
        }

        // Details aus den Session-Daten extrahieren
        $reservationId = $sessionData[0]['reservationId'] ?? 0; // Beispiel-ID
        $reservationNumber = $this->generateReservationTitle((int) $sessionData[0]['userId'] ?? 0);
        $memberName = $this->getCurrentUser()['userFullName'] ?? 'Unbekannt';

        // Reservierte Items aus Session-Daten
        $reservedItems = [];
        foreach ($sessionData as $entry) {
            if (!empty($entry['selectedAssets'])) {
                foreach ($entry['selectedAssets'] as $assetId) {
                    $assetDetails = $this->getAssetDetails($entry['category'], (int) $assetId);
                    if ($assetDetails) {
                        $reservedItems[] = $assetDetails;
                    }
                }
            }
        }

        if (empty($reservedItems)) {
            throw new \RuntimeException('Es wurden keine Assets für die Benachrichtigung reserviert.');
        }

        // E-Mail senden
        $this->sendReservationEmail($reservationId, $reservationNumber, $memberName, $reservedItems);
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

            foreach ($selectedAssets as $assetId) {
                $assetDetails = $this->getAssetDetails($category, (int)$assetId);

                if ($assetDetails) {
                    $storedAssets[] = $assetDetails;
                }
            }
        }

        return $storedAssets;
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
     * Session zurücksetzen.
     */
    private function resetSession(): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $bag->set('reservation_items', []);
    }

    /**
     * Berechnet die Gesamtsumme der Mietkosten.
     */
    private function calculateTotalRentalFee(array $sessionData): float
    {
        $totalRentalFee = 0.0;
        foreach ($sessionData as $entry) {
            $totalRentalFee += (float) ($entry['totalRentalFee'] ?? 0);
        }
        return $totalRentalFee;
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
            foreach ($selectedAssets as $assetId) {
                // Asset-Details abrufen
                $assetDetails = $this->getAssetDetails($category, (int)$assetId);

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

    /**
     * Gruppiert Assets nach Typ.
     */
    private function groupAssetsByType(array $assets): array
    {
        $groupedAssets = [];

        foreach ($assets as $asset) {
            $type = $asset['type'] ?? 'Unbekannter Typ';
            $groupedAssets[$type][] = $asset;
        }

        return $groupedAssets;
    }

    /**
     * Optionale Kategorien abrufen.
     */
    private function getCategories(): array
    {
        return [
            'tl_dc_tanks' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_tanks'],
            'tl_dc_regulators' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_regulators'],
            'tl_dc_equipment_types' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_equipment_types'],
        ];
    }

    /**
     * Erfolgsmeldung für gespeicherte Assets anzeigen.
     */
    private function displaySuccessMessage(array $storedAssets, Template $template): void
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
     * Speichert die Daten in der Session.
     */
    private function saveDataToSession(array $data): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []);

        // Verarbeiten und Daten speichern
        $totalRentalFee = 0; // Beispiel: Mietkosten berechnen

        // Berechnung des Mietpreises (sichere Extraktion)
        if (isset($data['selectedAssets']) && is_array($data['selectedAssets'])) {
            foreach ($data['selectedAssets'] as $assetId) {
                $assetDetails = $this->getAssetDetails($data['category'], (int) $assetId);

                if ($assetDetails) {
                    // Preis aus den Assetdetails extrahieren
                    preg_match('/([0-9]+\.[0-9]{2}) €/i', $assetDetails, $matches);

                    if (!empty($matches[1])) {
                        $totalRentalFee += (float)$matches[1];
                    }
                }
            }
        }

        $sessionData[] = [
            'userId' => $data['userId'],
            'category' => $data['category'],
            'selectedAssets' => $data['selectedAssets'],
            'totalRentalFee' => $totalRentalFee, // Füge die Gesamtsumme hinzu
        ];

        // Speichere aktualisierte Session-Daten
        $bag->set('reservation_items', $sessionData);
    }

    /**
     * Save Data to Database
     */
    function saveDataToDb(): string
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []); // Alle gespeicherten Reservierungsdaten abrufen

        if (empty($sessionData)) {
            throw new \RuntimeException('Es sind keine Reservierungsdaten in der Session gespeichert.');
        }

        foreach ($sessionData as $entry) {
            $userId = $entry['userId'] ?? null;
            $category = $entry['category'] ?? null;
            $selectedAssets = $entry['selectedAssets'] ?? [];
            $pid = $entry['pid'] ?? null;
            $totalRentalFee = $entry['totalRentalFee'] ?? 0; // Mietkosten berechnen


            if (empty($selectedAssets)) {
                continue; // Überspringen, wenn keine Assets ausgewählt sind
            }

            if (!$userId || !$category) {
                throw new \RuntimeException('Ungültige Session-Daten: Benutzer oder Kategorie fehlt.');
            }
/*
            foreach ($selectedAssets as $assetId) {
                $query = $this->db->prepare("SELECT rentalFee FROM {$category} WHERE id = ?");
                $result = $query->executeQuery([$assetId])->fetchOne();
                $totalRentalFee += (float)$result; // `rentalFee` summieren
            }
*/
            // Reservierungstitel generieren
            $reservationTitle = $this->generateReservationTitle((int) $userId);

            // Prüfen, ob eine Reservierung existiert
            $reservation = DcReservationModel::findOneBy(['title=?'], [$reservationTitle]);
            if (null === $reservation) {
                // Neue Reservierung erstellen
                $reservation = new DcReservationModel();
                $reservation->title = $reservationTitle;
                $reservation->alias = 'id-' . $reservationTitle;
                $reservation->tstamp = time();
                $reservation->member_id = $userId;
                $reservation->asset_type = $category;
                $reservation->reserved_at = time();
                $reservation->reservation_status = 'reserved';
                $reservation->rentalFee = $totalRentalFee; // Gesamtsumme in die Tabelle speichern
                $reservation->published = 1;
                $reservation->save();
            }

            $reservationId = $reservation->id;

            foreach ($selectedAssets as $assetId) {
                $existingItem = DcReservationItemsModel::findOneBy([
                    'pid=? AND item_id=?',
                ], [
                    $reservationId,
                    $assetId,
                ]);

                if (null === $existingItem) {
                    // Neue Reservierung für dieses Asset erstellen
                    $reservationItem = new DcReservationItemsModel();
                    $reservationItem->pid = $reservationId;
                    $reservationItem->tstamp = time();
                    $reservationItem->item_id = (int) $assetId;
                    $reservationItem->item_type = $category;
                    $reservationItem->reserved_at = time();
                    $reservationItem->created_at = time();
                    $reservationItem->updated_at = time();
                    $reservationItem->reservation_status = 'reserved';
                    $reservationItem->published = 1;

                    if ('tl_dc_equipment_types' === $category) {
                        $query = $this->db->prepare('SELECT title, subType FROM tl_dc_equipment_types WHERE id = ?');
                        $result = $query->executeQuery([$pid])->fetchAssociative();

                        if ($result) {
                            $reservationItem->types = $result['title'];
                            $reservationItem->sub_type = $result['subType'];
                        }
                    }

                    $reservationItem->save();
                }

                // Status des Assets aktualisieren
                $this->updateAssetStatus($category, (int) $assetId);
            }
        }

        return 'Reservierungsdaten wurden erfolgreich gespeichert.';
    }

    /**
     * @param int $reservationId
     * @param string $reservationNumber
     * @param string $memberName
     * @param array $reservedItems
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    function sendReservationEmail(int $reservationId, string $reservationNumber, string $memberName, array $reservedItems): void
    {
        $configAdapter = $this->framework->getAdapter(Config::class);

        // E-Mail-Adresse aus der Tabelle `tl_dc_config` abrufen
        $result = $this->db->fetchAssociative('SELECT reservationInfo, reservationInfoText FROM tl_dc_config LIMIT 1');
        $recipientEmail = $result['reservationInfo'] ?? null;
        $informationText = html_entity_decode($result['reservationInfoText'] , ENT_QUOTES, 'UTF-8') ?? '<p>Hallo,</p><p>es wurde eine neue Reservierung erstellt.</p>';

        if (empty($recipientEmail)) {
            throw new \RuntimeException('Keine Empfänger-E-Mail-Adresse in der Konfiguration gefunden.');
        }

        // Liste der reservierten Assets als HTML formatieren
        $assetsHtml = '<ul>';
        foreach ($reservedItems as $item) {
            $assetsHtml .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $assetsHtml .= '</ul>';

        $informationText = str_replace(
            ['#memberName#', '#reservationNumber#', '#assetsHtml#'],
            [$memberName, $reservationNumber, $assetsHtml],
            $informationText
        );

        // Erstellen der E-Mail
        $email = new Email();

        $email->from    = $GLOBALS['TL_ADMIN_EMAIL'] ?? $configAdapter->get('adminEmail') ?? 'reservierung@diversworld.eu';
        $email->subject = 'Neue Reservierung: ' . $reservationNumber;
        $email->html = $informationText;

        // Versenden der E-Mail
        $emailSuccess = $email->sendTo($recipientEmail); // Empfänger

        if (!$emailSuccess) {
            throw new \Exception('Something went wrong while trying to send the reservation Mail.');
        }
    }

    /**
    * Aktualisiert den Status eines Assets nach der Reservierung.
    */
    private function updateAssetStatus(string $category, int $assetId): void
            {
                switch ($category) {
                    case 'tl_dc_tanks':
                        $this->db->update('tl_dc_tanks', ['status' => 'reserved'], ['id' => $assetId]);
                        break;
                    case 'tl_dc_regulators':
                        $this->db->update('tl_dc_regulators', ['status' => 'reserved'], ['id' => $assetId]);
                        break;
                    case 'tl_dc_equipment_types':
                        $this->db->update('tl_dc_equipment_subtypes', ['status' => 'reserved'], ['id' => $assetId]);
                        break;
                    default:
                        throw new \RuntimeException('Ungültige Kategorie beim Aktualisieren des Asset-Status.');
                }
            }

    /**
     * @param string $category
     * @return array
     */
    function updateAssets( string $category) : array
    {
        // Verfügbare Assets laden
        // Fetch mappings from the Helpers
        $manufacturers = $this->helper->getManufacturers();
        $sizes = $this->helper->getSizes();
        $equipmentTypes = $this->helper->getEquipmentTypes();

        // Datum global abrufen
        $dateFormat = Config::get('dateFormat');

        // Fetch the pid-to-title mapping from the `tl_dc_equipment_types` table directly
        $equipmentTypesMapping = $this->getEquipmentTypeTitles(); // Custom method, explained below
        $updatedAssets = [];
        $assets = $this->getAvailableAssets($category);

        switch ($category) {
            case 'tl_dc_tanks':
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
                        'price'         => $asset['rentalFee'] ?? 'N/A',
                    ];
                }
                break;

            case 'tl_dc_regulators':
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
                        'price'         => $asset['rentalFee'] ?? 'N/A',
                    ];
                }
                break;

            case 'tl_dc_equipment_types':
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
                        'price'         => $asset['rentalFee'] ?? 'N/A',
                    ];
                }
                break;

            default:
                // Fallback: Falls keine Kategorie zutrifft, keine Verarbeitung
                foreach ($assets as $asset) {
                    $updatedAssets[] = [
                        'id'            => $asset['id'] ?? 'N/A',
                        'title'         => $asset['title'] ?? 'N/A',
                        'manufacturer'  => $asset['manufacturer'] ?? 'N/A',
                        'size'          => $asset['size'] ?? 'N/A',
                    ];
                }
                break;
        }
        return $updatedAssets;
    }

    /**
     * Get the mapping of `pid` to `title` in the `tl_dc_equipment_types` table.
     */
    private function getEquipmentTypeTitles(): array
    {
        // Fetch the `pid` and `title` from the equipment types table
        $results = $this->db->fetchAllAssociative(
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
        // Unterschiedliche Queries für verschiedene Kategorien
        switch ($category) {
            case 'tl_dc_tanks':
                $query = "SELECT id, title, serialNumber, manufacturer, bazNumber, size, o2clean, owner, checkId, lastCheckDate, nextCheckDate, status, rentalFee FROM tl_dc_tanks WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_regulators':
                $query = "SELECT id, title, manufacturer, serialNumber1st, regModel1st, serialNumber2ndPri, regModel2ndPri, serialNumber2ndSec, regModel2ndSec, status, rentalFee FROM tl_dc_regulators WHERE status = 'available'";
                $params = [];
                break;
            case 'tl_dc_equipment_types':
                //$query = "SELECT id, pid, title, status, manufacturer, model, color, size, serialNumber, buyDate, status FROM tl_dc_equipment_subtypes WHERE status = 'available' ORDER BY pid";
                $query = "SELECT es.id, es.pid, es.title, es.status, es.manufacturer, es.model, es.color, es.size, es.serialNumber, es.buyDate, es.status, et.rentalFee
                            FROM tl_dc_equipment_subtypes es
                            INNER JOIN tl_dc_equipment_types et ON es.pid = et.id
                            WHERE es.status = 'available' ORDER BY es.pid";
                $params = [];
                break;
            default:
                return [];
        }

        // Ergebnisse abrufen und zurückgeben
        try {
            return $this->db->fetchAllAssociative($query);
        } catch (\Exception $e) {
            // Fehlermeldung bei Problemen mit der Datenbank
            System::getContainer()->get('monolog.logger.contao.general')->error('Fehler beim Laden der Assets: ' . $e->getMessage());
            return [];
        }
    }

    private function getAssetDetails(string $category, int $assetId): ?string
    {
        // Abrufen der verfügbaren Assets für die entsprechende Kategorie
        $availableAssets = $this->getAvailableAssets($category);

        // Filtern des spezifischen Assets basierend auf der ID
        $assetDetails = array_filter($availableAssets, fn($asset) => (int)$asset['id'] === $assetId);

        // Prüfen, ob das Asset gefunden wurde
        if (empty($assetDetails)) {
            return null; // Kein passendes Asset gefunden
        }

        // Das erste gefundene Asset aus dem Array extrahieren (array_filter gibt ein Array zurück)
        $assetDetails = array_shift($assetDetails);

        // Rückgabe formatieren basierend auf der Kategorie
        switch ($category) {
            case 'tl_dc_tanks':
                $sizeText = $assetDetails['size'] . 'L' ?? 'Unbekannte Größe';
                return sprintf(
                    'Tank: %s (Größe: %s), %s',
                    $assetDetails['title'] ?? 'Unbekannt',
                    $sizeText,
                    number_format((float)$assetDetails['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt'
                );

            case 'tl_dc_regulators':
                $helper = new DcaTemplateHelper();

                // Hersteller und Modelle durch Helper-Methode auflösen
                $manufacturerMapping = $helper->getManufacturers();
                $manufacturerText = $manufacturerMapping[$assetDetails['manufacturer']] ?? 'Unbekannter Hersteller';

                $regModel1stMapping = $helper->getRegModels1st((int) $assetDetails['manufacturer']);
                $regModel1stText = $regModel1stMapping[$assetDetails['regModel1st']] ?? 'N/A';

                $regModel2ndMapping = $helper->getRegModels2nd((int) $assetDetails['manufacturer']);
                $regModel2ndPriText = $regModel2ndMapping[$assetDetails['regModel2ndPri']] ?? 'N/A';
                $regModel2ndSecText = $regModel2ndMapping[$assetDetails['regModel2ndSec']] ?? 'N/A';

                return sprintf(
                    'Regulator: %s (Hersteller: %s, 1st Stage: %s, 2nd Stage (Pri): %s, 2nd Stage (Sec): %s), %s',
                    $assetDetails['title'] ?? 'Unbekannt',
                    $manufacturerText,
                    $regModel1stText,
                    $regModel2ndPriText,
                    $regModel2ndSecText,
                    number_format((float)$assetDetails['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt'
                );

            case 'tl_dc_equipment_types':
                $helper = new DcaTemplateHelper();
                $sizeMapping = $helper->getSizes();
                $sizeText = $sizeMapping[$assetDetails['size']] ?? 'Unbekannte Größe';

                return sprintf(
                    'Ausrüstung: %s (Modell: %s, Größe: %s, Farbe: %s), %s',
                    $assetDetails['title'] ?? 'Unbekannt',
                    $assetDetails['model'] ?? 'Kein Modell angegeben',
                    $sizeText,
                    $assetDetails['color'] ?? 'Keine Farbe angegeben',
                    number_format((float)$assetDetails['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt'
                );

            default:
                return null; // Kategorie unbekannt
        }
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
            $selectedAssetIds = array_merge($selectedAssetIds, $entry['selectedAssets'] ?? []);
        }
        $selectedAssetIds = array_unique($selectedAssetIds); // Doppelte entfernen

        // Markiere Assets als "ausgewählt", falls sie in der Session sind
        foreach ($assets as &$asset) {
            $asset['selected'] = in_array($asset['id'], $selectedAssetIds, true); // Vergleicht IDs
        }

        return $assets;
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
        $currentDate = date('ymdHi');
        return $currentDate . $formattedMemberId;
    }
}
