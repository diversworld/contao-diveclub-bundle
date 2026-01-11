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

        $articles = Database::getInstance()->prepare("SELECT id, title, articlePriceBrutto FROM tl_dc_check_articles WHERE pid=?")->execute($dc->activeRecord->pid);

        while ($articles->next()) {
            $options[$articles->id] = $articles->title . ' (' . $articles->articlePriceBrutto . ' €)';
        }

        return $options;
    }
}
