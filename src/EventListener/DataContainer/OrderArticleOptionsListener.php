<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_order', target: 'fields.selectedArticles.options')]
class OrderArticleOptionsListener
{
    public function __invoke(DataContainer $dc): array
    {
        $options = [];

        if (!$dc->activeRecord) {
            return $options;
        }

        // tl_dc_check_order.pid → verweist auf tl_dc_check_booking.id
        $booking = Database::getInstance()
            ->prepare("SELECT pid FROM tl_dc_check_booking WHERE id=?")
            ->execute($dc->activeRecord->pid);

        if ($booking->numRows < 1) {
            return $options; // keine zugehörige Buchung gefunden
        }

        // tl_dc_check_booking.pid → verweist auf tl_dc_check_proposal.id
        $proposalId = (int)$booking->pid;

        // Artikel zum passenden Vorschlag laden
        $articles = Database::getInstance()
            ->prepare("SELECT id, title, articlePriceBrutto FROM tl_dc_check_articles WHERE pid=?")
            ->execute($proposalId);

        while ($articles->next()) {
            $options[$articles->id] = $articles->title . ' (' . number_format((float)$articles->articlePriceBrutto, 2, ',', '.') . ' €)';
        }

        return $options;
    }
}
