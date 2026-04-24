<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Component\Routing\RouterInterface;

#[AsCallback(table: 'tl_dc_check_proposal', target: 'list.operations.tuv_list.button')]
class ProposalTuvListButtonListener
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function __invoke(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $url = $this->router->generate('dc_tuv_list_export', ['id' => $row['id']]);

        return '<a href="' . $url . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . ' target="_blank">' . Image::getHtml($icon, $label) . '</a> ';
    }
}
