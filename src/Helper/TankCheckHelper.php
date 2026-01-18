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

namespace Diversworld\ContaoDiveclubBundle\Helper;

use Contao\Database;

class TankCheckHelper
{
    /**
     * Berechnet den Gesamtpreis für eine Flaschenprüfung
     *
     * @param int $proposalId ID des Angebots (tl_dc_check_proposal)
     * @param string|int $tankSize Größe der Flasche in Litern
     * @param array $selectedArticleIds IDs der zusätzlich gewählten Artikel
     * @return float
     */
    public static function calculateTotalPrice(int $proposalId, $tankSize, array $selectedArticleIds = []): float
    {
        $totalPrice = 0.0;
        $db = Database::getInstance();

        // 1. Grundpreis für die Flaschengröße finden
        $baseArticles = $db->prepare("SELECT articlePriceBrutto, articleSize From tl_dc_check_articles
										WHERE pid = ?
										AND articleSize != ''
										AND published = 1
										ORDER BY CAST(articleSize AS UNSIGNED)")
            ->execute($proposalId);

        if ($baseArticles->numRows) {
            $foundPrice = null;
            $maxSizeArticle = null;

            while ($baseArticles->next()) {
                $maxSizeArticle = $baseArticles->row();
                if ((float)$tankSize <= (float)$baseArticles->articleSize) {
                    $foundPrice = (float)$baseArticles->articlePriceBrutto;
                    break;
                }
            }

            // Wenn keine passende Größe gefunden wurde (Flasche ist größer als alle Staffeln),
            // wird der Preis der größten Staffel genommen
            if ($foundPrice === null && $maxSizeArticle !== null) {
                $foundPrice = (float)$maxSizeArticle['articlePriceBrutto'];
            }

            if ($foundPrice !== null) {
                $totalPrice += $foundPrice;
            }
        }

        // 2. Preise für Zusatzartikel addieren
        if (!empty($selectedArticleIds)) {
            $articles = $db->execute("SELECT articlePriceBrutto FROM tl_dc_check_articles WHERE id IN (" . implode(',', array_map('intval', $selectedArticleIds)) . ") AND published='1'");
            while ($articles->next()) {
                $totalPrice += (float)$articles->articlePriceBrutto;
            }
        }

        return $totalPrice;
    }

    /**
     * Holt alle verfügbaren Flaschen eines Mitglieds
     *
     * @param int $memberId
     * @return array
     */
    public static function getMemberTanks(int $memberId): array
    {
        $tanks = [];
        $db = Database::getInstance();
        $result = $db->prepare("SELECT id, title, serialNumber, size FROM tl_dc_tanks WHERE owner=?")
            ->execute($memberId);

        while ($result->next()) {
            $tanks[$result->id] = sprintf('%s (%s, %sL)', $result->title, $result->serialNumber, $result->size);
        }

        return $tanks;
    }
}
