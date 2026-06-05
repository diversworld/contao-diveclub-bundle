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

namespace Diversworld\ContaoDiveclubBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\StringUtil;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as Twig;

#[AsContentElement(category: 'dc_equipment', template: 'ce_dc_listing')]
class DcListingController extends AbstractContentElementController
{
    public const TYPE = 'dc_listing';

    public function __construct(
        private readonly Twig $twig,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $headline = StringUtil::deserialize($model->headline);
        $headlineData = null;
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $headlineData = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1',
            ];
        }

        return new Response($this->twig->render(
            '@DiversworldContaoDiveclub/content_element/ce_dc_listing.html.twig',
            [
                'text' => $model->text,
                'headline' => $headlineData,
                'type' => $model->type,
            ]
        ));
    }
}
