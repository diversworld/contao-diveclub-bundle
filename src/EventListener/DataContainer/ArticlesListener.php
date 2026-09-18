<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class ArticlesListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection $db,
        private readonly Slug       $slug,
    )
    {
    }

    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.alias.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_check_articles');
    }

    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.articlePriceNetto.save')]
    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.articlePriceBrutto.save')]
    public function calculatePrices(mixed $varValue, DataContainer $dc): mixed
    {
        $price = (float)$varValue;
        $isNetto = ($dc->field === 'articlePriceNetto');

        $priceNetto = $isNetto ? $price : round($price / 1.19, 2);
        $priceBrutto = $isNetto ? round($price * 1.19, 2) : $price;

        $targetField = $isNetto ? 'articlePriceBrutto' : 'articlePriceNetto';
        $targetValue = $isNetto ? $priceBrutto : $priceNetto;

        // Synchronisierung über activeRecord
        $dc->activeRecord->{$targetField} = $targetValue;

        // Preise speichern
        $this->db->executeStatement(
            "UPDATE tl_dc_check_articles SET $targetField=? WHERE id=?",
            [$targetValue, $dc->id]
        );

        return $varValue;
    }
}
