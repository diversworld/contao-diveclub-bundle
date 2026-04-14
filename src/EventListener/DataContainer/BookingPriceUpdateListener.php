<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;
use Contao\StringUtil;

class BookingPriceUpdateListener
{
    #[AsCallback(table: 'tl_dc_check_order', target: 'config.onsubmit')]
    public function onOrderSubmit(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
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

        // 1. Basispreis für die Flaschengröße (size) ermitteln
        if ($dc->activeRecord->size) {
            // Die zugehörige Buchung finden, um die proposalId (pid) zu erhalten
            $booking = $db->prepare("SELECT pid FROM tl_dc_check_booking WHERE id=?")
                ->execute($dc->activeRecord->pid);

            if ($booking->next()) {
                $proposalId = (int)$booking->pid;

                // Passenden Artikel für diese Größe in diesem Angebot finden
                // Wir suchen nach einem Artikel, dessen articleSize mit der size der Order übereinstimmt
                $baseArticle = $db->prepare("SELECT articlePriceBrutto FROM tl_dc_check_articles WHERE pid=? AND articleSize=?")
                    ->execute($proposalId, $dc->activeRecord->size);

                if ($baseArticle->next()) {
                    $totalPrice += (float)$baseArticle->articlePriceBrutto;
                }
            }
        }

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
