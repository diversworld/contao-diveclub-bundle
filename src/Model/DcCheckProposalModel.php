<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Model;

use Contao\CoreBundle\DependencyInjection\Attribute\AsModel;
use Contao\Model;

#[AsModel(table: 'tl_dc_check_proposal')] // Registriert die Klasse als Modell für die Prüfvorschlagstabelle
class DcCheckProposalModel extends Model // Modell-Klasse für den Zugriff auf Prüftermine/Vorschläge
{
    protected static $strTable = 'tl_dc_check_proposal'; // Name der zugrunde liegenden Tabelle in der Datenbank

    /**
     * Find a proposal by its ID or alias
     *
     * @param mixed $varId
     * @param array $arrOptions
     *
     * @return static|null
     */
    public static function findByIdOrAlias(mixed $varId, array $arrOptions = []): ?static
    {
        if (is_numeric($varId)) {
            return static::findByPk($varId, $arrOptions);
        }

        return static::findOneByAlias($varId, $arrOptions);
    }
}
