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

        // Alle Artikel für dieses Angebot laden, um Basisartikel und Pflichtartikel zu identifizieren
        $articles = $db->prepare("SELECT id, articleSize, `default` FROM tl_dc_check_articles WHERE pid=?")
            ->execute($proposalId);

        $articleIdsToSelect = [];
        $sizeArticleIds = [];
        $bestMatchingArticleId = null;
        $minMatchingSize = 999999;

        while ($articles->next()) {
            $currentArticleSize = (float)str_replace(',', '.', $articles->articleSize);
            $targetSize = (float)str_replace(',', '.', $size);

            // 1. Identifiziere alle größenabhängigen Artikel
            if ($articles->articleSize !== '') {
                $sizeArticleIds[] = (int)$articles->id;

                // 2. Suche den bestmöglichen Basisartikel (kleinste articleSize >= targetSize)
                if ($currentArticleSize >= $targetSize && $currentArticleSize < $minMatchingSize) {
                    $minMatchingSize = $currentArticleSize;
                    $bestMatchingArticleId = (int)$articles->id;
                }
            }

            // 3. Pflichtartikel (default) vormerken
            if ($articles->default) {
                $articleIdsToSelect[] = (int)$articles->id;
            }
        }

        // Falls kein exakt passender gefunden wurde, nehmen wir den größten verfügbaren als Fallback?
        // Oder die Anforderung sagt: "Der Preis bis 10L gilt für die Volumen größer gleich 10 Liter."
        // Das ist etwas widersprüchlich zum Beispiel davor: "bis 8 Liter gilt für alle volumen bis 8Liter".
        // Meistens ist es: "Nimm den kleinsten Artikel, dessen 'Größe bis' >= gewählte Größe ist".
        if ($bestMatchingArticleId) {
            $articleIdsToSelect[] = $bestMatchingArticleId;
        }

        $selectedArticles = StringUtil::deserialize($objOrder->selectedArticles, true);
        $originalSelectedArticles = $selectedArticles;

        $hasChanges = false;

        // Falls size im POST anders ist als in der DB, müssen wir size auch speichern
        if (Input::post('size') !== null && Input::post('size') !== $objOrder->size) {
            $db->prepare("UPDATE tl_dc_check_order SET size=? WHERE id=?")
                ->execute(Input::post('size'), $dc->id);
            $hasChanges = true;
        }

        // Berechne die neue Auswahl
        // a) Entferne alle alten größenabhängigen Artikel, die NICHT der neue bestMatchingArticleId sind
        $selectedArticles = array_diff($selectedArticles, $sizeArticleIds);

        // b) Füge die neuen gewünschten Artikel hinzu (Basisartikel + Pflichtartikel)
        foreach ($articleIdsToSelect as $id) {
            if (!\in_array($id, $selectedArticles, true)) {
                $selectedArticles[] = $id;
            }
        }

        // Sortierung beibehalten oder normalisieren
        sort($selectedArticles);
        $oldSorted = $originalSelectedArticles;
        sort($oldSorted);

        if ($selectedArticles !== $oldSorted) {
            $db->prepare("UPDATE tl_dc_check_order SET selectedArticles=? WHERE id=?")
                ->execute(serialize($selectedArticles), $dc->id);

            $hasChanges = true;
        }

        if ($hasChanges) {
            // Den Preislistener triggern, damit die Preise in tl_dc_check_order und tl_dc_check_booking aktuell sind
            $container = System::getContainer();
            if ($container->has(BookingPriceUpdateListener::class)) {
                $priceListener = $container->get(BookingPriceUpdateListener::class);
                if ($priceListener instanceof BookingPriceUpdateListener) {
                    // Ein temporäres DataContainer-Objekt erstellen, da wir im onload sind
                    $priceListener->onOrderSubmit($dc);
                }
            }

            // Seite neu laden, damit die Checkbox im Formular markiert ist
            Controller::reload();
        }
    }
}
