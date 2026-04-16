<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;
use Contao\Input;
use Contao\StringUtil;

class BookingPriceUpdateListener
{
    #[AsCallback(table: 'tl_dc_check_order', target: 'config.onsubmit')]
    public function onOrderSubmit(DataContainer $dc): void
    {
        // Wenn kein activeRecord vorhanden ist (z.B. bei manuellem Aufruf aus onload), laden wir ihn
        if (!$dc->activeRecord) {
            $objOrder = Database::getInstance()
                ->prepare("SELECT * FROM tl_dc_check_order WHERE id=?")
                ->limit(1)
                ->execute($dc->id);

            if ($objOrder->numRows < 1) {
                return;
            }

            $dc->activeRecord = $objOrder;
        }

        // Falls wir im onload sind und Daten per POST kommen, aktualisieren wir den activeRecord für die Preisberechnung
        if (null !== Input::post('size')) {
            $dc->activeRecord->size = Input::post('size');
        }

        if (null !== Input::post('selectedArticles')) {
            $selected = Input::post('selectedArticles');
            // Checkboxen senden ein Array
            if (\is_array($selected)) {
                $dc->activeRecord->selectedArticles = serialize($selected);
            }
        }

        // Falls wir bereits Änderungen in der DB gespeichert haben (z.B. im OrderSizeArticleListener), laden wir den aktuellen Stand
        $objCurrent = Database::getInstance()
            ->prepare("SELECT size, selectedArticles FROM tl_dc_check_order WHERE id=?")
            ->limit(1)
            ->execute($dc->id);

        if ($objCurrent->numRows > 0) {
            $dc->activeRecord->size = $objCurrent->size;
            $dc->activeRecord->selectedArticles = $objCurrent->selectedArticles;
        }

        $this->updateOrderPrice($dc);
        $this->updateBookingPrice((int)$dc->activeRecord->pid);
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'config.ondelete')]
    public function onOrderDelete(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $this->updateBookingPrice((int)$dc->activeRecord->pid);
    }

    private function updateOrderPrice(DataContainer $dc): void
    {
        $db = Database::getInstance();
        $totalPrice = 0.0;

        // 2. Preise der zusätzlich gewählten Artikel addieren
        $selected = StringUtil::deserialize($dc->activeRecord->selectedArticles, true);

        if (!empty($selected)) {
            $articles = $db->prepare("SELECT SUM(articlePriceBrutto) AS total FROM tl_dc_check_articles WHERE id IN (" . implode(',', array_map('intval', $selected)) . ")")
                ->execute();

            if ($articles->next()) {
                $totalPrice += (float)$articles->total;
            }
        }

        $db->prepare("UPDATE tl_dc_check_order SET totalPrice=? WHERE id=?")
            ->execute($totalPrice, $dc->id);

        // Den Wert auch im activeRecord aktualisieren, damit er in der UI sofort sichtbar ist (falls möglich)
        $dc->activeRecord->totalPrice = $totalPrice;
    }

    private function updateBookingPrice(int $bookingId): void
    {
        $db = Database::getInstance();

        // 1. Alle Orders dieser Buchung holen
        $orders = $db->prepare("SELECT totalPrice FROM tl_dc_check_order WHERE pid=?")
            ->execute($bookingId);

        $totalPrice = 0.0;

        while ($orders->next()) {
            $totalPrice += (float)$orders->totalPrice;
        }

        // 2. Buchung aktualisieren
        $db->prepare("UPDATE tl_dc_check_booking SET totalPrice=? WHERE id=?")
            ->execute($totalPrice, $bookingId);
    }
}
