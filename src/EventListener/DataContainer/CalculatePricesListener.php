<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;

class CalculatePricesListener
{
    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.articlePriceNetto.save')]
    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.articlePriceBrutto.save')]
    public function __invoke(mixed $varValue, DataContainer $dc): mixed
    {
        // Fall: Netto-Wert wurde eingegeben
        if ($dc->field === 'articlePriceNetto') {
            $priceNetto = (float)$varValue; // Netto-Wert speichern
            $priceBrutto = round($priceNetto * 1.19, 2); // Brutto berechnen

            // Synchronisierung über activeRecord
            $dc->activeRecord->articlePriceBrutto = $priceBrutto;

            // Preise speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceBrutto=? WHERE id=?")
                ->execute($priceBrutto, $dc->id);

        } elseif ($dc->field === 'articlePriceBrutto') {
            // Fall: Brutto-Wert wurde eingegeben
            $priceBrutto = (float)$varValue; // Brutto-Wert speichern
            $priceNetto = round($priceBrutto / 1.19, 2); // Netto berechnen

            // Synchronisierung über activeRecord
            $dc->activeRecord->articlePriceNetto = $priceNetto;

            // Preise speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceNetto=? WHERE id=?")
                ->execute($priceNetto, $dc->id);
        }

        // Rückgabe des aktuellen Feldes: Immer den eingegebenen Wert zurückgeben
        return $varValue;
    }
}
