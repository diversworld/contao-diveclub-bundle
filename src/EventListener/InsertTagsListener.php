<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub.
 *
 * (c) DiversWorld 2025 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\EventListener;

use Contao\Config;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;
use Symfony\Component\HttpFoundation\RequestStack;

class InsertTagsListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $tag
     * @param bool   $useCache
     * @param string $cachedValue
     * @param array  $flags
     * @param array  $tags
     * @param array  $cache
     * @param int    $_rit
     * @param int    $_cnt
     *
     * @return string|false
     */
    public function onReplaceInsertTags(
        string $tag,
        bool $useCache,
        string $cachedValue,
        array $flags,
        array $tags,
        array $cache,
        int $_rit,
        int $_cnt
    ) {
        $elements = explode('::', $tag);

        if ('course' === $elements[0]) {
            if (!isset($elements[1], $elements[2])) {
                return false;
            }

            $courseId = (int) $elements[1];
            $field = $elements[2];

            $course = DcCourseEventModel::findByPk($courseId);

            if (null === $course) {
                return '';
            }

            // Return the field value if it exists
            if (isset($course->$field)) {
                $value = $course->$field;

                // Resolve course_id to dive course title
                if ('course_id' === $field) {
                    $diveCourse = DcDiveCourseModel::findByPk($value);
                    if (null !== $diveCourse) {
                        return (string) $diveCourse->title;
                    }
                }

                // Handle date fields
                if (in_array($field, ['dateStart', 'dateEnd', 'start', 'stop', 'tstamp'])) {
                    if (empty($value)) {
                        return '';
                    }
                    return Date::parse(Config::get('datimFormat'), (int) $value);
                }

                // Handle price
                if ('price' === $field && !empty($value)) {
                    return (string) $value . ' €';
                }

                return (string) $value;
            }
        }

        if ('tank_check_order' === $elements[0]) {
            $orderId = (int) ($elements[1] ?? 0);

            if (!$orderId) {
                $session = $this->requestStack->getCurrentRequest()?->getSession();
                $orderId = (int) $session?->get('last_tank_check_order');
            }

            if (!$orderId) {
                return '';
            }

            $order = DcCheckOrderModel::findByPk($orderId);
            if (null === $order) {
                return '';
            }

            $field = $elements[2] ?? $elements[1] ?? 'totalPrice';

            if (isset($order->$field)) {
                $value = $order->$field;

                if ('totalPrice' === $field) {
                    return number_format((float)$value, 2, ',', '.') . ' €';
                }

                if ('tstamp' === $field) {
                    return Date::parse(Config::get('datimFormat'), (int) $value);
                }

                return (string) $value;
            }

            if ('proposal_title' === $field) {
                $proposal = DcCheckProposalModel::findByPk($order->pid);
                return $proposal ? $proposal->title : '';
            }
        }

        return false;
    }
}
