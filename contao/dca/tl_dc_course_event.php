<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_event
 * Kursveranstaltung: konkrete Durchführung einer Kurs‑Vorlage (tl_dc_dive_course)
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseEventLabelListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseEventOnSubmitListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\InstructorOptionsListener;

$GLOBALS['TL_DCA']['tl_dc_course_event'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_course_event_schedule'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'course_id' => 'index',
                'published,start,stop' => 'index'
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'dateStart', 'course_id'],
            'format' => '%s <span style="color:#999;">(%s) [Kurs‑Vorlage: %s]</span>',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit',
            '!schedule' => [
                'label' => ['Zeitplan', 'Zeitplan der Veranstaltung bearbeiten'],
                'href' => 'table=tl_dc_course_event_schedule',
                'icon' => 'calendar.svg',
                'primary' => true,
                'showInHeader' => true
            ],
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => ['addImage', 'overwriteMeta'],
        'default' => '{title_legend},title,alias,course_id;
                            {time_legend},dateStart,dateEnd,location;
                            {image_legend},addImage;
                            {details_legend},instructor,max_participants,price,description;
                            {publish_legend},published,start,stop'
    ],
    'subpalettes' => [
        'addImage' => 'singleSRC,fullsize,size,floating,overwriteMeta',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption'
    ],

    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'course_id' => [
            'label' => ['Kurs‑Vorlage', 'Referenz auf tl_dc_dive_course'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_dive_course.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w33 clr'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'dateStart' => [
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 clr wizard'],
            'sql' => "int NULL",
        ],
        'dateEnd' => [
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int NULL",
        ],
        'addImage' => [
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['singleSRC'],
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true],
            'sql' => "binary(16) NULL"
        ],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['fullsize'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50 clr'],
            'options_callback' => ['contao.listener.image_size_options', '__invoke'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '', 'customSchemaOptions' => ['collation' => 'ascii_bin']]
        ],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['floating'],
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => ['cols' => 4, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'sql' => "varchar(32) NOT NULL default 'above'"
        ],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['overwriteMeta'],
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['alt'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "text NULL"
        ],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['imageTitle'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "text NULL"
        ],
        'imageUrl' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['imageUrl'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'dcaPicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "text NULL"
        ],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['caption'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'allowHtml' => true, 'tl_class' => 'w50'],
            'sql' => "text NULL"
        ],
        'location' => [
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['maxlength' => 128, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['instructor'],
            'inputType' => 'select',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'max_participants' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'price' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'price', 'tl_class' => 'w25'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'description' => [
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
    ],
];
