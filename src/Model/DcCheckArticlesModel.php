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

#[AsModel(table: 'tl_dc_check_articles')]
class DcCheckArticlesModel extends Model
{
    protected static $strTable = 'tl_dc_check_articles';
}
