<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.onload')]
class BookingMemberDataListener
{
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id || 'edit' !== \Contao\Input::get('act')) {
            return;
        }

        $objBooking = Database::getInstance()
            ->prepare("SELECT * FROM tl_dc_check_booking WHERE id=?")
            ->limit(1)
            ->execute($dc->id);

        if ($objBooking->numRows < 1) {
            return;
        }

        $memberId = (int)$objBooking->memberId;

        // Wenn memberId geändert wurde (durch submitOnChange), hat POST Priorität
        if (\Contao\Input::post('memberId')) {
            $memberId = (int)\Contao\Input::post('memberId');
        }

        if ($memberId > 0) {
            $objMember = Database::getInstance()
                ->prepare("SELECT firstname, lastname, email, phone, mobile FROM tl_member WHERE id=?")
                ->limit(1)
                ->execute($memberId);

            if ($objMember->numRows > 0) {
                $set = [];
                $update = false;

                // Wenn sich die Mitglieds-ID geändert hat, überschreiben wir die Felder immer
                $isNewMember = ($memberId !== (int)$objBooking->memberId);

                if ($isNewMember || !$objBooking->firstname) {
                    $set['firstname'] = $objMember->firstname;
                    $update = true;
                }

                if ($isNewMember || !$objBooking->lastname) {
                    $set['lastname'] = $objMember->lastname;
                    $update = true;
                }

                if ($isNewMember || !$objBooking->email) {
                    $set['email'] = $objMember->email;
                    $update = true;
                }

                if ($isNewMember || !$objBooking->phone) {
                    $set['phone'] = $objMember->phone ?: $objMember->mobile;
                    $update = true;
                }

                if ($isNewMember) {
                    $set['memberId'] = $memberId;
                }

                if ($update) {
                    Database::getInstance()
                        ->prepare("UPDATE tl_dc_check_booking %s WHERE id=?")
                        ->set($set)
                        ->execute($dc->id);

                    // Reload the page to show the updated values in the form
                    \Contao\Controller::reload();
                }
            }
        }
    }
}
