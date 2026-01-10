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
use Contao\Controller;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Helper\TankCheckHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                $params = '/' . ($useAutoItem ? '' : '/') . $item;
                $data['url'] = $currentPage ? $currentPage->getFrontendUrl($params) : '';

                $proposalList[] = $data;
            }
        }

        $template->proposals = $proposalList;

        return $template->getResponse();
    }

    private function handleBooking(FragmentTemplate $template, DcCheckProposalModel $proposal, ?FrontendUser $user, Request $request, ModuleModel $model): Response
    {
        $template->isBooking = true;
        $template->proposal = $proposal;
        $template->user = $user;
        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

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
        $articles = $db->prepare("SELECT id, title, articlePriceBrutto, articleSize FROM tl_dc_check_articles WHERE pid=? AND published='1'")
            ->execute($proposal->id);

        $articleList = [];
        $basePrices = [];
        while ($articles->next()) {
            if ($articles->articleSize) {
                $basePrices[$articles->articleSize] = (float)$articles->articlePriceBrutto;
            } else {
                $articleList[] = $articles->row();
            }
        }
        $template->articles = $articleList;
        $template->basePricesJson = json_encode($basePrices);

        // Flaschengrößen für das Formular
        System::loadLanguageFile('tl_dc_tanks');
        $template->tankSizes = $GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'] ?? [];

        // Sicherstellen, dass die MSC-Labels geladen sind
        System::loadLanguageFile('default');

        if ($request->isMethod('POST') && $request->request->get('FORM_SUBMIT') === 'dc_tank_check_booking') {
            // Buchung speichern
            $order = new DcCheckOrderModel();
            $order->pid = $proposal->id;
            $order->tstamp = time();

            if ($user) {
                $order->memberId = $user->id;
                $order->firstname = $user->firstname;
                $order->lastname = $user->lastname;
                $order->email = $user->email;
                $order->phone = $user->phone ?: ($user->mobile ?: '');
            } else {
                $order->memberId = 0;
                $order->firstname = $request->request->get('firstname');
                $order->lastname = $request->request->get('lastname');
                $order->email = $request->request->get('email');
                $order->phone = $request->request->get('phone');
            }

            $order->tankId = $request->request->get('tankId') ?: 0;

            // Wenn keine tankId (Gast), dann tankData speichern (falls im Formular vorgesehen)
            if (!$order->tankId) {
                $order->tankData = $request->request->get('tankData');
                $order->serialNumber = $request->request->get('serialNumber');
                $order->manufacturer = $request->request->get('manufacturer');
                $order->bazNumber = $request->request->get('bazNumber');
                $order->size = $request->request->get('tankSize');
            }

            $order->o2clean = $request->request->get('o2clean') ? '1' : '';
            $order->notes = $request->request->get('notes');

            $order->selectedArticles = serialize($request->request->all('articles'));

            // Preis berechnen
            $tankSize = $request->request->get('tankSize') ?: "12";
            if ($order->tankId) {
                $tank = $db->prepare("SELECT size FROM tl_dc_tanks WHERE id=?")->execute($order->tankId);
                if ($tank->numRows) {
                    $tankSize = (string)$tank->size;
                }
            }

            $selectedArticles = $request->request->all('articles');
            $order->totalPrice = TankCheckHelper::calculateTotalPrice((int)$proposal->id, $tankSize, $selectedArticles);
            $order->status = 'ordered';
            $order->save();

            // E-Mail-Versand
            $this->sendConfirmationEmail($order, $proposal, $model);

            // Weiterleitung oder Erfolgsmeldung
            if ($model->jumpTo) {
                $page = PageModel::findByPk($model->jumpTo);
                if ($page) {
                    // Order ID in Session speichern für Insert-Tags auf der Zielseite
                    $request->getSession()->set('last_tank_check_order', $order->id);
                    return new Response('', 303, ['Location' => $page->getFrontendUrl()]);
                }
            }

            $template->success = true;
            $template->order = $order;
        }

        return $template->getResponse();
    }

    private function sendConfirmationEmail(DcCheckOrderModel $order, DcCheckProposalModel $proposal, ModuleModel $model): void
    {
        if (!$model->reg_notification) {
            return;
        }

        $email = new Email();
        $email->from = $GLOBALS['TL_ADMIN_EMAIL'];
        $email->fromName = $GLOBALS['TL_ADMIN_NAME'];
        $parser = System::getContainer()->get('contao.insert_tag.parser');
        $email->subject = $parser->replace($model->reg_subject ?: 'Bestätigung Ihrer TÜV-Prüfung');

        $text = $model->reg_text ?: "Vielen Dank für Ihre Buchung.\n\nGesamtpreis: {{tank_check_order::totalPrice}} €";

        // Wir müssen hier die Insert-Tags manuell ersetzen, da wir uns im Backend-Kontext des Mailversands befinden könnten
        // oder die Order-ID übergeben müssen.
        // Einfacher: Wir setzen die Order-ID in die Session und nutzen den Parser
        System::getContainer()->get('request_stack')->getCurrentRequest()->getSession()->set('last_tank_check_order', $order->id);

        $email->text = $parser->replace($text);

        $recipients = StringUtil::splitCsv($model->reg_notification);
        $email->sendTo($recipients);

        // Kopie an den Nutzer
        if ($order->email) {
            $email->sendTo($order->email);
        }
    }
}
