<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class CourseExerciseAliasListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Slug $slug
    ) {
    }

    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.alias.save')]
    public function __invoke(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            $id = $this->connection->fetchOne(
                "SELECT id FROM tl_dc_course_exercises WHERE alias=? AND id!=?",
                [$alias, $dc->id]
            );

            return (bool) $id;
        };

        // Generate the alias if there is none
        if (!$varValue) {
            $varValue = $this->slug->generate(
                $dc->activeRecord->title,
                [],
                $aliasExists
            );
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
