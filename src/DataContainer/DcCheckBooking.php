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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

#[AsCallback(table: 'tl_dc_check_booking', target: 'edit.buttons', priority: 100)]
class DcCheckBooking
{
    private ContaoFramework $framework;
    private RouterInterface $router;

    public function __construct(ContaoFramework $framework, RouterInterface $router)
    {
        $this->framework = $framework;
        $this->router = $router;
    }

    public function __invoke(array $arrButtons, DataContainer $dc): array
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $systemAdapter = $this->framework->getAdapter(System::class);

        $systemAdapter->loadLanguageFile('tl_dc_check_booking');

        if ('edit' === $inputAdapter->get('act') && $inputAdapter->post('pdfButton')) {
            $url = $this->router->generate('dc_check_order_pdf', ['id' => $dc->id]);
            (new RedirectResponse($url))->send();
            exit;
        }

        if ('edit' === $inputAdapter->get('act')) {
            $arrButtons['customButton'] = '<button type="submit" name="pdfButton" id="pdfButton" class="tl_submit pdfButton" accesskey="p">' . $GLOBALS['TL_LANG']['tl_dc_check_booking']['pdfButton'] . '</button>';
        }

        return $arrButtons;
    }
}
