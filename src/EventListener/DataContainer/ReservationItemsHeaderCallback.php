<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation_items', target: 'list.sorting.header')]
class ReservationItemsHeaderCallback
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * Adjust parent header fields for tl_dc_reservation to show readable member names instead of IDs.
     */
    public function __invoke(array $labels, DataContainer $dc): array
    {
        // Parent (reservation) id from URL context
        $parentId = (int) (Input::get('id') ?? 0);
        if ($parentId <= 0) {
            return $labels;
        }

        // Load reservation row
        $reservation = $this->db->fetchAssociative(
            'SELECT id, title, member_id, reservedFor, reservation_status, reserved_at FROM tl_dc_reservation WHERE id = ?',
            [$parentId]
        );

        if (!$reservation) {
            return $labels;
        }

        // Resolve members
        $memberIds = [];
        if (!empty($reservation['member_id'])) {
            $memberIds[] = (int) $reservation['member_id'];
        }
        if (!empty($reservation['reservedFor']) && (int)$reservation['reservedFor'] !== (int)$reservation['member_id']) {
            $memberIds[] = (int) $reservation['reservedFor'];
        }

        $members = [];
        if ($memberIds) {
            $in = implode(',', array_fill(0, count($memberIds), '?'));
            $rows = $this->db->fetchAllAssociative('SELECT id, firstname, lastname FROM tl_member WHERE id IN ('.$in.')', $memberIds);
            foreach ($rows as $row) {
                $members[(int)$row['id']] = trim((string)$row['firstname'].' '.(string)$row['lastname']);
            }
        }

        // Map readable values
        $memberName = $members[(int)$reservation['member_id']] ?? (string)$reservation['member_id'];
        $reservedForName = $members[(int)$reservation['reservedFor']] ?? (string)$reservation['reservedFor'];

        // Translate status if available
        $status = (string)($reservation['reservation_status'] ?? '');
        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$status] ?? $status;

        // Format dates
        System::loadLanguageFile('default');
        $datimFormat = $GLOBALS['TL_CONFIG']['datimFormat'] ?? 'd.m.Y H:i';
        $createdAt = !empty($reservation['reserved_at']) ? date($datimFormat, (int)$reservation['reserved_at']) : '';
        $updatedAt = !empty($reservation['updated_at']) ? date($datimFormat, (int)$reservation['updated_at']) : '';

        // Build header labels using language labels where possible
        System::loadLanguageFile('tl_dc_reservation');
        $map = [
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['title'][0] ?? 'Titel') => (string)$reservation['title'],
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['member_id'][0] ?? 'Mitglied') => $memberName,
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['reservedFor'][0] ?? 'Reserviert für') => $reservedForName,
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_status'][0] ?? 'Status') => $statusLabel,
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['reserved_at'][0] ?? 'Reserviert am') => $createdAt,
            ($GLOBALS['TL_LANG']['tl_dc_reservation']['updated_at'][0] ?? 'Aktualisiert am') => $updatedAt,
        ];

        // Return as labels array; Contao expects an associative array mapping label => value
        return $map;
    }
}
