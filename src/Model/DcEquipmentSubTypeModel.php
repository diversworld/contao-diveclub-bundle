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

use Contao\Model;
use Contao\Model\Collection;
use Contao\System;

class DcEquipmentSubTypeModel extends Model
{
    protected static $strTable = 'tl_dc_equipment_subtypes';

    public static function findAvailable(): Model|Collection|null
    {
        return self::findBy('status', 'available');
    }

    public static function findPublished(): Model|Collection|null
    {
        return self::findBy('published', 1);
    }

    /**
     * Find available equipment subtypes with join on equipment types.
     *
     * @return array|null
     */
    public static function findAvailableWithJoin(): ?array
    {
        $db = System::getContainer()->get('database_connection'); // Contao Datenbankdienst

        // SQL-Abfrage für den JOIN
        $query = "SELECT es.id, es.pid, es.title, es.status, es.manufacturer,
                         es.model, es.color, es.size, es.serialNumber, es.buyDate,
                         es.status, et.rentalFee
                  FROM tl_dc_equipment_subtypes es
                  INNER JOIN tl_dc_equipment_types et ON es.pid = et.id
                  WHERE es.status = 'available'
                  ORDER BY es.pid";

        return $db->fetchAllAssociative($query); // fetchAllAssociative gibt ein Array zurück
    }
}
