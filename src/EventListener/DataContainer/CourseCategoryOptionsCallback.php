<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

#[AsCallback(table: 'tl_dc_dive_course', target: 'fields.category.options')]
class CourseCategoryOptionsCallback
{
    public function __construct(private DcaTemplateHelper $templateHelper)
    {
    }

    public function __invoke(): array
    {
        return $this->templateHelper->getCourseCategories();
    }
}
