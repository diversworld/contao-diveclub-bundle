<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class ConfigListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection $db,
        private readonly Slug       $slug,
    )
    {
    }

    #[AsCallback(table: 'tl_dc_config', target: 'fields.alias.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_config');
    }

    #[AsCallback(table: 'tl_dc_config', target: 'fields.apiNewsArchive.options')]
    public function getNewsArchiveOptions(): array
    {
        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, title FROM tl_news_archive ORDER BY title");

        foreach ($result as $row) {
            $options[$row['id']] = $row['title'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_config', target: 'fields.instructor_groups.options')]
    public function getMemberGroupOptions(): array
    {
        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, name FROM tl_member_group ORDER BY name");

        foreach ($result as $row) {
            $options[$row['id']] = $row['name'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_config', target: 'fields.training_manager.options')]
    public function getTrainingManagerOptions(?DataContainer $dc = null): array
    {
        return $this->getInstructorMemberOptions($dc);
    }

    /**
     * Ausbildungsleiter muessen auch Instruktoren sein, deshalb wird die Liste
     * auf konfigurierte Instruktoren-Gruppen oder die Gruppe "instruktoren"
     * eingeschraenkt.
     */
    private function getInstructorMemberOptions(?DataContainer $dc = null): array
    {
        $instructorGroups = $this->getConfiguredInstructorGroups($dc);
        $fallbackGroupId = $this->getInstructorenFallbackGroupId();

        if ($instructorGroups === [] && $fallbackGroupId > 0) {
            $instructorGroups = [$fallbackGroupId];
        }

        if ($instructorGroups === []) {
            return [];
        }

        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, firstname, lastname, groups FROM tl_member ORDER BY lastname, firstname");

        foreach ($result as $row) {
            $memberGroups = array_filter(array_map('intval', StringUtil::deserialize($row['groups'], true)));

            if (array_intersect($instructorGroups, $memberGroups) !== []) {
                $options[$row['id']] = trim($row['firstname'] . ' ' . $row['lastname']);
            }
        }

        return $options;
    }

    private function getConfiguredInstructorGroups(?DataContainer $dc = null): array
    {
        $serializedGroups = '';

        if ($dc?->activeRecord?->instructor_groups) {
            $serializedGroups = (string)$dc->activeRecord->instructor_groups;
        } elseif ($dc?->id) {
            $serializedGroups = (string)($this->db->fetchOne(
                'SELECT instructor_groups FROM tl_dc_config WHERE id=?',
                [(int)$dc->id],
            ) ?: '');
        }

        if ($serializedGroups === '') {
            $serializedGroups = (string)($this->db->fetchOne(
                "SELECT instructor_groups FROM tl_dc_config WHERE published='1' OR published=1 LIMIT 1",
            ) ?: '');
        }

        return array_values(array_unique(array_filter(
            array_map('intval', StringUtil::deserialize($serializedGroups, true)),
        )));
    }

    private function getInstructorenFallbackGroupId(): int
    {
        return (int)$this->db->fetchOne(
            "SELECT id FROM tl_member_group WHERE LOWER(name) = 'instruktoren' ORDER BY id LIMIT 1",
        );
    }
}
