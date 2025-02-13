<?php

/*
 * This file is part of Diveclub.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

use Diversworld\ContaoDiveclubBundle\Model\DcCheckInvoiceModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCoursesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
/**
 * Backend modules
 */

// Add child table tl_calendar_events_member to tl_calendar_events
$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_dc_tanks';

$GLOBALS['BE_MOD']['diversworld'] = [
    'dc_tank_collection' => [
        'tables' => ['tl_dc_tanks','tl_dc_check_invoice'],
    ],
    'dc_course_collection' => [
        'tables' => ['tl_dc_courses'],
    ],
    'dc_check_collection' => [
        'tables' => ['tl_dc_check_proposal','tl_dc_check_articles'],
    ],
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_dc_courses']          = DcCoursesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_tanks']            = DcTanksModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_invoice']    = DcCheckInvoiceModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_proposal']   = DcCheckProposalModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_articles']   = DcCheckArticlesModel::class;
