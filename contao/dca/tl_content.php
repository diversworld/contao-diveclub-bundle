<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

use Diversworld\ContaoDiveclubBundle\Controller\ContentElement\DcListingController;

/**
 * Content elements
 */
$GLOBALS['TL_DCA']['tl_content']['palettes'][DcListingController::TYPE] = '{type_legend},type,headline;{text_legend},text;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
