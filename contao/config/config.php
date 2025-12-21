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

use Diversworld\ContaoDiveclubBundle\Model\DcCalendarEventsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckInvoiceModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcConfigModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseExercisesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseModulesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseStudentsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorControlModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationModel;
use Diversworld\ContaoDiveclubBundle\Model\DcStudentExercisesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;

/**
 * Backend modules
 */

// Add child table tl_calendar_events_member to tl_calendar_events
//$GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_dc_tanks';

$GLOBALS['TL_DCA']['tl_calendar_events']['config']['ctable'][] = 'tl_dc_dive_course';

$GLOBALS['BE_MOD']['diveclub'] = [

    'dc_equipment_collection' => [
        'tables' => ['tl_dc_equipment'],
    ],
    'dc_regulators_collection' => [
        'tables' => ['tl_dc_regulators', 'tl_dc_regulator_control'],
    ],
    'dc_tanks_collection' => [
        'tables' => ['tl_dc_tanks', 'tl_dc_check_invoice'],
    ],
    'dc_course_collection' => [
        'tables' => [
            'tl_dc_dive_course',
            'tl_dc_course_modules',
            'tl_dc_course_exercises'
        ],
    ],
    'dc_dive_student_collection' => [
        'tables' => [
            'tl_dc_students',
            'tl_dc_course_students',
            'tl_dc_student_exercises'
        ],
    ],
    'dc_reservation_collection' => [
        'tables' => ['tl_dc_reservation', 'tl_dc_reservation_items'],
    ],
    'dc_check_collection' => [
        'tables' => ['tl_dc_check_proposal', 'tl_dc_check_articles'],
    ],
    'dc_config_collection' => [
        'tables' => ['tl_dc_config'],
    ],
];

/**
 * Models
 */

$GLOBALS['TL_MODELS']['tl_dc_tanks'] = DcTanksModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_invoice'] = DcCheckInvoiceModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_proposal'] = DcCheckProposalModel::class;
$GLOBALS['TL_MODELS']['tl_dc_check_articles'] = DcCheckArticlesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_equipment'] = DcEquipmentModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_events'] = DcCalendarEventsModel::class;
$GLOBALS['TL_MODELS']['tl_dc_regulators'] = DcRegulatorsModel::class;
$GLOBALS['TL_MODELS']['tl_dc_regulator_control'] = DcRegulatorControlModel::class;
$GLOBALS['TL_MODELS']['tl_dc_config'] = DcConfigModel::class;
$GLOBALS['TL_MODELS']['tl_dc_reservation'] = DcReservationModel::class;
$GLOBALS['TL_MODELS']['tl_dc_reservation_items'] = DcReservationItemsModel::class;
$GLOBALS['TL_MODELS']['tl_dc_dive_course'] = DcDiveCourseModel::class;
$GLOBALS['TL_MODELS']['tl_dc_course_modules'] = DcCourseModulesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_course_exercises'] = DcCourseExercisesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_student_exercises'] = DcStudentExercisesModel::class;
$GLOBALS['TL_MODELS']['tl_dc_course_students'] = DcCourseStudentsModel::class;

/**
 * Frontend Modules
 */


/**
 * Hooks
 */

