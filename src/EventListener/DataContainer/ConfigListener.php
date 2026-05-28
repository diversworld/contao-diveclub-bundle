<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
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
    #[AsCallback(table: 'tl_dc_students', target: 'fields.instructor_groups.options')]
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
    public function getMemberOptions(): array
    {
        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, firstname, lastname FROM tl_member ORDER BY lastname, firstname");

        foreach ($result as $row) {
            $options[$row['id']] = $row['firstname'] . ' ' . $row['lastname'];
        }

        return $options;
    }
}
