<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\InsertTag;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseStudentsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcStudentsModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('course')]
class DcCourseInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return new InsertTagResult('', OutputType::text);
        }

        $assignmentId = $request->getSession()->get('last_course_order');

        if (!$assignmentId) {
            return new InsertTagResult('', OutputType::text);
        }

        $assignment = DcCourseStudentsModel::findByPk($assignmentId);

        if (null === $assignment) {
            return new InsertTagResult('', OutputType::text);
        }

        $property = $insertTag->getParameters()->get(0);

        if (!$property) {
            return new InsertTagResult('', OutputType::text);
        }

        $value = null;

        // Check assignment properties first
        if (isset($assignment->$property) && $assignment->$property !== null) {
            $value = $assignment->$property;
        } else {
            // Check student (parent) properties
            $student = DcStudentsModel::findByPk($assignment->pid);

            if (null !== $student && isset($student->$property) && $student->$property !== null) {
                $value = $student->$property;
            } else {
                // Check event properties
                $event = DcCourseEventModel::findByPk($assignment->event_id);

                if (null !== $event && isset($event->$property) && $event->$property !== null) {
                    $value = $event->$property;
                }
            }
        }

        if (null === $value) {
            return new InsertTagResult('', OutputType::text);
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'dateStart', 'dateEnd', 'registered_on', 'dateOfBirth'], true)) {
            $value = Date::parse(Config::get('datimFormat'), (int) $value);
        }

        return new InsertTagResult((string) $value, OutputType::text);
    }
}
