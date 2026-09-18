<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\NotificationType;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

class CourseScheduleUpdateNotificationType implements NotificationTypeInterface
{
    public const NAME = 'dc_course_schedule_update';

    public function __construct(private readonly TokenDefinitionFactoryInterface $tokenDefinitionFactory)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            $this->tokenDefinitionFactory->create(EmailTokenDefinition::class, 'student_email', 'dc_course_schedule_update.student_email'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'student_firstname', 'dc_course_schedule_update.student_firstname'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'student_lastname', 'dc_course_schedule_update.student_lastname'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'student_name', 'dc_course_schedule_update.student_name'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'event_title', 'dc_course_schedule_update.event_title'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'module_title', 'dc_course_schedule_update.module_title'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'planned_at', 'dc_course_schedule_update.planned_at'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'location', 'dc_course_schedule_update.location'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'instructor_name', 'dc_course_schedule_update.instructor_name'),
            $this->tokenDefinitionFactory->create(HtmlTokenDefinition::class, 'schedule_html', 'dc_course_schedule_update.schedule_html'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'schedule_text', 'dc_course_schedule_update.schedule_text'),
            $this->tokenDefinitionFactory->create(HtmlTokenDefinition::class, 'current_schedule_html', 'dc_course_schedule_update.current_schedule_html'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'current_schedule_text', 'dc_course_schedule_update.current_schedule_text'),
            $this->tokenDefinitionFactory->create(HtmlTokenDefinition::class, 'changed_schedule_html', 'dc_course_schedule_update.changed_schedule_html'),
            $this->tokenDefinitionFactory->create(TextTokenDefinition::class, 'changed_schedule_text', 'dc_course_schedule_update.changed_schedule_text'),
        ];
    }
}
