<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Model;

use Contao\CoreBundle\DependencyInjection\Attribute\AsModel;
use Contao\Model;
use Contao\System;

#[AsModel(table: 'tl_dc_config')] // Registriert die Klasse als Contao-Modell für die Konfigurationstabelle
class DcConfigModel extends Model // Basis-Klasse für den Zugriff auf Konfigurationseinstellungen
{
    protected static $strTable = 'tl_dc_config'; // Name der zugehörigen Datenbanktabelle
}
