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

use Contao\Model;
use Contao\Model\Collection;

class DcTanksModel extends Model
{
    protected static $strTable = 'tl_dc_tanks';

    public static function findAvailable(): Model|Collection|null
    {
        return self::findBy('status', 'available');
    }

    public static function findPublished(): Model|Collection|null
    {
        return self::findBy('published', 1);
    }
}
