<?php

declare(strict_types=1);

/*
 * This file is part of Resource Booking Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/resource-booking-bundle
 */

use Diversworld\ContaoDiveclubBundle\Controller\FrontendModule\ModuleBooking;
use Diversworld\ContaoDiveclubBundle\Controller\FrontendModule\ModuleEquipmentDetail;
use Diversworld\ContaoDiveclubBundle\Controller\FrontendModule\ModuleTanksDetail;
use Diversworld\ContaoDiveclubBundle\Controller\FrontendModule\DcListingController;

/*
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes'][DcListingController::TYPE] =
    '{title_legend},name,headline,type;
     {template_legend:hide},customTpl;
     {protected_legend:hide},protected;
     {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes'][ModuleTanksDetail::TYPE] =
    '{title_legend},name,headline,type;
     {template_legend:hide},customTpl;
     {protected_legend:hide},protected;
     {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes'][ModuleEquipmentDetail::TYPE] =
    '{title_legend},name,headline,type;
     {template_legend:hide},customTpl;
     {protected_legend:hide},protected;
     {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes'][ModuleBooking::TYPE] =
    '{title_legend},name,headline,type;
     {template_legend:hide},customTpl;
     {equipment_legend},dc_reservation;
     {protected_legend:hide},protected;
     {expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['dc_reservation'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_module']['dc_reservation'],
    'inputType'     => 'checkbox',  // Typ des Eingabefelds (z.B. 'text', 'select', 'checkbox')
    'exclude'       => true,
    'eval'          => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql'           => ['type' => 'boolean', 'default' => false]
];
