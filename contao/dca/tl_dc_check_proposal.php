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

use Contao\Backend;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcCheckProposal;

/**
 * Table tl_dc_check_proposal
 */
$GLOBALS['TL_DCA']['tl_dc_check_proposal'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_check_articles', 'tl_dc_check_booking'],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'alias' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['title', 'alias', 'published'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['title', 'vendorName', 'checkId'],
            'format' => '%s %s %s',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit',
            'children',
            '!orders' => [
                'href' => 'table=tl_dc_check_booking',
                'icon' => 'forward.svg', //'bundles/diversworldcontaodiveclub/icons/order.svg', // Icon muss ggf. noch erstellt werden
                'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['orders'],
                'primary' => true,
                'showInHeader' => true
            ],
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
            '!tuv_list' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['tuv_list'],
                'href' => 'key=tuv_list',
                'icon' => 'bundles/diversworldcontaodiveclub/icons/pdf.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => [tl_dc_check_proposal::class, 'generateTuvListButton'],
                'primary' => true,
                'showInHeader' => true
            ],
            'new_after' => [
                'label' => ['Neu danach', 'Neue Zuordnung hinzufügen'],
                'href' => 'act=create&amp;mode=1',
                'icon' => 'new.svg', // Das Plus-Icon
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ],
        ]
    ],
    'palettes' => [
        '__selector__' => ['addNotes'],
        'default' => '{title_legend},title,alias;
                                {details_legend},proposalDate,checkId;
                                {vendor_legend},vendorName,vendorWebsite,vendorStreet,vendorPostal,vendorCity,vendorEmail,vendorPhone,vendorMobile;
                                {notes_legend},notes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [
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
            'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['title'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback' => [['tl_dc_check_proposal', 'generateAlias']],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'checkId' => [
            'inputType' => 'select', // 'select' für Dropdown
            'foreignKey' => 'tl_calendar_events.title',
            //'options_callback'  => [['tl_dc_check_proposal', 'getCalenarOptions']],
            'options_callback' => function () {
                $options = [];
                $db = Database::getInstance();
                $result = $db->execute("SELECT id, title FROM tl_calendar_events WHERE addCheckInfo = '1'");

                if ($result->numRows > 0) {
                    $data = $result->fetchAllAssoc();
                    $options = array_column($data, 'title', 'id');
                }
                return $options;
            },
            'save_callback' => [
                ['tl_dc_check_proposal', 'updateEventVendorInfo']
            ], // Spezifische Callback-Methode
            'eval' => [
                'includeBlankOption' => true, // Ermöglicht eine leere Auswahl als Standardvalue
                'mandatory' => false,
                'chosen' => true, // Bessere Darstellung des Dropdowns
                'tl_class' => 'w25', // CSS-Klasse fürs Layout
            ],
            'sql' => "int unsigned NULL default 0",
        ],
        'proposalDate' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['proposalDate'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w25 clr wizard'],
            'sql' => "int unsigned NULL"
        ],
        'vendorName' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql' => "varchar(255) NULL default ''",
        ],
        'vendorWebsite' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => HttpUrlListener::RGXP_NAME, 'maxlength' => 255, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w33'],
            'sql' => "varchar(255) NULL default ''"
        ],
        'vendorStreet' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33 clr',],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'vendorPostal' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['maxlength' => 12, 'tl_class' => 'w25',],
            'sql' => "varchar(32) NULL default ''",
        ],
        'vendorCity' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql' => "varchar(255) NULL default ''",
        ],
        'vendorEmail' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'rgxp' => 'email', 'unique' => false, 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25 clr'],
            'sql' => "varchar(255) NULL default ''"
        ],
        'vendorPhone' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25'],
            'sql' => "varchar(64) NULL default ''"
        ],
        'vendorMobile' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25'],
            'sql' => "varchar(64) NULL default ''"
        ],
        'addNotes' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'],
            'exclude' => true,
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'notes' => [
            'inputType' => 'textarea',
            'exclude' => true,
            'search' => false,
            'filter' => true,
            'sorting' => false,
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcCheckProposal $dcCheckProposal
 *
 * @internal
 */
class tl_dc_check_proposal extends Backend
{
    /**
     * Auto-generate the event alias if it has not been set yet
     *
     * @param mixed $varValue
     * @param DataContainer $dc
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_dc_check_proposal WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        // Generate the alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate(
                $dc->activeRecord->title,
                [],
                $aliasExists
            );
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Funktion, um die Vendor-Info in das zugehörige Event zu schreiben
     *
     * @param mixed $varValue Der neue Wert des Feldes (checkId)
     * @param DataContainer $dc Das DataContainer-Objekt des aktuellen Datensatzes
     *
     * @return mixed
     */
    public function updateEventVendorInfo(mixed $varValue, DataContainer $dc): mixed
    {
        // Prüfe, ob der Wert gesetzt ist (keine leere Auswahl)
        if (!empty($varValue)) {
            // Hole die Datenbank-Instanz
            $db = Database::getInstance();

            // Lade die vorhandenen Event-Daten aus der Tabelle tl_calendar_events
            $event = $db->prepare("SELECT * FROM tl_calendar_events WHERE id = ?")
                ->execute($varValue);

            if ($event->numRows > 0) {
                // Hole den Vendor-Namen aus dem aktuellen tl_dc_check_proposal-Datensatz
                $vendor = $dc->activeRecord->id;

                // Update der Vendor-Info für das Event
                $db->prepare("UPDATE tl_calendar_events SET addVendorInfo = ? WHERE id = ?")
                    ->execute($vendor, $varValue);

                // Optional: Protokollieren, dass der Vendor eingetragen wurde
                $logger = System::getContainer()->get('monolog.logger.contao');
                $logger->info(
                    'Vendor-Info für Event-ID ' . $varValue . ' aktualisiert: ' . $vendor,
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]
                );
            } else {
                throw new RuntimeException(sprintf('Das Event mit der ID %d existiert nicht.', $varValue));
            }
        }
        // Rückgabe des gespeicherten Wertes
        return $varValue;
    }

    public function generateTuvListButton($row, $href, $label, $title, $icon, $attributes)
    {
        $url = System::getContainer()->get('router')->generate('dc_tuv_list_export', ['id' => $row['id']]);

        return '<a href="' . $url . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . ' target="_blank">' . Image::getHtml($icon, $label) . '</a> ';
    }
}
