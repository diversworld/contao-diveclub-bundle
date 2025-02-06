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

use Diversworld\ContaoDiveclubBundle\Controller\FrontendModule\DucListingController;

/**
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['dc_modules'] = 'Diveclub';
$GLOBALS['TL_LANG']['MOD']['dc_collection'] = ['Diveclub', 'Diveclub management'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['dc_modules'] = 'Diveclub';
$GLOBALS['TL_LANG']['FMD'][DucListingController::TYPE] = ['Diveclub Modul', 'Diveclub Frontend module'];

