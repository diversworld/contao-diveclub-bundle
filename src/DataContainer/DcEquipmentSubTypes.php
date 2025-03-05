<?php


declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;

class DcEquipmentSubTypes
{
    private ContaoFramework $framework;
    private TemplateService $templateService;

    public function __construct(ContaoFramework $framework, TemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->framework = $framework;
    }
}
