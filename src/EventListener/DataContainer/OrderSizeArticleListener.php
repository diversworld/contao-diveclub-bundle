<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;
use Contao\Input;
use Contao\Controller;
use Contao\StringUtil;
use Contao\System;

#[AsCallback(table: 'tl_dc_check_order', target: 'config.onload')]
class OrderSizeArticleListener
{
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id || 'edit' !== Input::get('act')) {
            return;
        }

        $db = Database::getInstance();
        $objOrder = $db->prepare("SELECT pid, size, selectedArticles FROM tl_dc_check_order WHERE id=?")
            ->limit(1)
            ->execute($dc->id);

        if ($objOrder->numRows < 1) {
            return;
        }

        $size = $objOrder->size;

        // Falls size im POST steht (durch submitOnChange), hat POST Priorität
        if (Input::post('size') !== null) {
            $size = Input::post('size');
        }

        if (!$size) {
            return;
        }

        // Die zugehörige Buchung finden, um die proposalId (pid) zu erhalten
        $booking = $db->prepare("SELECT pid FROM tl_dc_check_booking WHERE id=?")
            ->execute($objOrder->pid);

        if (!$booking->next()) {
            return;
        }

        $proposalId = (int)$booking->pid;

        // Passenden Artikel für diese Größe in diesem Angebot finden
        $baseArticle = $db->prepare("SELECT id FROM tl_dc_check_articles WHERE pid=? AND articleSize=?")
            ->execute($proposalId, $size);

        if (!$baseArticle->next()) {
            return;
        }

        $articleId = (int)$baseArticle->id;
        $selectedArticles = StringUtil::deserialize($objOrder->selectedArticles, true);

        // Prüfen, ob der Artikel bereits ausgewählt ist
        if (!\in_array($articleId, $selectedArticles, true)) {

            // Optional: Alte Größen-Artikel entfernen?
            // Da wir nicht wissen, welche Artikel "Größen-Artikel" sind, außer durch erneutes Suchen:
            $sizeArticles = $db->prepare("SELECT id FROM tl_dc_check_articles WHERE pid=? AND articleSize != ''")
                ->execute($proposalId);

            $sizeArticleIds = $sizeArticles->fetchEach('id');

            // Entferne alle anderen Größen-Artikel aus der Auswahl
            $selectedArticles = array_diff($selectedArticles, $sizeArticleIds);

            // Füge den neuen hinzu
            $selectedArticles[] = $articleId;

            $db->prepare("UPDATE tl_dc_check_order SET selectedArticles=? WHERE id=?")
                ->execute(serialize($selectedArticles), $dc->id);

            // Den Preislistener triggern, damit die Preise in tl_dc_check_order und tl_dc_check_booking aktuell sind
            $priceListener = System::getContainer()->get(BookingPriceUpdateListener::class);
            if ($priceListener instanceof BookingPriceUpdateListener) {
                // Ein temporäres DataContainer-Objekt erstellen, da wir im onload sind
                $priceListener->onOrderSubmit($dc);
            }

            // Seite neu laden, damit die Checkbox im Formular markiert ist
            Controller::reload();
        }
    }
}
