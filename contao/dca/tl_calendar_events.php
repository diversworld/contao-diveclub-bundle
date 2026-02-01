<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Diversworld\ContaoDiveclubBundle\DataContainer\CalendarEvents;
use Contao\Database;

// Overwrite child record callback
/*
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['child_record_callback'] = [
    CalendarEvents::class,
    'listEvents',
];*/
/*
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['onsubmit_callback'] = [
    [CalendarEvents::class, 'calculateAllGrossPrices']
];
*/

// Palettes
PaletteManipulator::create()
    ->addLegend('dive_legend', 'details_legend')
    ->addLegend('vendor_legend', 'dive_legend')
    ->addLegend('article_legend', 'vendor_legend')
    ->addField(['addCheckInfo', 'addCourseInfo'], 'dive_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar_events');

// Selector
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addCheckInfo';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addVendorInfo';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addArticleInfo';
$GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['__selector__'][] = 'addCourseInfo';

// Subpalettes
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addCheckInfo']     = 'addVendorInfo, addArticleInfo';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addVendorInfo']    = 'vendorName, street, postal, city, vendorEmail, vendorPhone, vendorMobile';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addArticleInfo']   = 'checkArticles';
$GLOBALS['TL_DCA']['tl_calendar_events']['subpalettes']['addCourseInfo']    = 'category, courseFee';

// Operations
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'] = array_slice($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'], 0, 6, true) + [
        'registrations' => [
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['tanks'],
            'href' => 'table=tl_dc_tanks',
            'icon' => 'bundles/diversworldcontaodiveclub/icons/tanks.svg',
        ],
    ] + array_slice($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'], 6, count($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']) - 1, true);

$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'] = array_slice($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'], 0, 7, true) + [
        'registrations' => [
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['check_articles'],
            'href' => 'table=tl_dc_check_articles',
            'icon' => 'bundles/diversworldcontaodiveclub/icons/tanks.svg',
        ],
    ] + array_slice($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'], 6, count($GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']) - 1, true);

//Fields
$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['is_tuv_appointment'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w25'],
    'sql'       => ['type' => 'boolean', 'default' => false]
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addCheckInfo'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr w25',],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addVendorInfo'] = [
    'inputType'         => 'select',
    'options_callback'  => function () {
            $options = [];
            $db = Database::getInstance();
            $result = $db->execute("SELECT id, title FROM tl_dc_check_proposal WHERE published = '1'");

            if ($result->numRows > 0) {
                $data = $result->fetchAllAssoc();
                $options = array_column($data, 'title', 'id');
            }
            return $options;
    },
    'eval'              => array('submitOnChange' => true, 'alwaysSave' => true,'mandatory'=> false, 'includeBlankOption'=> true, 'tl_class' => 'w33 clr'),
    'sql'               => "int unsigned NOT NULL default 0"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['courseFee'] = [
    'inputType' => 'text',
    'exclude'   => true,
    'search'    => true,
    'filter'    => true,
    'sorting'   => true,
    'eval'      => array('tl_class'=>'w25', 'alwaysSave' => true, 'rgxp' => 'digit',),
    'sql'       => "DECIMAL(10,2) NOT NULL default '0.00'"
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['category'] = [
    'inputType' => 'select',
    'exclude'   => true,
    'search'    => true,
    'filter'    => true,
    'sorting'   => true,
    'reference' => &$GLOBALS['TL_LANG']['tl_calendar_events'],
    'options'   => array('basicOption', 'advancedOption', 'professionalOption','technicalOption'),
    'eval'      => array('includeBlankOption' => true, 'tl_class' => 'w25'),
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['addCourseInfo'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr w25',],
    'sql'       => ['type' => 'boolean', 'default' => false],
];
