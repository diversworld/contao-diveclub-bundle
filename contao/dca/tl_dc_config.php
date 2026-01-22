<?php

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Psr\Log\LoggerInterface;

$GLOBALS['TL_DCA']['tl_dc_config'] = [
    'config' => [ // Konfiguration des Data Containers
        'dataContainer' => DC_Table::class, // Verwendung der Standard-Tabellen-Klasse
        'enableVersioning' => true, // Aktivierung der Versionierung für Datensätze
        'sql' => [ // SQL-Definitionen
            'keys' => [ // Index-Definitionen
                'id' => 'primary', // Primärschlüssel
                'tstamp' => 'index', // Zeitstempel-Index
                'alias' => 'index', // Alias-Index
            ],
        ],
    ],
    'list' => [ // Konfiguration der Listenansicht im Backend
        'sorting' => [ // Sortierungseinstellungen
            'mode' => DataContainer::MODE_SORTED, // Sortieren nach einem Feld
            'fields' => ['title', 'alias'], // Sortierfelder: Titel und Alias
            'flag' => DataContainer::SORT_ASC, // Sortierung aufsteigend
            'panelLayout' => 'filter;sort,search,limit', // Layout des Filter-Panels (Filter, Sortierung, Suche, Limit)
        ],
        'label' => [ // Label-Einstellungen für die Liste
            'fields' => ['title', 'alias'], // Anzeigefelder in der Liste
            'showColumns' => true, // Felder in Spalten anzeigen
            'format' => '%s (%s)', // Formatierung der Label-Ausgabe
        ],
        'global_operations' => [ // Globale Operationen (für die ganze Tabelle)
            'all' => [ // Bearbeiten mehrerer Datensätze
                'href' => 'act=select', // Link-Aktion
                'class' => 'header_edit_all', // CSS-Klasse
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"', // HTML-Attribute
            ],
        ],
        'operations' => [ // Operationen pro Datensatz
            'edit', // Bearbeiten
            'copy', // Kopieren
            'delete', // Löschen
            'toggle', // Sichtbarkeit umschalten
            'show', // Details anzeigen
        ],
    ],
    'palettes' => [ // Definition der Eingabemasken (Paletten)
        '__selector__' => ['addManufacturer', 'addRegulators', 'addEquipment', 'addSizes', 'addCourses', 'addReservations', 'addChecks'], // Selektoren für Subpaletten
        'default' => '{title_legend},title,alias; // Standardpalette mit verschiedenen Legenden und Feldern
                                {manufacturer_legend},addManufacturer;
                                {equipment_legend},addEquipment;
                                {sizes_legend},addSizes;
                                {types_legend},addTypes;
                                {course_legend},addCourses;
                                {regulator_legend},addRegulators;
                                {invoice_legend},invoiceTemplate,invoiceText,pdfFolder;
                                {tuv_legend},tuvListFormat,tuvListFolder;
                                {reservation_legend},reservationMessage,reservationInfo,reservationInfoText;
                                {conditions_legend},rentalConditions;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [ // Definition der Subpaletten (eingeblendet bei Checkbox-Aktivierung)
        'addManufacturer' => 'manufacturersFile', // Erscheint wenn addManufacturer aktiv ist
        'addEquipment' => 'typesFile,subTypesFile', // Erscheint wenn addEquipment aktiv ist
        'addSizes' => 'sizesFile', // Erscheint wenn addSizes aktiv ist
        'addRegulators' => 'regulatorsFile', // Erscheint wenn addRegulators aktiv ist
        'addCourses' => 'courseTypesFile,courseCategoriesFile' // Erscheint wenn addCourses aktiv ist
    ],
    'fields' => [ // Definition der einzelnen Datenbankfelder
        'id' => [ // ID-Feld
            'sql' => "int(10) unsigned NOT NULL auto_increment", // SQL-Typ
        ],
        'tstamp' => [ // Zeitstempel-Feld
            'sql' => "int(10) unsigned NOT NULL default 0", // SQL-Typ
        ],
        'title' => [ // Titel-Feld
            'inputType' => 'text', // Eingabetyp Text
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['title'], // Sprachlabel
            'exclude' => true, // Für Nicht-Admins ausschließbar
            'search' => true, // In der Suche berücksichtigen
            'filter' => true, // Filterbar in der Liste
            'sorting' => true, // Sortierbar in der Liste
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC, // Sortierung nach Anfangsbuchstabe
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'], // Evaluierungseinstellungen
            'sql' => "varchar(255) NOT NULL default ''" // SQL-Typ
        ],
        'alias' => [ // Alias-Feld (URL-Fragment)
            'search' => true, // Suchbar
            'inputType' => 'text', // Text-Eingabe
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'], // Validierung als Alias
            'save_callback' => [ // Callback-Funktion vor dem Speichern
                ['tl_dc_config', 'generateAlias'] // Generiert den Alias automatisch
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''" // SQL-Typ
        ],
        'addManufacturer' => [ // Checkbox zum Aktivieren von Herstellern
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['addManufacturer'], // Label
            'exclude' => true, // Ausschließbar
            'inputType' => 'checkbox', // Checkbox-Eingabe
            'eval' => ['submitOnChange' => true], // Seite bei Änderung neu laden
            'sql' => ['type' => 'boolean', 'default' => false] // SQL-Typ Boolean
        ],
        'addEquipment' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['addEquipment'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'addSizes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['addSizes'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'addRegulators' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['addRegulators'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'manufacturersFile' => array
        (
            'inputType' => 'fileTree',
            'eval' => array('fieldType' => 'radio', 'files' => true, 'tl_class' => '33clr'),
            'sql' => "binary(16) NULL"
        ),
        'sizesFile' => array
        (
            'inputType' => 'fileTree',
            'eval' => array('fieldType' => 'radio', 'files' => true, 'tl_class' => 'w33clr'),
            'sql' => "binary(16) NULL"
        ),
        'typesFile' => array
        (
            'inputType' => 'fileTree',
            'eval' => array('fieldType' => 'radio', 'files' => true, 'tl_class' => 'w33clr'),
            'sql' => "binary(16) NULL"
        ),
        'regulatorsFile' => array
        (
            'inputType' => 'fileTree',
            'eval' => array('fieldType' => 'radio', 'files' => true, 'tl_class' => 'w33clr'),
            'sql' => "binary(16) NULL"
        ),
        'addCourses' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['addCourses'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'courseTypesFile' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => true, 'tl_class' => 'w33clr'],
            'sql' => "binary(16) NULL"
        ],
        'courseCategoriesFile' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => true, 'tl_class' => 'w33clr'],
            'sql' => "binary(16) NULL"
        ],
        'invoiceTemplate' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['invoiceTemplate'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => true, 'extensions' => 'pdf', 'tl_class' => 'clr'],
            'sql' => "binary(16) NULL"
        ],
        'invoiceText' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['invoiceText'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
        'pdfFolder' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['pdfFolder'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => false, 'mandatory' => false, 'tl_class' => 'clr'],
            'sql' => "binary(16) NULL"
        ],
        'tuvListFormat' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['tuvListFormat'],
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['pdf', 'csv', 'xlsx'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(10) NOT NULL default 'pdf'"
        ],
        'tuvListFolder' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['tuvListFolder'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => false, 'mandatory' => false, 'tl_class' => 'w50'],
            'sql' => "binary(16) NULL"
        ],
        'reservationInfo' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['reservationInfo'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'eval' => ['rgxp' => 'emails', 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33 clr'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'reservationInfoText' => [
            'inputType' => 'textarea',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['reservationInfoText'],
            'exclude' => true,
            //'eval'                  => ['mandatory' => false, 'tl_class' => 'clr'],
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
        'reservationMessage' => [
            'inputType' => 'textarea',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['reservationMessage'],
            'exclude' => true,
            //'eval'                  => ['mandatory' => false, 'tl_class' => 'clr'],
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
        'rentalConditions' => [
            'inputType' => 'textarea',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_config']['rentalConditions'],
            'exclude' => true,
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
        'published' => [
            'inputType' => 'checkbox',
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
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
    ],
];

class tl_dc_config extends Backend
{
    public LoggerInterface $logger;

    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_dc_config WHERE alias=? AND id!=?")
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
}
