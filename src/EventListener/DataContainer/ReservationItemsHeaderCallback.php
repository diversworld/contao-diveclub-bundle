<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * Adds the total number of events to the header fields.
 */
#[AsCallback(table: 'tl_calendar_events', target: 'list.sorting.header')]
class ReservationItemsHeaderCallback
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke(array $labels, DataContainer $dc): array
    {
        $labels['Status'] = $GLOBALS['TL_LANG']['tl_dc_reservation']['itemStatus'][$labels['Status']];

        return $labels;
    }
}
