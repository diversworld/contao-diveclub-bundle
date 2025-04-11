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
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Contao\Input;
use Contao\CoreBundle\Monolog\ContaoContext;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcTanks;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_tanks'] = [
    'config'            => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_check_invoice'],
        'enableVersioning'  => true,
        'ondelete_callback' => [],
        'sql'               => [
            'keys'          => [
                'id'            => 'primary',
                'title'         => 'index',
                'alias'         => 'index',
                'serialNumber'  => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'              => [
        'sorting'           => [
            'mode'              => DataContainer::MODE_SORTABLE,
            'fields'            => ['title','owner','manufacturer','size','lastCheckDate','nextCheckDate','o2clean','status'],
            'flag'              => DataContainer::SORT_ASC,
            'panelLayout'       => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields'            => ['title','serialNumber','manufacturer','size','o2clean','lastCheckDate','nextCheckDate','status'],
            'showColumns'       => true,
            'format'            => '%s',
            'label_callback'    => ['tl_dc_tanks', 'formatCheckDates'],
            'group_callback'    => ['tl_dc_tanks', 'formatGroupHeader'],
        ],
        'global_operations' => [
            'all'               => [
                'href'          => 'act=select',
                'class'         => 'header_edit_all',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'children',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'          => [
        '__selector__'      => ['addNotes'],
        'default'           => '{title_legend},title,alias,status,rentalFee;
                                {details_legend},serialNumber,manufacturer,bazNumber,size,o2clean,owner,checkId,lastCheckDate,nextCheckDate;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'     => 'notes',
    ],
    'fields'            => [
        'id'                => [
            'sql'               => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'            => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['alias'],
            'search'            => true,
            'eval'              => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w25'],
            'save_callback' => [
                        ['tl_dc_tanks', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['serialNumber'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'manufacturer'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['manufacturer'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'bazNumber'         => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['bazNumber'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'size'              => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['size'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'],
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'],
            'eval'              => ['includeBlankOption' => true, 'tl_class' => 'w25'],
            'sql'               => "varchar(20) NOT NULL default ''",
        ],
        'o2clean'           => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['o2clean'],
            'exclude'           => true,
            'filter'            => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'checkId'           => [
            'inputType'         => 'select',                        // Typ ist "select"
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['checkId'],
            'foreignKey'        => 'tl_calendar_events.title',      // Zeigt den Titel des Events als Auswahl
            'relation'          => ['type' => 'hasOne', 'load' => 'lazy'], // Relationstyp
            'options_callback'  => ['tl_dc_tanks', 'getCalendarOptions'],  // Option Callback
            'save_callback'     => [
                ['tl_dc_tanks', 'setLastCheckDate']
            ],
            'eval'              => [
                'includeBlankOption'=> true,                      // Option "Bitte wählen" hinzufügen
                'chosen'            => true,                       // Dropdown mit Suchfunktion
                'submitOnChange'    => true,                       // Lade-Seite bei Änderung reload
                'tl_class'          => 'w33 clr'                   // Layout-Klasse
            ],
            'sql'               => "int(10) unsigned NOT NULL default 0" // Datenbankspalte
        ],
        'lastCheckDate'     => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['lastCheckDate'],
            'exclude'           => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true, 'rgxp'=>'date', 'mandatory'=>false, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql'               => "bigint(20) NULL"
        ],
        'nextCheckDate'     => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['nextCheckDate'],
            'exclude'           => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true,'rgxp'=>'date', 'doNotCopy'=>false, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql'               => "bigint(20) NULL"
        ],
        'rentalFee'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['rentalFee'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'save_callback'     => [['tl_dc_tanks', 'convertPrice']],
            'eval'              => [ 'mandatory'=>false, 'tl_class' => 'w25'], // Beachten Sie "rgxp" für Währungsangaben
            'sql'               => "DECIMAL(10,2) NOT NULL default '0.00'"
        ],
        'owner'             => [
            'inputType'         => 'select',                                        // Typ ist "select"
            'foreignKey'        => 'tl_member.CONCAT(firstname, " ", lastname)',    // Zeigt Vor- und Nachnamen als Titel
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['owner'],
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'],       // Relationstyp
            'eval'              => [
                'includeBlankOption'=> true,                                        // Option "Bitte wählen" hinzufügen
                'chosen'            => true,                                        // Dropdown mit Suchfunktion
                'mandatory'         => false,                                       // Nicht obligatorisch
                'tl_class'          => 'w33 clr'                                    // Layout-Klasse
            ],
            'sql'               => "int(10) unsigned NOT NULL default 0"            // Datenbankspalte
        ],
        'status'        => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['status'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_tanks']['itemStatus'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_tanks']['itemStatus'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'         => [
            'inputType'         => 'checkbox',
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'toggle'            => true,
            'filter'            => true,
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcTanks $DcTanks
 *
 * @internal
 */
class tl_dc_tanks extends Backend
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
                ->prepare("SELECT id FROM tl_dc_tanks WHERE alias=? AND id!=?")
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

    function formatCheckDates($row): string
    {
        $owners = $this->getOwnerOptions(); // Add this line to get owner options stucture
        $ownerName = $owners[$row['owner']] ?? 'N/A';

        $title = $row['title'] ?? '';
        $serialnumber = $row['serialNumber'] ?? '';
        $size = $row['size'] ?? '';
        $manufacturer = $row['manufacturer'] ?? '';
        $invoices = $this->listChildren($row);
        $lastTotal = $this->getLastInvoiceTotal($row);

        if($row['o2clean'] == 1){
            $o2CleanValue = 'ja';
        } else {
            $o2CleanValue = 'nein';
        }

        $lastCheckDate = isset($row['lastCheckDate']) && is_numeric($row['lastCheckDate'])
            ? date('d.m.Y', $row['lastCheckDate'])
            : 'N/A';

        $nextCheckDate = isset($row['nextCheckDate']) && is_numeric($row['nextCheckDate'])
            ? date('d.m.Y', $row['nextCheckDate'])
            : 'N/A';

        if($invoices == 1) {
            return sprintf(' %s - %s - %s L - %s - O2: %s - %s - letzter TÜV %s - nächster TÜV %s <span style="color:#b3b3b3; padding-left:4px;">[%s Rechnung] [letzte Rechnung: %s €]</span>',
                $title,
                $serialnumber,
                $size,
                $manufacturer,
                $o2CleanValue,
                $ownerName,
                $lastCheckDate,
                $nextCheckDate,
                $invoices,
                $lastTotal
            );
        }elseif ($invoices >= 2) {
            return sprintf('%s - %s - %s L - %s -  O2: %s - %s - letzter TÜV %s - nächster TÜV %s <span style="color:#b3b3b3; padding-left:4px;">[%s Rechnungen] [letzte Rechnung: %s €]</span>',
                $title,
                $serialnumber,
                $size,
                $manufacturer,
                $o2CleanValue,
                $ownerName,
                $lastCheckDate,
                $nextCheckDate,
                $invoices,
                $lastTotal
            );
        } else {
            return sprintf('%s - %s - %s L - %s - O2: %s - %s - letzter TÜV %s - nächster TÜV %s',
                $title,
                $serialnumber,
                $size,
                $manufacturer,
                $o2CleanValue,
                $ownerName,
                $lastCheckDate,
                $nextCheckDate
            );
        }
    }

    function formatGroupHeader($group, $field, $row): string
    {
        if ($field === 'owner') { // Check if field is 'owner'
            $db = Database::getInstance();
            $result = $db->prepare("SELECT SUM(priceTotal) as total FROM tl_dc_check_invoice WHERE $field = ?")
                ->execute($row[$field]);

            $lastTotal =  $result->total;
            return $group . ' (Rechnung: ' . $lastTotal . ' €)';
        }

        return $group; // default return
    }

    /**
     * @throws Exception
     */
    public function setLastCheckDate($varValue, DataContainer $dc)
    {
        $logger = System::getContainer()->get('monolog.logger.contao');
        $logger->error(
            'Varvalue: ' . $varValue,
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]
        );

        if ($varValue)
        {
            // Holen Sie das startDate des ausgewählten TÜV-Termins
            $db = Contao\Database::getInstance();
            $result = $db->prepare("SELECT startDate FROM tl_calendar_events WHERE id = ?")
                ->execute($varValue);

            $row = $result->fetchAssoc();

            $logger->error(
                'StartDate: ' . $row['startDate'],
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]
            );

            $lastCheckDate = new DateTime('@'.$row['startDate']);
            $lastCheckDate->modify('+2 years');

            $nextCheckDate = $lastCheckDate->getTimestamp();

            // Setzen Sie lastCheckDate auf das startDate des ausgewählten TÜV-Termins
            $updateStmt = Database::getInstance()
                ->prepare("UPDATE tl_dc_tanks SET lastCheckDate = ?, nextCheckDate = ? WHERE id = ?");
            $updateStmt->execute($row['startDate'], $nextCheckDate, $dc->id);
        }

        return $varValue;
    }

    public function getOwnerOptions(): array
    {
        $owners = Database::getInstance()->execute("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tl_member")->fetchAllAssoc();
        $options = array();

        foreach($owners as $owner)
        {
            $options[$owner['id']] = $owner['name'];
        }

        return $options;
    }

    function getCalendarOptions():array
    {
        $events = Database::getInstance()->execute("SELECT id, title FROM tl_calendar_events WHERE addCheckInfo = '1' and published = '1'")->fetchAllAssoc();
        $options = [];

        foreach($events as $event)
        {
            System::getContainer()->get('monolog.logger.contao.general')
            ->info('Event-Daten: ', $event); // Log zusätzlicher Details

            $options[$event['id']] = $event['title'];
        }

        return $options;
    }

    public function getLastInvoiceTotal($arrRow)
    {
        $tankId = $arrRow['id'];

        $result = Database::getInstance()
            ->prepare("SELECT priceTotal AS total FROM tl_dc_check_invoice WHERE pid = ? ORDER BY id DESC LIMIT 1")
            ->execute($tankId)
            ->fetchAssoc();

        // Prüfen, ob das Abfrageergebnis nicht leer ist
        if ($result){
            return $result['total'];
        }
        return null;  // Oder einen anderen Standardwert zurückgeben
    }

    public function listChildren($arrRow)
    {
        // Get the ID of the current tank
        $tankId = $arrRow['id'];

        // Query the database to find the number of invoices related to this tank
        // Return the count of invoices
        return Database::getInstance()
            ->prepare("SELECT COUNT(*) AS count FROM tl_dc_check_invoice WHERE pid = ?")
            ->execute($tankId)
            ->fetchAssoc()['count'];
    }

    public function filterTanksByEventId(DataContainer $dc): void
    {
        if (Input::get('do') == 'calendar' && ($eventId = Input::get('event_id')) !== null) {
            $GLOBALS['TL_DCA']['tl_dc_tanks']['list']['sorting']['filter'] = [['pid=?', $eventId]];
        }
    }

    /**
     * Formatiert den Preis für die Anzeige im Backend
     */
    public function formatPrice($value): string
    {
        return number_format((float)$value, 2, '.', ',') . ' €'; // z. B. "123.45 €"
    }

    /**
     * Konvertiert den eingegebenen Preis zurück ins DB-Format
     */
    public function convertPrice($value): float
    {
        // Logik für leere Eingabe
        if (empty($value)) {
            return 0.00;
        }

        // Entferne eventuell angefügte Währungszeichen und whitespace
        $value = str_replace(['€', ' '], '', $value);

        // Stelle sicher, dass es ein gültiger Dezimalwert ist
        return round((float)$value, 2);
    }
}
