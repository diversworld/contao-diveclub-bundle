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
        $baseArticle = $db->prepare("SELECT articlePriceBrutto From tl_dc_check_articles
										WHERE (
											(`articleSize` = '8' AND ? <= 8) OR
											(`articleSize` = '10' AND ? <= 10) OR
											(`articleSize` = '80' AND ? > 10)
										)
										AND published = 1
										AND pid = ?
										ORDER BY 
											CASE 
												WHEN `articleSize` = '8' THEN 1
												WHEN `articleSize` = '10' THEN 2
												WHEN `articleSize` = '80' THEN 3
												ELSE 4
										  	END")
            ->limit(1)
            ->execute((string)$tankSize,(string)$tankSize,(string)$tankSize,$proposalId);

        if ($baseArticle->numRows) {
            $totalPrice += (float)$baseArticle->articlePriceBrutto;
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
