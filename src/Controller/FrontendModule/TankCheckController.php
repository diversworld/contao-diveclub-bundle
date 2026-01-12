<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CalendarEventsModel;
use Contao\Date;
use Contao\Input;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\FrontendUser;
use Contao\Database;
use Contao\Email;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Helper\TankCheckHelper;
use Diversworld\ContaoDiveclubBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function is_array;

#[AsFrontendModule('dc_tank_check', category: 'dc_manager', template: 'frontend_module/mod_dc_tank_check')]
class TankCheckController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        /** @var FrontendUser|null $user */
        $user = System::getContainer()->get('security.helper')->getUser();

        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';
        $template->type = $model->type;

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        } else {
            $template->headline = ['text' => '', 'tag_name' => 'h1'];
        }

        $template->user = $user;
        $template->isLoggedIn = ($user instanceof FrontendUser);
        $template->success = false;
        $template->isBooking = false;
        $template->order = null;
        $template->proposal = null;
        $template->tanks = [];
        $template->articles = [];
        $template->basePricesJson = json_encode([]);
        $template->userTanksJson = json_encode([]);
        $template->proposals = [];

        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // Detailansicht (Buchungsformular)
        $proposalAlias = Input::get('auto_item') ?: $request->get('item');

        if ($proposalAlias) {
            $proposal = DcCheckProposalModel::findByIdOrAlias($proposalAlias);
            if ($proposal) {
                return $this->handleBooking($template, $proposal, $user, $request, $model);
            }
        }

        // Liste der Angebote (Termine) laden
        $proposals = DcCheckProposalModel::findBy(['published=?'], [1], ['order' => 'proposalDate DESC']);
        $proposalList = [];

        $dateFormat = Config::get('datimFormat');
        $useAutoItem = (bool)Config::get('useAutoItem');
        $currentPage = $request->attributes->get('pageModel');

        if ($proposals) {
            foreach ($proposals as $proposal) {
                $data = $proposal->row();
                $data['eventDate'] = '';
                $data['eventTitle'] = '';

                if ($proposal->checkId) {
                    $event = CalendarEventsModel::findByPk($proposal->checkId);
                    if ($event && $event->startDate) {
                        $data['eventDate'] = Date::parse($dateFormat, (int)$event->startDate);
                        $data['eventTitle'] = $event->title;
                    }
                }

                // URL generieren
                $item = $proposal->alias ?: (string)$proposal->id;
                // Korrektur: Wir nutzen die Contao-Logik für Auto-Items sauberer
                if ($useAutoItem) {
                    $params = '/' . $item;
                } else {
                    $params = '/items/' . $item; // Oder was auch immer als Schlüssel definiert ist
                }
                $data['url'] = $currentPage ? $currentPage->getFrontendUrl($params) : '';

                $proposalList[] = $data;
            }
        }

        $template->proposals = $proposalList;

        // Sprachdatei laden und Labels an das Template für die Liste übergeben
        System::loadLanguageFile('default');
        $template->labels = $GLOBALS['TL_LANG']['MSC']['dc_tank_check'] ?? [];

        return $template->getResponse();
    }

    private function handleBooking(FragmentTemplate $template, DcCheckProposalModel $proposal, ?FrontendUser $user, Request $request, ModuleModel $model): Response
    {
        $template->isBooking = true;
        $template->proposal = $proposal;
        $template->user = $user;
        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $session = $request->getSession();
        $bag = $session->getBag(ArrayAttributeBag::ATTRIBUTE_NAME);
        $sessionTanks = $bag->get('tank_check_items', []);

        if ($user) {
            $template->tanks = TankCheckHelper::getMemberTanks((int)$user->id);
            // Tank-Daten für JS-Preisberechnung
            $db = Database::getInstance();
            $userTanks = $db->prepare("SELECT id, size FROM tl_dc_tanks WHERE owner=?")->execute($user->id)->fetchAllAssoc();
            $template->userTanksJson = json_encode($userTanks);
        } else {
            $template->tanks = [];
            $template->userTanksJson = '[]';
        }

        // Artikel aus dem Angebot laden
        $db = Database::getInstance();
        $articles = $db->prepare("SELECT id, title, articlePriceBrutto, articleSize, `default` FROM tl_dc_check_articles WHERE pid=? AND published='1'")
            ->execute($proposal->id);

        $articleList = [];
        $basePrices = [];
        while ($articles->next()) {
            $articleData = $articles->row();
            // Sicherstellen, dass default ein boolean ist für Twig
            $articleData['default'] = (bool)$articleData['default'];

            if ($articles->articleSize && $articles->articleSize !== '') {
                $basePrices[$articles->articleSize] = (float)$articles->articlePriceBrutto;
            } else {
                $articleList[] = $articleData;
            }
        }
        $template->articles = $articleList;
        $template->basePricesJson = json_encode($basePrices);

        // Flaschengrößen für das Formular
        System::loadLanguageFile('tl_dc_tanks');
        $template->tankSizes = $GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'] ?? [];

        // Sprachdatei laden und Labels an das Template übergeben
        System::loadLanguageFile('default');
        $template->labels = $GLOBALS['TL_LANG']['MSC']['dc_tank_check'] ?? [];

        // Aktion: Flasche entfernen
        if ($request->get('act') === 'remove' && $request->get('idx') !== null) {
            $idx = (int)$request->get('idx');
            if (isset($sessionTanks[$idx])) {
                unset($sessionTanks[$idx]);
                $sessionTanks = array_values($sessionTanks);
                $bag->set('tank_check_items', $sessionTanks);
            }
            return new RedirectResponse($request->getPathInfo());
        }

        if ($request->isMethod('POST')) {
            // Aktion: Flasche vormerken
            if ($request->request->get('FORM_SUBMIT') === 'dc_tank_check_add') {
                $tankData = $request->request->all('tank');
                $selectedArticles = $request->request->all('articles');

                // Sicherstellen, dass alle Default-Artikel enthalten sind
                foreach ($template->articles as $art) {
                    if ($art['default'] && !in_array((string)$art['id'], array_map('strval', $selectedArticles), true)) {
                        $selectedArticles[] = (string)$art['id'];
                    }
                }

                $tankData['articles'] = $selectedArticles;
                $sessionTanks[] = $tankData;
                $bag->set('tank_check_items', $sessionTanks);

                return new RedirectResponse($request->getPathInfo());
            }

            // Aktion: Verbindlich buchen
            if ($request->request->get('FORM_SUBMIT') === 'dc_tank_check_booking') {
                if (empty($sessionTanks)) {
                    return new RedirectResponse($request->getPathInfo());
                }

                $db = Database::getInstance();
                $bookingDate = time();
                $bookingNumber = 'TC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

                // Buchungskopf erstellen
                $booking = new DcCheckBookingModel();
                $booking->pid = $proposal->id;
                $booking->tstamp = time();
                $booking->bookingDate = $bookingDate;
                $booking->bookingNumber = $bookingNumber;
                $booking->status = 'ordered';
                $booking->notes = $request->request->get('notes');

                if ($user) {
                    $booking->memberId = $user->id;
                    $booking->firstname = $user->firstname;
                    $booking->lastname = $user->lastname;
                    $booking->email = $user->email;
                    $booking->phone = $user->phone ?: ($user->mobile ?: '');
                } else {
                    $booking->memberId = 0;
                    $booking->firstname = $request->request->get('firstname');
                    $booking->lastname = $request->request->get('lastname');
                    $booking->email = $request->request->get('email');
                    $booking->phone = $request->request->get('phone');
                }

                $booking->save();

                $totalOrderPrice = 0.0;
                $orders = [];

                foreach ($sessionTanks as $tankData) {
                    $order = new DcCheckOrderModel();
                    $order->pid = $booking->id;
                    $order->tstamp = time();

                    // Wir behalten die bookingId für Rückwärtskompatibilität oder interne Referenz, falls gewünscht
                    $order->bookingId = $bookingNumber;

                    $order->tankId = $tankData['tankId'] ?? 0;
                    $tankSize = $tankData['tankSize'] ?? "12";

                    if ($order->tankId) {
                        $tank = $db->prepare("SELECT * FROM tl_dc_tanks WHERE id=?")->execute($order->tankId);
                        if ($tank->numRows) {
                            $tankSize = (string)$tank->size;
                            $order->serialNumber = $tank->serialNumber;
                            $order->manufacturer = $tank->manufacturer;
                            $order->bazNumber = $tank->bazNumber;
                            $order->size = $tank->size;
                        }
                    } else {
                        $order->serialNumber = $tankData['serialNumber'] ?? '';
                        $order->manufacturer = $tankData['manufacturer'] ?? '';
                        $order->bazNumber = $tankData['bazNumber'] ?? '';
                        $order->size = $tankSize;
                        $order->tankData = $tankData['tankData'] ?? '';
                    }

                    $order->o2clean = isset($tankData['o2clean']) ? '1' : '0';
                    $order->notes = $booking->notes;

                    $selectedArticles = $tankData['articles'] ?? [];
                    $order->selectedArticles = serialize($selectedArticles);

                    $order->totalPrice = TankCheckHelper::calculateTotalPrice((int)$proposal->id, $tankSize, $selectedArticles);
                    $order->status = 'ordered';
                    $order->save();

                    $totalOrderPrice += (float)$order->totalPrice;
                    $orders[] = $order;
                }

                // Gesamtpreis im Buchungskopf aktualisieren
                $booking->totalPrice = $totalOrderPrice;
                $booking->save();

                // E-Mail-Versand
                $this->sendConfirmationEmail($orders, $proposal, $model);

                // Session leeren
                $bag->remove('tank_check_items');

                // Weiterleitung oder Erfolgsmeldung
                if ($model->jumpTo) {
                    $page = PageModel::findByPk($model->jumpTo);
                    if ($page) {
                        $request->getSession()->set('last_tank_check_order', $orders[0]->id);
                        return new Response('', 303, ['Location' => $page->getFrontendUrl()]);
                    }
                }

                $template->success = true;
                $template->totalPrice = $totalOrderPrice;
                $template->orders = $orders;
                $template->order = $orders[0];
            }
        }

        // Session-Tanks für Anzeige aufbereiten
        $displayTanks = [];
        foreach ($sessionTanks as $index => $st) {
            $st['index'] = $index;
            $tankId = $st['tankId'] ?? 0;
            $st['price'] = TankCheckHelper::calculateTotalPrice((int)$proposal->id, $tankId ? $this->getTankSizeFromId($tankId) : ($st['tankSize'] ?? "12"), $st['articles'] ?? []);

            if ($tankId) {
                $tank = $db->prepare("SELECT title, serialNumber FROM tl_dc_tanks WHERE id=?")->execute($tankId);
                $st['displayName'] = $tank->numRows ? $tank->title . ' (' . $tank->serialNumber . ')' : 'Unbekannt';
            } else {
                $st['displayName'] = ($st['serialNumber'] ?: 'Unbekannte Flasche') . ' (' . ($st['tankSize'] ?? '12') . 'L)';
            }
            $displayTanks[] = $st;
        }
        $template->sessionTanks = $displayTanks;

        return $template->getResponse();
    }

    private function getTankSizeFromId($tankId): string
    {
        $db = Database::getInstance();
        $size = $db->prepare("SELECT size FROM tl_dc_tanks WHERE id=?")->execute($tankId)->size;
        return (string)($size ?: "12");
    }

    /**
     * @param DcCheckOrderModel[] $orders
     */
    private function sendConfirmationEmail(array $orders, DcCheckProposalModel $proposal, ModuleModel $model): void
    {
        if (!$model->reg_notification) {
            return;
        }

        $firstOrder = $orders[0];
        $totalPrice = 0;
        $tankDetails = "";

        foreach ($orders as $order) {
            $totalPrice += (float)$order->totalPrice;
            $tankDetails .= "- " . ($order->serialNumber ?: 'Unbekannt') . " (" . $order->size . "L): " . number_format((float)$order->totalPrice, 2, ',', '.') . " €\n";
        }

        $email = new Email();
        $email->from = $GLOBALS['TL_ADMIN_EMAIL'];
        $email->fromName = $GLOBALS['TL_ADMIN_NAME'];
        $parser = System::getContainer()->get('contao.insert_tag.parser');
        $email->subject = $parser->replace($model->reg_subject ?: 'Bestätigung Ihrer TÜV-Prüfung');

        $text = $model->reg_text ?: "Vielen Dank für Ihre Buchung.\n\nFlaschen:\n" . $tankDetails . "\nGesamtpreis: " . number_format($totalPrice, 2, ',', '.') . " €";

        // Wir setzen die Order-ID der ersten Flasche in die Session für Kompatibilität mit Insert-Tags
        System::getContainer()->get('request_stack')->getCurrentRequest()->getSession()->set('last_tank_check_order', $firstOrder->id);

        $email->text = $parser->replace($text);

        $recipients = StringUtil::splitCsv($model->reg_notification);
        $email->sendTo($recipients);

        // Kopie an den Nutzer
        if ($firstOrder->email) {
            $email->sendTo($firstOrder->email);
        }
    }
}
