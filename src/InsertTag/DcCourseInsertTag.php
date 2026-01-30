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
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;
use Diversworld\ContaoDiveclubBundle\Model\DcStudentsModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('course')]
class DcCourseInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult // Hauptmethode des Insert-Tag-Resolvers
    {
        $request = $this->requestStack->getCurrentRequest(); // Hole den aktuellen Request aus dem RequestStack
        if (null === $request) { // Falls kein Request vorhanden ist
            return new InsertTagResult('', OutputType::text); // Gib leeres Ergebnis zurück
        }

        $assignmentId = $request->getSession()->get('last_course_order'); // Hole die ID der letzten Kurs-Zuweisung aus der Session
        if (!$assignmentId) { // Falls keine Zuweisungs-ID vorhanden ist
            return new InsertTagResult('', OutputType::text); // Gib leeres Ergebnis zurück
        }

        $assignment = DcCourseStudentsModel::findByPk($assignmentId); // Lade das Zuweisungs-Modell (Student zu Kurs)

        if (null === $assignment) { // Falls die Zuweisung nicht gefunden wurde
            return new InsertTagResult('', OutputType::text); // Gib leeres Ergebnis zurück
        }

        $property = $insertTag->getParameters()->get(0); // Hole den gewünschten Eigenschaftsnamen aus dem Insert-Tag

        if (!$property) { // Falls keine Eigenschaft angegeben wurde
            return new InsertTagResult('', OutputType::text); // Gib leeres Ergebnis zurück
        }

        $value = null; // Initialisiere den Wert

        // Check assignment properties first
        if (isset($assignment->$property) && $assignment->$property !== null && $assignment->$property !== '') { // Prüfe zuerst Eigenschaften der Zuweisung
            $value = $assignment->$property; // Setze den Wert
        }

        // Check student (parent) properties
        if (null === $value || $value === '') {
            $student = DcStudentsModel::findByPk($assignment->pid); // Lade den zugehörigen Studenten (Eltern-Datensatz)
            if (null !== $student && isset($student->$property) && $student->$property !== null && $student->$property !== '') { // Prüfe Eigenschaften des Studenten
                $value = $student->$property; // Setze den Wert
            }
        }

        // Check event properties
        if (null === $value || $value === '') {
            $event = DcCourseEventModel::findByPk($assignment->event_id); // Lade das zugehörige Kurs-Event
            if (null !== $event) {
                if (isset($event->$property) && $event->$property !== null && $event->$property !== '') { // Prüfe Eigenschaften des Events
                    $value = $event->$property; // Setze den Wert
                }

                // Fallback to course template if property not found on event
                if ((null === $value || $value === '') && $event->course_id > 0) {
                    $course = DcDiveCourseModel::findByPk($event->course_id);
                    if (null !== $course && isset($course->$property) && $course->$property !== null && $course->$property !== '') {
                        $value = $course->$property;
                    }
                }
            }
        }

        if (null === $value || $value === '') { // Falls kein Wert gefunden wurde
            return new InsertTagResult('', OutputType::text); // Gib leeres Ergebnis zurück
        }

        // Handle specific fields formatting
        if ($property === 'price' && $value !== '') {
            if (is_numeric($value)) {
                $value = number_format((float)$value, 2, ',', '.') . ' €';
            } elseif (strpos((string)$value, '€') === false) {
                $value = $value . ' €';
            }
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'dateStart', 'dateEnd', 'registered_on', 'dateOfBirth'], true)) { // Prüfe auf Datumsfelder
            if (is_numeric($value) && (int)$value > 0) {
                $value = Date::parse(Config::get('datimFormat'), (int) $value); // Formatiere den Zeitstempel
            }
        }

        return new InsertTagResult((string) $value, OutputType::text); // Gib den finalen Wert zurück
    }
}
