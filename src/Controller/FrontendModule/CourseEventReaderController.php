<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

#[AsFrontendModule('dc_course_event_reader', category: 'dc_manager', template: 'mod_dc_course_event_reader')]
class CourseEventReaderController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $db = Database::getInstance();

        $identifier = Input::get('event') ?: Input::get('items');
        if (!$identifier) {
            $template->notFound = true;
            return $template->getResponse();
        }

        // Per ID oder Alias laden
        if (is_numeric($identifier)) {
            $event = DcCourseEventModel::findByPk((int)$identifier);
        } else {
            $event = DcCourseEventModel::findOneBy(['alias=?', 'published=?'], [$identifier, 1]);
        }

        if (!$event || (int)$event->published !== 1) {
            $template->notFound = true;
            return $template->getResponse();
        }

        $dateFormat = Config::get('datimFormat');
        $template->event = [
            'id' => (int)$event->id,
            'title' => (string)$event->title,
            'alias' => (string)$event->alias,
            'dateStart' => $event->dateStart ? Date::parse($dateFormat, (int)strtotime((string)$event->dateStart)) : '',
            'dateEnd' => $event->dateEnd ? Date::parse($dateFormat, (int)strtotime((string)$event->dateEnd)) : '',
            'price' => (string)$event->price,
            'instructor' => (string)$event->instructor,
            'description' => (string)$event->description,
        ];

        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // Zeitplan laden (mit Modul- und Übungsnamen)
        $schedule = $db->prepare(
            'SELECT s.id, s.planned_at, s.location, s.notes, m.title AS module_title, e.title AS exercise_title
             FROM tl_dc_course_event_schedule s
             INNER JOIN tl_dc_course_modules m ON m.id = s.module_id
             INNER JOIN tl_dc_course_exercises e ON e.id = s.exercise_id
             WHERE s.pid = ? AND s.published = 1
             ORDER BY s.planned_at, m.sorting, e.sorting'
        )->execute((int)$event->id);

        $rows = [];
        while ($schedule->next()) {
            $rows[] = [
                'planned_at' => $schedule->planned_at ? Date::parse($dateFormat, (int)strtotime((string)$schedule->planned_at)) : '',
                'location' => (string)$schedule->location,
                'notes' => (string)$schedule->notes,
                'module' => (string)$schedule->module_title,
                'exercise' => (string)$schedule->exercise_title,
            ];
        }
        $template->schedule = $rows;
        $template->hasSchedule = !empty($rows);

        // Labels für das Anmeldeformular bereitstellen
        $signupLabels = $GLOBALS['TL_LANG']['MSC']['dc_event_signup'] ?? null;
        $template->signup = $signupLabels ?: [
            'headline' => 'Anmeldung zur Kursveranstaltung',
            'firstname' => 'Vorname',
            'lastname' => 'Nachname',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'birthdate' => 'Geburtsdatum',
            'privacy' => 'Ich stimme der Verarbeitung meiner Daten zu.',
            'submit' => 'Jetzt anmelden',
        ];

        // Anmeldung: eingeloggte Member (bestehend) ODER Gäste (neu)
        /** @var FrontendUser|null $user */
        $user = System::getContainer()->get('security.helper')->getUser();
        $template->isLoggedIn = ($user instanceof FrontendUser);

        $studentId = null;
        if ($template->isLoggedIn) {
            $student = $db->prepare('SELECT id FROM tl_dc_students WHERE memberId=?')->execute((int)$user->id);
            if ($student->numRows > 0) {
                $studentId = (int)$student->id;
            }
        }
        $template->hasStudent = $studentId !== null;

        // Prüfen, ob bereits angemeldet
        $alreadyRegistered = false;
        $assignmentId = null;
        if ($studentId) {
            $check = $db->prepare('SELECT id FROM tl_dc_course_students WHERE pid=? AND event_id=?')
                ->execute($studentId, (int)$event->id);
            if ($check->numRows > 0) {
                $alreadyRegistered = true;
                $assignmentId = (int)$check->id;
            }
        }
        $template->alreadyRegistered = $alreadyRegistered;
        $template->assignmentId = $assignmentId;

        // Verarbeitung der Anmeldung (mit CSRF-Validierung)
        if (Input::post('FORM_SUBMIT') === 'dc_event_signup' && !$alreadyRegistered) {
            // CSRF prüfen – bei ungültigem Token abbrechen und Meldung setzen
            if (!System::getContainer()->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken('contao_frontend', Input::post('REQUEST_TOKEN')))) {
                $this->addHtml5Message('Ungültiges Request-Token. Bitte Seite neu laden und erneut versuchen.', 'error');
                return $template->getResponse();
            }

            // Honeypot (Spam) – wenn gefüllt, abbrechen
            if (trim((string)Input::post('website')) !== '') {
                $this->addHtml5Message('Ihre Anmeldung konnte nicht verarbeitet werden.', 'error');
                return $template->getResponse();
            }

            $currentStudentId = $studentId; // kann null sein (Gast)
            $isGuest = !$template->isLoggedIn || Input::post('mode') === 'guest';

            if ($isGuest) {
                // Gastfelder einlesen und validieren
                $firstname = trim((string)Input::post('firstname'));
                $lastname = trim((string)Input::post('lastname'));
                $email = trim((string)Input::post('email'));
                $phone = trim((string)Input::post('phone'));
                $birthdate = trim((string)Input::post('birthdate'));
                $privacy = (bool)Input::post('privacy');

                $errors = [];
                if ($firstname === '') {
                    $errors[] = 'Bitte geben Sie Ihren Vornamen ein.';
                }
                if ($lastname === '') {
                    $errors[] = 'Bitte geben Sie Ihren Nachnamen ein.';
                }
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Bitte geben Sie eine gültige E‑Mail ein.';
                }
                if (!$privacy) {
                    $errors[] = 'Bitte akzeptieren Sie die Datenschutzbestimmungen.';
                }

                if (!empty($errors)) {
                    foreach ($errors as $err) {
                        $this->addHtml5Message($err, 'error');
                    }
                    return $template->getResponse();
                }

                // Dublettenprüfung: existiert Schüler mit gleicher E‑Mail?
                $stu = $db->prepare('SELECT id FROM tl_dc_students WHERE email=?')
                    ->execute($email);
                if ($stu->numRows > 0) {
                    $currentStudentId = (int)$stu->id;
                } else {
                    // Schüler anlegen (ohne tl_member)
                    $db->prepare('INSERT INTO tl_dc_students (tstamp, firstname, lastname, email, phone, dateOfBirth, published) VALUES (?, ?, ?, ?, ?, ?, ?)')
                        ->execute(time(), $firstname, $lastname, $email, $phone, $birthdate, 1);
                    $currentStudentId = (int)$db->insertId;
                }
            } else {
                // Eingeloggt: es muss ein Student vorhanden sein
                if ($currentStudentId === null) {
                    $this->addHtml5Message('Für Ihr Konto ist kein Tauchschüler hinterlegt.', 'error');
                    return $template->getResponse();
                }
            }

            // Falls bereits zugewiesen (Rennbedingungen), nochmal prüfen
            $check2 = $db->prepare('SELECT id FROM tl_dc_course_students WHERE pid=? AND event_id=?')
                ->execute((int)$currentStudentId, (int)$event->id);
            if ($check2->numRows > 0) {
                $template->alreadyRegistered = true;
                $template->assignmentId = (int)$check2->id;
                $this->addHtml5Message('Sie sind bereits für diese Veranstaltung angemeldet.', 'info');
                return $template->getResponse();
            }

            // Zuweisung anlegen
            $db->prepare('INSERT INTO tl_dc_course_students (pid, tstamp, course_id, event_id, status, registered_on, published) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(
                    (int)$currentStudentId,
                    time(),
                    (int)$event->course_id,
                    (int)$event->id,
                    'registered',
                    time(),
                    1
                );

            $newAssignmentId = (int)$db->insertId;

            // Übungen erzeugen (kopiert aus generateDefaultExercises-Logik)
            $ex = $db->prepare(
                'SELECT e.id FROM tl_dc_course_exercises e
                 INNER JOIN tl_dc_course_modules m ON e.pid = m.id
                 WHERE m.pid = ?'
            )->execute((int)$event->course_id);

            while ($ex->next()) {
                $exists = $db->prepare('SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=?')
                    ->execute($newAssignmentId, (int)$ex->id);
                if ($exists->numRows < 1) {
                    $db->prepare('INSERT INTO tl_dc_student_exercises (pid, tstamp, exercise_id, status, published) VALUES (?, ?, ?, ?, ?)')
                        ->execute($newAssignmentId, time(), (int)$ex->id, 'pending', 1);
                }
            }

            $this->addHtml5Message('Erfolgreich zur Veranstaltung angemeldet.', 'confirm');

            // Refresh Status
            $template->alreadyRegistered = true;
            $template->assignmentId = $newAssignmentId;
        }

        return $template->getResponse();
    }

    private function addHtml5Message(string $message, string $type): void
    {
        $container = System::getContainer();
        $session = $container->get('request_stack')->getSession();
        $flashBag = $session->getFlashBag();

        $flashBag->add('contao.FE.' . $type, $message);
    }
}
