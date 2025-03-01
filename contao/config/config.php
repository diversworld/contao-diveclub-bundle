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
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentTypeModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorControlModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCoursesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCalendarEventsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorModel;
use Diversworld\ContaoDiveclubBundle\Model\DcControlCardModel;

/**
 * Backend modules
 */

// Add child table tl_calendar_events_member to tl_calendar_events
//$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_dc_tanks';

$GLOBALS['BE_MOD']['diveclub'] = [
        'dc_equipment_collection' => [
            'tables' => ['tl_dc_equipment_type', 'tl_dc_equipment'],
        ],
        'dc_regulators_collection' => [
        'tables' => ['tl_dc_regulators','tl_dc_regulator_control'],
        ],
        'dc_tanks_collection' => [
            'tables' => ['tl_dc_tanks','tl_dc_check_invoice'],
        ],
        'dc_course_collection' => [
            'tables' => ['tl_dc_courses','tl_content'],
        ],
        'dc_check_collection' => [
            'tables' => ['tl_dc_check_proposal','tl_dc_check_articles'],
        ],
        'dc_config_collection' => [
            'tables' => ['tl_dc_config'],
        ]
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_dc_courses']              = DcCoursesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_tanks']                = DcTanksModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_invoice']        = DcCheckInvoiceModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_proposal']       = DcCheckProposalModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_articles']       = DcCheckArticlesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_eqipment_type']        = DcEquipmentTypeModel::class;
$GLOBALS['TL_MODELS']['tl_dc_equipment']            = DcEquipmentModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_events']         = DcCalendarEventsModel::class;
$GLOBALS['TL_MODELS']['tl_dc_regulator']            = DcRegulatorModel::class;
$GLOBALS['TL_MODELS']['tl_dc_control_card']         = DcControlCardModel::class;
$GLOBALS['TL_MODELS']['tl_regulators']              = DcRegulatorsModel::class;
$GLOBALS['TL_MODELS']['tl_regulators_control']      = DcRegulatorControlModel::class;
