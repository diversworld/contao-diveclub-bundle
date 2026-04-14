<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;
use Contao\Input;
use Contao\Controller;

#[AsCallback(table: 'tl_dc_check_order', target: 'config.onload')]
class OrderTankDataListener
{
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id || 'edit' !== Input::get('act')) {
            return;
        }

        $objOrder = Database::getInstance()
            ->prepare("SELECT * FROM tl_dc_check_order WHERE id=?")
            ->limit(1)
            ->execute($dc->id);

        if ($objOrder->numRows < 1) {
            return;
        }

        $tankId = (int)$objOrder->tankId;

        // Wenn tankId im POST steht (durch submitOnChange), hat POST Priorität
        if (Input::post('tankId') !== null) {
            $tankId = (int)Input::post('tankId');
        }

        if ($tankId > 0) {
            $objTank = Database::getInstance()
                ->prepare("SELECT * FROM tl_dc_tanks WHERE id=?")
                ->limit(1)
                ->execute($tankId);

            if ($objTank->numRows > 0) {
                $set = [];
                $update = false;

                // Wenn sich die tankId geändert hat oder Felder leer sind, Daten übernehmen
                $isNewTank = ($tankId !== (int)$objOrder->tankId);

                if ($isNewTank || !$objOrder->serialNumber) {
                    $set['serialNumber'] = $objTank->serialNumber;
                    $update = true;
                }

                if ($isNewTank || !$objOrder->manufacturer) {
                    $set['manufacturer'] = $objTank->manufacturer;
                    $update = true;
                }

                if ($isNewTank || !$objOrder->bazNumber) {
                    $set['bazNumber'] = $objTank->bazNumber;
                    $update = true;
                }

                if ($isNewTank || !$objOrder->size) {
                    $set['size'] = $objTank->size;
                    $update = true;
                }

                if ($isNewTank || (bool)$objTank->o2clean !== (bool)$objOrder->o2clean) {
                    $set['o2clean'] = $objTank->o2clean ? '1' : '';
                    $update = true;
                }

                if ($isNewTank) {
                    $set['tankId'] = $tankId;
                }

                if ($update) {
                    Database::getInstance()
                        ->prepare("UPDATE tl_dc_check_order %s WHERE id=?")
                        ->set($set)
                        ->execute($dc->id);

                    // Seite neu laden, damit die Werte im Formular erscheinen
                    Controller::reload();
                }
            }
        }
    }
}
