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

class TankCheckHelper // Hilfsklasse für Berechnungen und Datenabfragen rund um den Tank-Check
{
    /**
     * Berechnet den Gesamtpreis für eine Flaschenprüfung
     *
     * @param int $proposalId ID des Angebots (tl_dc_check_proposal)
     * @param string|int $tankSize Größe der Flasche in Litern
     * @param array $selectedArticleIds IDs der zusätzlich gewählten Artikel
     * @return float
     */
    public static function calculateTotalPrice(int $proposalId, $tankSize, array $selectedArticleIds = []): float // Statische Methode zur Preisberechnung
    {
        $totalPrice = 0.0; // Initialisierung des Gesamtpreises
        $db = Database::getInstance(); // Datenbank-Instanz holen

        // 1. Grundpreis für die Flaschengröße finden
        $baseArticles = $db->prepare("SELECT articlePriceBrutto, articleSize From tl_dc_check_articles
										WHERE pid = ?
										AND articleSize != ''
										AND published = 1
										ORDER BY CAST(articleSize AS UNSIGNED)") // SQL-Abfrage für Basisartikel sortiert nach Größe
            ->execute($proposalId); // Ausführen mit der Proposal-ID

        if ($baseArticles->numRows) { // Wenn Basisartikel gefunden wurden
            $foundPrice = null; // Variable für den gefundenen Staffelpreis
            $maxSizeArticle = null; // Variable für den Artikel mit der größten Kapazität

            while ($baseArticles->next()) { // Durchlaufe alle gefundenen Artikel
                $maxSizeArticle = $baseArticles->row(); // Speichere aktuellen Datensatz als potenzielle Maximalgröße
                if ((float)$tankSize <= (float)$baseArticles->articleSize) { // Wenn die Flasche in diese Größenstaffel passt
                    $foundPrice = (float)$baseArticles->articlePriceBrutto; // Setze den entsprechenden Preis
                    break; // Beende die Suche nach dem Staffelpreis
                }
            }

            // Wenn keine passende Größe gefunden wurde (Flasche ist größer als alle Staffeln),
            // wird der Preis der größten Staffel genommen
            if ($foundPrice === null && $maxSizeArticle !== null) { // Fallback falls Flasche extrem groß ist
                $foundPrice = (float)$maxSizeArticle['articlePriceBrutto']; // Nimm den Preis der größten verfügbaren Staffel
            }

            if ($foundPrice !== null) { // Falls ein Preis ermittelt werden konnte
                $totalPrice += $foundPrice; // Addiere ihn zum Gesamtpreis
            }
        }

        // 2. Preise für Zusatzartikel addieren
        if (!empty($selectedArticleIds)) { // Falls zusätzliche Artikel (z.B. O2-Reinigung) gewählt wurden
            $articles = $db->execute("SELECT articlePriceBrutto FROM tl_dc_check_articles WHERE id IN (" . implode(',', array_map('intval', $selectedArticleIds)) . ") AND published='1'"); // Preise der Zusatzartikel abfragen
            while ($articles->next()) { // Durchlaufe alle Zusatzartikel
                $totalPrice += (float)$articles->articlePriceBrutto; // Addiere deren Preise zum Gesamtpreis
            }
        }

        return $totalPrice; // Gib den finalen berechneten Gesamtpreis zurück
    }

    /**
     * Holt alle verfügbaren Flaschen eines Mitglieds
     *
     * @param int $memberId
     * @return array
     */
    public static function getMemberTanks(int $memberId): array // Methode zum Abrufen der Flaschen eines Mitglieds
    {
        $tanks = []; // Initialisiere Rückgabe-Array
        $db = Database::getInstance(); // Datenbank-Instanz holen
        $result = $db->prepare("SELECT id, title, serialNumber, size FROM tl_dc_tanks WHERE owner=?") // SQL für Flaschen des Besitzers
            ->execute($memberId); // Ausführen mit der Mitglieds-ID

        while ($result->next()) { // Über alle gefundenen Flaschen iterieren
            $tanks[$result->id] = sprintf('%s (%s, %sL)', $result->title, $result->serialNumber, $result->size); // Formatiere Anzeige-String für Dropdowns
        }

        return $tanks; // Gib das Array mit Flaschen zurück
    }
}
