<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

#[AsCallback(table: 'tl_dc_dive_course', target: 'fields.course_type.options')]
class CourseTypeOptionsCallback
{
    public function __construct(private DcaTemplateHelper $templateHelper)
    {
    }

    public function __invoke(): array
    {
        return $this->templateHelper->getCourseTypes();
    }
}
