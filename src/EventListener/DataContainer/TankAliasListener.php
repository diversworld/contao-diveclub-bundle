<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Exception;

class TankAliasListener
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.alias.save')]
    public function __invoke(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            return (bool) $this->db->fetchOne(
                "SELECT id FROM tl_dc_tanks WHERE alias=? AND id!=?",
                [$alias, $dc->id]
            );
        };

        // Generate the alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate(
                $dc->activeRecord->title,
                [],
                $aliasExists
            );
        } elseif (preg_match('/^[1-9]\d*$/', (string) $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'] ?? 'Alias %s must not be numeric!', $varValue));
        } elseif ($aliasExists((string) $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Alias %s already exists!', $varValue));
        }

        return $varValue;
    }
}
