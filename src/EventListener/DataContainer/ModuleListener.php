<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;

class ModuleListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    public function setJumpToLabel(DataContainer|null $dc = null): void
    {
        $activeRecord = $dc?->activeRecord;
        $moduleType = \is_object($activeRecord) ? ($activeRecord->type ?? null) : null;

        $postType = Input::post('type');
        if (\is_string($postType) && '' !== $postType) {
            $moduleType = $postType;
        }

        if ((!\is_string($moduleType) || '' === $moduleType) && $dc?->id) {
            $moduleType = $this->connection->fetchOne(
                'SELECT type FROM tl_module WHERE id = ?',
                [(int)$dc->id],
            );
        }

        $languageKey = match ($moduleType) {
            'dc_student_courses' => 'dc_progress_jumpTo',
            'dc_course_event_reader' => 'dc_course_confirmation_jumpTo',
            'dc_tank_check' => 'dc_tank_confirmation_jumpTo',
            default => null,
        };

        if (null === $languageKey || !isset($GLOBALS['TL_LANG']['tl_module'][$languageKey])) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_module']['fields']['jumpTo']['label'] =
            $GLOBALS['TL_LANG']['tl_module'][$languageKey];
    }
}
