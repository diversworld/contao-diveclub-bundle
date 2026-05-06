<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\Service\TankCheckPdfGenerator;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\RouterInterface;

class BookingListener
{
    public function __construct(
        private readonly Connection            $db,
        private readonly TankCheckPdfGenerator $pdfGenerator,
        private readonly RouterInterface       $router
    )
    {
    }

    #[AsCallback(table: 'tl_dc_check_booking', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, DataContainer $dc, ?array $args = null): string|array
    {
        return sprintf(
            '[%s] %s, %s - %s € - %s (%s)',
            $row['bookingNumber'],
            $row['lastname'],
            $row['firstname'],
            number_format((float)$row['totalPrice'], 2, ',', '.'),
            $GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'][$row['status']] ?? $row['status'],
            $row['bookingDate'] ? Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['bookingDate']) : '-'
        );
    }

    #[AsCallback(table: 'tl_dc_check_booking', target: 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        if (!$dc->id || 'edit' !== Input::get('act')) {
            return;
        }

        $rowBooking = $this->db->fetchAssociative("SELECT * FROM tl_dc_check_booking WHERE id=? LIMIT 1", [$dc->id]);

        if (!$rowBooking) {
            return;
        }

        $memberId = (int)$rowBooking['memberId'];

        if (Input::post('memberId') && is_numeric(Input::post('memberId'))) {
            $memberId = (int)Input::post('memberId');

            // Validate memberId exists
            $exists = $this->db->fetchOne("SELECT id FROM tl_member WHERE id=? LIMIT 1", [$memberId]);
            if (!$exists) {
                return;
            }
        }

        if ($memberId > 0) {
            $rowMember = $this->db->fetchAssociative("SELECT firstname, lastname, email, phone, mobile FROM tl_member WHERE id=? LIMIT 1", [$memberId]);

            if ($rowMember) {
                $set = [];
                $update = false;
                $isNewMember = ($memberId !== (int)$rowBooking['memberId']);

                if ($isNewMember || !$rowBooking['firstname']) {
                    $set['firstname'] = $rowMember['firstname'];
                    $update = true;
                }

                if ($isNewMember || !$rowBooking['lastname']) {
                    $set['lastname'] = $rowMember['lastname'];
                    $update = true;
                }

                if ($isNewMember || !$rowBooking['email']) {
                    $set['email'] = $rowMember['email'];
                    $update = true;
                }

                if ($isNewMember || !$rowBooking['phone']) {
                    $set['phone'] = $rowMember['phone'] ?: $rowMember['mobile'];
                    $update = true;
                }

                if ($isNewMember) {
                    $set['memberId'] = $memberId;
                    $update = true;
                }

                if ($update) {
                    $this->db->update('tl_dc_check_booking', $set, ['id' => $dc->id]);
                    Controller::reload();
                }
            }
        }
    }

    #[AsCallback(table: 'tl_dc_check_booking', target: 'config.oncreate')]
    public function onCreateCallback(string $table, int $insertId, array $set, DataContainer $dc): void
    {
        if ('tl_dc_check_booking' !== $table) {
            return;
        }

        $bookingNumber = '';
        $maxAttempts = 10;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $bookingNumber = 'TC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), -4));
            $exists = $this->db->fetchOne("SELECT id FROM tl_dc_check_booking WHERE bookingNumber=? LIMIT 1", [$bookingNumber]);

            if (!$exists) {
                break;
            }
            $attempt++;
        }

        if ($bookingNumber) {
            $this->db->executeStatement(
                "UPDATE tl_dc_check_booking SET bookingNumber=? WHERE id=?",
                [$bookingNumber, $insertId]
            );
        }
    }

    #[AsCallback(table: 'tl_dc_check_booking', target: 'list.operations.pdf.button')]
    public function onPdfButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $url = $this->router->generate('dc_check_order_pdf', ['id' => $row['id']]);

        return '<a href="' . $url . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . ' target="_blank">' . Image::getHtml($icon, $label) . '</a> ';
    }

    #[AsCallback(table: 'tl_dc_check_booking', target: 'config.onsubmit')]
    public function onSubmitCallback(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        // Sync status to orders
        $this->db->update(
            'tl_dc_check_order',
            ['status' => $dc->activeRecord->status],
            ['pid' => $dc->activeRecord->id]
        );

        // Generate PDF if status is 'pickedup'
        if ($dc->activeRecord->status === 'pickedup') {
            $this->pdfGenerator->generateForBooking((int)$dc->activeRecord->id);
        }
    }
}
