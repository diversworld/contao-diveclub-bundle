<?php

declare(strict_types=1);

namespace ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;

#[AsCallback(table: 'tl_dc_check_order', target: 'fields.size.options')]
class OrderSizeOptionsListener
{
    /**
     * Provide size options based on language file keys
     */
    public function __invoke(DataContainer $dc): array
    {
        // Ensure language file is loaded
        System::loadLanguageFile('tl_dc_check_order');

        $sizes = $GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'] ?? null;
        if (\is_array($sizes)) {
            return array_keys($sizes);
        }

        // Fallback to a safe default set to avoid runtime errors if language is not initialized
        return ['1', '2', '3', '4', '5', '6', '7', '8', '10', '12', '15', '18', '20', '11', '22'];
    }
}
