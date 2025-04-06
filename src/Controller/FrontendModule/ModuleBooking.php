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
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;


#[AsFrontendModule(ModuleBooking::TYPE, category: 'dc_modules', template: 'mod_dc_booking')]
class ModuleBooking extends AbstractFrontendModuleController
{
    public const TYPE = 'dc_booking';

    protected ?PageModel $page;

    private DcaTemplateHelper $helper;
    private RequestStack $requestStack;

    private Connection $db;
    public function __construct(DcaTemplateHelper $helper, Connection $db, ContaoFramework $framework, RequestStack $requestStack)
    {
        $this->helper = $helper;
        $this->db = $db;
        $this->framework = $framework;
        $this->requestStack = $requestStack;
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
        // Sprachdateien laden
        System::loadLanguageFile('tl_dc_reservation_items');

        // Session und Hilfsparameter
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []); // Alle Daten in der Session

        // Kategorie aus dem Request abrufen
        $category = $request->get('category');
        $template->selectedCategory = $category;

        // Benutzerinformationen abrufen
        $hasFrontendUser = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
        $userId = null;
        $userFullName = 'Gast';

        if ($hasFrontendUser) {
            $user = $this->getUser();
            $userId = $user->id;
            $userFullName = trim($user->firstname . ' ' . $user->lastname);
        }

        // Benutzerinformation ans Template übergeben
        $template->currentUser = [
            'userId'       => $userId,
            'userFullName' => $userFullName ?: 'Gast',
        ];

        if ($category ) {
            // Verfügbare Assets abrufen
            $updatedAssets = $this->updateAssets($category);

            // Session-Daten mit geladenen Assets kombinieren
            $updatedAssets = $this->combineAssetsWithSession($category, $updatedAssets);

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
                $template->assets = $assets; // Keine Gruppierung für andere Kategorien
            }

