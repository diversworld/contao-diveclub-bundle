<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub.
 *
 * (c) DiversWorld 2025 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\Model;

use Contao\CoreBundle\DependencyInjection\Attribute\AsModel;
use Contao\Model;

#[AsModel(table: 'tl_dc_course_event')]
class DcCourseEventModel extends Model
{
    protected static $strTable = 'tl_dc_course_event';
}
