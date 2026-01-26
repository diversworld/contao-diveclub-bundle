<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_tanks', target: 'list.label.label')]
class TankLabelListener
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): string
    {
        $owners = $this->getOwnerOptions();
        $ownerName = $owners[$row['owner']] ?? 'N/A';

        $title = $row['title'] ?? '';
        $serialnumber = $row['serialNumber'] ?? '';
        $size = $row['size'] ?? '';
        $manufacturer = $row['manufacturer'] ?? '';

        $o2CleanValue = ($row['o2clean'] == 1) ? 'ja' : 'nein';

        $lastCheckDate = isset($row['lastCheckDate']) && is_numeric($row['lastCheckDate'])
            ? date('d.m.Y', (int) $row['lastCheckDate'])
            : 'N/A';

        $nextCheckDate = isset($row['nextCheckDate']) && is_numeric($row['nextCheckDate'])
            ? date('d.m.Y', (int) $row['nextCheckDate'])
            : 'N/A';

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

    private function getOwnerOptions(): array
    {
        $owners = $this->db->fetchAllAssociative("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tl_member");
        $options = [];

        foreach ($owners as $owner) {
            $options[$owner['id']] = $owner['name'];
        }

        return $options;
    }
}
