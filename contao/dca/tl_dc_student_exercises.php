<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_student_exercises
 * Status/Auswertung einer Übung pro Schüler
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_dc_student_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_students',
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'onload_callback' => [
            ['tl_dc_student_exercises', 'checkCustomAktion']
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'exercise_id' => 'index'
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting', 'exercise_id', 'dateCompleted'],
            'headerFields' => ['course_id', 'status', 'registered_on'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['exercise_id', 'status'],
            'label_callback' => ['tl_dc_student_exercises', 'addExerciseInfo'],
            'format' => '%s — <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
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
            '!complete' => [
                'label' => ['Übung abschließen', 'Status auf OK setzen und Datum eintragen'],
                'icon' => 'ok.svg',
                'href' => 'key=completeExercise',
                'button_callback' => ['tl_dc_student_exercises', 'showCompleteButton'],
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
        'default' => '{exercise_legend},exercise_id;
                      {result_legend},status,dateCompleted,instructor;
                      {notes_legend},notes;
                      {publish_legend},published,start,stop',
    ],

    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_course_students.id',
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'exercise_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['exercise_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_exercises.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['status'],
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'],
            'options' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'pending'",
        ],
        'dateCompleted' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['dateCompleted'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['instructor'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['notes'],
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['published'],
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
        ]
    ],
];

class tl_dc_student_exercises extends Backend
{
    /**
     * Prüft beim Laden der Liste, ob eine Quick-Action ausgeführt werden soll
     */
    public function checkCustomAktion(DataContainer $dc): void
    {
        if (Input::get('key') === 'completeExercise' && Input::get('id')) {
            $this->completeExercise((int)Input::get('id'));
        }
    }

    public function completeExercise($id): void
    {
        $db = Database::getInstance();

        // Hinweis: dateCompleted wird als Zeitstempel gespeichert,
        // stelle sicher dass das Feld im DCA 'rgxp' => 'date' oder 'datim' hat.
        $db->prepare("UPDATE tl_dc_student_exercises SET status='ok', dateCompleted=? WHERE id=?")
            ->execute(time(), $id);

        // Wir leiten zurück zur Liste, um die URL zu säubern (Parameter key/id entfernen)
        // Das verhindert auch das Problem der leeren Seite
        $this->redirect($this->getReferer());
    }

    /**
     * Zeigt den Button nur an, wenn der Status noch nicht 'ok' ist
     */
    public function showCompleteButton($row, $href, $label, $title, $icon, $attributes): string
    {
        if ($row['status'] === 'ok') {
            return Image::getHtml(str_replace('.svg', '_1.svg', $icon), $label, 'class="disabled"');
        }

        return sprintf('<a href="%s" title="%s"%s>%s</a> ',
            $this->addToUrl($href . '&amp;id=' . $row['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }

    public function addExerciseInfo(array $row, string $label): string
    {
        $db = Database::getInstance();

        // Übungs- und Modulnamen über die Template-Tabellen holen
        $objInfo = $db->prepare("
            SELECT e.title AS exTitle, m.title AS modTitle
            FROM tl_dc_course_exercises e
            JOIN tl_dc_course_modules m ON e.pid = m.id
            WHERE e.id = ?
        ")->execute($row['exercise_id']);

        if ($objInfo->numRows < 1) {
            return $label;
        }

        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_student_exercises']['status'][$row['status']] ?? $row['status'];
        $color = ($row['status'] === 'ok') ? '#2fb31b' : (($row['status'] === 'pending') ? '#ff8000' : '#ff0000');

        return sprintf(
            '<span style="color:#999; width:150px; display:inline-block;">[%s]</span> <span style="width:250px; display:inline-block;"><strong>%s</strong></span> — <span style="color:%s; font-weight:bold;">%s</span>',
            $objInfo->modTitle,
            $objInfo->exTitle,
            $color,
            $statusLabel
        );
    }
}
