<?php

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['dc_modules']['dc_collection'] = array(
    'tables' => array('tl_dc_tanks')
);

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_dc_tanks'] = DcTanksModel::class;