            // Checkboxen für verfügbare Assets generieren
            $reservationCheckboxes = $this->generateReservationCheckboxes($updatedAssets);
            $template->reservationCheckboxes = $reservationCheckboxes;
        }

        // POST-Anfrage: Verarbeitungen
        if ($request->isMethod('POST')) {
            $formType = $request->request->get('FORM_SUBMIT');

            // A - Speichern in der Session
            if ('reservationItems_submit' === $formType) {
                try {
                    // Neue Reservierungen in der Session speichern
                    $this->saveDataToSession($request->request->all());

                    // Session-Daten neu laden
                    $sessionData = $bag->get('reservation_items', []);

                    // Reservierte Items aus der Session laden
                    $storedAssets = [];
                    foreach ($sessionData as $entry) {
                        dump($entry);
                        if (!empty($entry['selectedAssets'])) {
                            foreach ($entry['selectedAssets'] as $assetId) {
                                $assetDetails = $this->getAssetDetails($entry['category'], (int) $assetId);
                                if ($assetDetails) {
                                    $storedAssets[] = $assetDetails;
                                }
                            }
                        }
                    }

                    $totalPrice = '';
                    // Erfolgsmeldung für gespeicherte Items
                    if (!empty($storedAssets)) {
                        $message = 'Die folgenden Reservierungen wurden vorgemerkt gespeichert:<br><ul>';
                        foreach ($storedAssets as $item) {
                            $message .= '<li>' . htmlspecialchars($item) . '</li>';
                        }
                        $message .= '</ul>';
                        Message::addConfirmation($message);
                    }

                    $template->messages = Message::generate();
                } catch (\Exception $e) {
                    // Fehlermeldung bei Problemen mit der Session-Speicherung
                    System::getContainer()->get('monolog.logger.contao.general')->error($e->getMessage());
                    Message::addError('Es gab ein Problem beim Speichern der Reservierungsdaten in der Session.');
                    $template->messages = Message::generate();
                }
            }

            // B - Speichern in die Datenbank
            if ('reservationSubmit' === $formType) {
                try {
                    // Reservierungsdaten in die Datenbank speichern
                    $saveMessage = $this->saveDataToDb();

                    // Erfolgreich-Meldung anzeigen
                    Message::addConfirmation(htmlspecialchars($saveMessage));

                    // Reservierungen aus der Datenbank ausgeben
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

                    if (!empty($reservedItems)) {
                        $emailMessage = 'Die Reservierung wurde erfolgreich durchgeführt und eine Bestätigungsmail versendet.<br>Reservierte Gegenstände:<ul>';
                        foreach ($reservedItems as $item) {
                            $emailMessage .= '<li>' . htmlspecialchars($item) . '</li>';
                        }
                        $emailMessage .= '</ul>';
                        Message::addConfirmation($emailMessage);
                    }

                    // Mail senden
                    $this->sendReservationEmail((int) $userId, 'SP-01', $userFullName, $reservedItems);

                    // Nachrichten generieren
                    $template->messages = Message::generate();

                    // Session zurücksetzen (optional, um doppelte Speicherung zu verhindern)
                    $bag->set('reservation_items', []);
                } catch (\Exception $e) {
                    // Fehler bei der Datenbank-Speicherung
                    System::getContainer()->get('monolog.logger.contao.general')->error($e->getMessage());
                    Message::addError('Es gab ein Problem beim Speichern der Reservierungen in der Datenbank.');
                    $template->messages = Message::generate();
                }
            }
        }

        // Kategorien-Auswahl (Dropdown)
        $categories = [
            'tl_dc_tanks'           => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_tanks'],
            'tl_dc_regulators'      => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_regulators'],
            'tl_dc_equipment_types' => $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']['tl_dc_equipment_types'],
        ];

        $template->categories = $categories;
        // Weitergabe an Twig
        $template->action = $request->getUri();

        return $template->getResponse();
    }

    /**
     * Speichert die Daten in der Session.
     */
    private function saveDataToSession(array $data): void
    {
        $session = $this->requestStack->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionData = $bag->get('reservation_items', []);

        // Extrahiert Daten aus der Anfrage
        $userId = $data['userId'] ?? null;
        $category = $data['category'] ?? null;
        $selectedAssets = $data['selectedAssets'] ?? [];
        $selectedAssets = array_filter($selectedAssets, fn($value) => !empty($value));
        $pid = $data['pid'] ?? null;

        // Daten validieren
        if (!$userId || !$category || empty($selectedAssets)) {
            throw new \RuntimeException('Ungültige Daten: Es fehlt der Benutzer, Kategorie oder ausgewählte Assets.');
        }

        $totalRentalFee = 0; // Variable für die Gesamtsumme
        foreach ($selectedAssets as $assetId) {
            $query = $this->db->prepare("SELECT rentalFee FROM {$category} WHERE id = ?"); // Tabelle dynamisch verwenden
            $result = $query->executeQuery([$assetId])->fetchOne();
            $totalRentalFee += (float)$result; // `rentalFee` addieren
        }

        // Füge die Gesamtsumme in die Nachricht hinzu
        if ($totalRentalFee > 0) {
            Message::addConfirmation(sprintf(
                'Die reservierten Gegenstände wurden gespeichert. Gesamtsumme: %.2f €',
                $totalRentalFee
            ));
        }

        $entryExists = false;
        foreach ($sessionData as &$entry) {
            // Aktualisieren, falls die Kategorie und der Benutzer identisch sind
            if ($entry['userId'] === $userId && $entry['category'] === $category) {
                $entry['selectedAssets'] = array_unique(array_merge($entry['selectedAssets'] ?? [], $selectedAssets));
                if ($category === 'tl_dc_equipment_types') {
                    $entry['pid'] = $pid;
                }
                $entryExists = true;
                break;
            }
        }

        if (!$entryExists) {
            // Neuen Eintrag hinzufügen, falls keine Übereinstimmung gefunden wurde
            $sessionData[] = [
                'userId' => $userId,
                'category' => $category,
                'selectedAssets' => $selectedAssets,
                'pid' => $pid,
                'totalRentalFee' => $totalRentalFee,
            ];
        }
        // Daten in der Session speichern
        $bag->set('reservation_items', $sessionData);
    }

    /**
     * Save Data to Database
     */
    /**
     * Speichert die Session-Daten in die Datenbank.
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
            $totalRentalFee = 0; // Mietkosten berechnen


            if (empty($selectedAssets)) {
                continue; // Überspringen, wenn keine Assets ausgewählt sind
            }

            if (!$userId || !$category) {
                throw new \RuntimeException('Ungültige Session-Daten: Benutzer oder Kategorie fehlt.');
            }

            foreach ($selectedAssets as $assetId) {
                $query = $this->db->prepare("SELECT rentalFee FROM {$category} WHERE id = ?");
                $result = $query->executeQuery([$assetId])->fetchOne();
                $totalRentalFee += (float)$result; // `rentalFee` summieren
            }

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
                $query = "SELECT es.id,es.pid, es.title, es.status, es.manufacturer, es.model, es.color, es.size, es.serialNumber, es.buyDate, es.status, et.rentalFee
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
        $query = '';
        $params = [$assetId];

        switch ($category) {
            case 'tl_dc_tanks':
                $query = 'SELECT title, size, rentalFee FROM tl_dc_tanks WHERE id = ?';
                break;
            case 'tl_dc_regulators':
                $query = 'SELECT title, manufacturer, regModel1st, regModel2ndPri, regModel2ndSec, rentalFee FROM tl_dc_regulators WHERE id = ?';
                break;
            case 'tl_dc_equipment_types':
                $query = 'SELECT es.id,es.pid, es.title, es.model, es.color, es.size, et.rentalFee
                            FROM tl_dc_equipment_subtypes es
                            INNER JOIN tl_dc_equipment_types et ON es.pid = et.id
                            WHERE et.id = ?';
                break;
            default:
                return null;
        }

        $result = $this->db->fetchAssociative($query, $params);

        if ($result) {
            $helper = new DcaTemplateHelper();
            // Unterschiedliche Rückgabe je nach Kategorie formatieren
            switch ($category) {
                case 'tl_dc_tanks':
                    // Größe durch Helper-Methode auflösen
                    //$sizeMapping = $helper->getSizes();
                    $sizeText = $result['size'].'L' ?? 'Unbekannte Größe';
                    return sprintf(
                        'Tank: %s (Größe: %s), %s',
                        $result['title'] ?? 'Unbekannt',
                        $sizeText,
                        number_format((float)$result['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt' // z. B. "123.45 €"
                    );

                case 'tl_dc_regulators':
                    // Hersteller und Modelle durch Helper-Methode auflösen
                    $manufacturerMapping = $helper->getManufacturers();
                    $manufacturerText = $manufacturerMapping[$result['manufacturer']] ?? 'Unbekannter Hersteller';

                    $regModel1stMapping = $helper->getRegModels1st((int) $result['manufacturer']);
                    $regModel1stText = $regModel1stMapping[$result['regModel1st']] ?? 'N/A';

                    $regModel2ndMapping = $helper->getRegModels2nd((int) $result['manufacturer']);
                    $regModel2ndPriText = $regModel2ndMapping[$result['regModel2ndPri']] ?? 'N/A';
                    $regModel2ndSecText = $regModel2ndMapping[$result['regModel2ndSec']] ?? 'N/A';

                    return sprintf(
                        'Regulator: %s (Hersteller: %s, 1st Stage: %s, 2nd Stage (Pri): %s, 2nd Stage (Sec): %s), %s',
                        $result['title'] ?? 'Unbekannt',
                        $manufacturerText,
                        $regModel1stText,
                        $regModel2ndPriText,
                        $regModel2ndSecText,
                        number_format((float)$result['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt' // z. B. "123.45 €"
                    );

                case 'tl_dc_equipment_types':
                    // Subtypen und weitere Felder durch Helper-Methode auflösen
                    $sizeMapping = $helper->getSizes();
                    $sizeText = $sizeMapping[$result['size']] ?? 'Unbekannte Größe';

                    return sprintf(
                        'Ausrüstung: %s (Modell: %s, Größe: %s, Farbe: %s), %s',
                        $result['title'] ?? 'Unbekannt',
                        $result['model'] ?? 'Kein Modell angegeben',
                        $sizeText,
                        $result['color'] ?? 'Keine Farbe angegeben',
                        number_format((float)$result['rentalFee'], 2, '.', ',') . ' €' ?? 'Unbekannt' // z. B. "123.45 €"
                    );
            }
        }
        return null;
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
