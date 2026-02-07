<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\MemberModel;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation_items', target: 'list.label.label')]
class ReservationLabelCallback
{
    private Connection $db;

    public function __construct(Connection $db, DcaTemplateHelper $helper)
    {
        $this->db = $db;
    }

    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            // If it's a column view, we might want to return $args,
            // but the original code was returning a string regardless of showColumns.
            // Contao says: "If the DCA uses showColumns then the return value must be an array of strings. Otherwise just the label as a string."
            // Since tl_dc_reservation_items HAS showColumns => true, it SHOULD return an array.
            //['title', 'member_id', 'reservedFor', 'reservation_status', 'reserved_at', 'rentalFee'],
            // Let's adapt it to return an array if $args is provided.
            $labels = $args;

            $member = MemberModel::findById((int)$row['member_id']);
            $reservedFor = MemberModel::findById((int)$row['reservedFor']);

            $labels[1] = $member->firstname . ' ' . $member->lastname;
            $labels[2] = $reservedFor->firstname . ' ' . $reservedFor->lastname;
            $labels[3] = $GLOBALS['TL_LANG']['tl_dc_reservation']['itemStatus'][$row['reservation_status']] ?? $row['reservation_status'];
            $labels[4] = !empty($row['reserved_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['reserved_at']) : '-';
            $labels[5] = number_format((float)$row['rentalFee'], 2, ',', '.') . ' €';

            return $labels;
        }
        return $label;
    }
}
