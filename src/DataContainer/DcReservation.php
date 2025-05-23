<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;

//#[AsCallback(table: 'tl_dc_tanks', target: 'edit.buttons', priority: 100)]
class DcReservation
{
    private ContaoFramework $framework;
    private Database $db;

    public function __construct(ContaoFramework $framework, Database $database)

    {
        $this->framework = $framework;
        $this->db = $database;
    }
}
