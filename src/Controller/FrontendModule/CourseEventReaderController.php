<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseStudentsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcStudentsModel;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use function is_array;

#[AsFrontendModule('dc_course_event_reader', category: 'dc_manager', template: 'frontend_module/mod_dc_course_event_reader')]
class CourseEventReaderController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->notFound = false;
        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'tag_name' => $headline['unit'] ?? 'h1'
            ];
        }

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
            'dateStart' => $event->dateStart ? Date::parse($dateFormat, (int)$event->dateStart) : '',
            'dateEnd' => $event->dateEnd ? Date::parse($dateFormat, (int)$event->dateEnd) : '',
            'price' => (string)$event->price,
            'instructor' => (string)$event->instructor,
            'description' => (string)$event->description,
        ];

        // Request Token für Twig bereitstellen
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // Zeitplan laden (mit Modul- und Übungsnamen)
        $schedule = System::getContainer()->get('database_connection')->fetchAllAssociative(
            'SELECT s.id, s.planned_at, s.location, s.notes, m.title AS module_title, e.title AS exercise_title
             FROM tl_dc_course_event_schedule s
             INNER JOIN tl_dc_course_modules m ON m.id = s.module_id
             INNER JOIN tl_dc_course_exercises e ON e.id = s.exercise_id
             WHERE s.pid = ? AND s.published = 1
             ORDER BY s.planned_at, m.sorting, e.sorting',
            [(int)$event->id]
        );

        $rows = [];
        foreach ($schedule as $row) {
            $rows[] = [
                'planned_at' => $row['planned_at'] ? Date::parse($dateFormat, (int)$row['planned_at']) : '',
                'location' => (string)$row['location'],
                'notes' => (string)$row['notes'],
                'module' => (string)$row['module_title'],
                'exercise' => (string)$row['exercise_title'],
            ];
        }
        $template->schedule = $rows;
        $template->hasSchedule = !empty($rows);

        // Labels für das Anmeldeformular bereitstellen
        $signupLabels = $GLOBALS['TL_LANG']['MSC']['dc_event_signup'] ?? null;
        $template->signup = $signupLabels ?: [
            'headline' => 'Anmeldung zur Kursveranstaltung',
            'gender' => 'Anrede',
            'firstname' => 'Vorname',
            'lastname' => 'Nachname',
            'street' => 'Straße',
            'postal' => 'PLZ',
            'city' => 'Wohnort',
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
            $student = DcStudentsModel::findOneByMemberId((int)$user->id);
            if ($student !== null) {
                $studentId = (int)$student->id;
            }
        }
        $template->hasStudent = $studentId !== null;

        // Prüfen, ob bereits angemeldet
        $alreadyRegistered = false;
        $assignmentId = null;
        if ($studentId) {
            $check = DcCourseStudentsModel::findOneBy(['pid=?', 'event_id=?'], [$studentId, (int)$event->id]);
            if ($check !== null) {
                $alreadyRegistered = true;
                $assignmentId = (int)$check->id;
            }
        }
        $template->alreadyRegistered = $alreadyRegistered;
        $template->assignmentId = $assignmentId;

        // Debug-Log
        System::getContainer()->get('monolog.logger.contao.general')->info('CourseEventReaderController::getResponse called. Method: ' . $request->getMethod() . ', FORM_SUBMIT: ' . Input::post('FORM_SUBMIT'));

        // Verarbeitung der Anmeldung (mit CSRF-Validierung)
        if (Input::post('FORM_SUBMIT') === 'dc_event_signup' && !$alreadyRegistered) {
            // CSRF prüfen – bei ungültigem Token abbrechen und Meldung setzen
            $tokenValue = (string)Input::post('REQUEST_TOKEN');
            $container = System::getContainer();
            $tokenId = $container->getParameter('contao.csrf_token_name');
            $isValidToken = $container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($tokenId, $tokenValue));

            if (!$isValidToken) {
                System::getContainer()->get('monolog.logger.contao.general')->error('CSRF-Token Validierung fehlgeschlagen für dc_event_signup. Token: ' . substr($tokenValue, 0, 8) . '...');
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
                $gender = trim((string)Input::post('gender'));
                $firstname = trim((string)Input::post('firstname'));
                $lastname = trim((string)Input::post('lastname'));
                $street = trim((string)Input::post('street'));
                $postal = trim((string)Input::post('postal'));
                $city = trim((string)Input::post('city'));
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
                if ($street === '') {
                    $errors[] = 'Bitte geben Sie Ihre Straße ein.';
                }
                if ($postal === '') {
                    $errors[] = 'Bitte geben Sie Ihre PLZ ein.';
                }
                if ($city === '') {
                    $errors[] = 'Bitte geben Sie Ihren Wohnort ein.';
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
                $existingStudent = DcStudentsModel::findOneByEmail($email);
                if ($existingStudent !== null) {
                    $currentStudentId = (int)$existingStudent->id;
                    // Falls eingeloggt und Schüler hat noch keine memberId, jetzt verknüpfen
                    if ($template->isLoggedIn && (int)$existingStudent->memberId === 0) {
                        $existingStudent->memberId = (int)$user->id;
                        $existingStudent->save();
                    }
                } else {
                    // Schüler anlegen
                    try {
                        $birthdateTs = strtotime($birthdate);

                        $newStudent = new DcStudentsModel();
                        $newStudent->tstamp = time();
                        $newStudent->gender = $gender;
                        $newStudent->firstname = $firstname;
                        $newStudent->lastname = $lastname;
                        $newStudent->street = $street;
                        $newStudent->postal = $postal;
                        $newStudent->city = $city;
                        $newStudent->email = $email;
                        $newStudent->phone = $phone;
                        $newStudent->dateOfBirth = $birthdateTs ? (string)$birthdateTs : '';
                        $newStudent->memberId = $template->isLoggedIn ? (int)$user->id : 0;
                        $newStudent->published = '1';
                        $newStudent->save();

                        $currentStudentId = (int)$newStudent->id;
                        System::getContainer()->get('monolog.logger.contao.general')->info('Gast-Schüler erfolgreich angelegt: ID ' . $currentStudentId);
                    } catch (Exception $e) {
                        System::getContainer()->get('monolog.logger.contao.general')->error('Fehler beim Anlegen des Gast-Schülers: ' . $e->getMessage());
                        $this->addHtml5Message('Fehler beim Speichern Ihrer Daten.', 'error');
                        return $template->getResponse();
                    }
                }
            } else {
                // Eingeloggt (Member-Modus): Es wurde kein Formular ausgefüllt, wir nutzen die Daten des Users
                if ($currentStudentId === null) {
                    // Automatisch einen Schüler-Datensatz anlegen
                    try {
                        $dob = (string)$user->dateOfBirth;
                        if ($dob !== '' && !is_numeric($dob)) {
                            $dobTs = strtotime($dob);
                            $dob = $dobTs ? (string)$dobTs : '';
                        }

                        $newStudent = new DcStudentsModel();
                        $newStudent->tstamp = time();
                        $newStudent->gender = (string)$user->gender;
                        $newStudent->firstname = (string)$user->firstname;
                        $newStudent->lastname = (string)$user->lastname;
                        $newStudent->street = (string)$user->street;
                        $newStudent->postal = (string)$user->postal;
                        $newStudent->city = (string)$user->city;
                        $newStudent->email = (string)$user->email;
                        $newStudent->phone = (string)$user->phone;
                        $newStudent->dateOfBirth = $dob;
                        $newStudent->memberId = (int)$user->id;
                        $newStudent->published = '1';
                        $newStudent->save();

                        $currentStudentId = (int)$newStudent->id;
                    } catch (Exception $e) {
                        System::getContainer()->get('monolog.logger.contao.general')->error('Fehler beim automatischen Schüler-Insert: ' . $e->getMessage());
                    }
                }
            }

            // WICHTIG: Prüfung, ob die ID jetzt gesetzt ist
            if (!$currentStudentId) {
                $this->addHtml5Message('Fehler beim Erstellen des Schüler-Profils.', 'error');
                return $template->getResponse();
            }

            // Falls bereits zugewiesen (Rennbedingungen), nochmal prüfen
            $check2 = DcCourseStudentsModel::findOneBy(['pid=?', 'event_id=?'], [(int)$currentStudentId, (int)$event->id]);
            if ($check2 !== null) {
                $template->alreadyRegistered = true;
                $template->assignmentId = (int)$check2->id;
                $this->addHtml5Message('Sie sind bereits für diese Veranstaltung angemeldet.', 'info');
                return $template->getResponse();
            }
            // Zuweisung anlegen
            try {
                $db = System::getContainer()->get('database_connection');
                $logger = System::getContainer()->get('monolog.logger.contao.general');

                $logger->info('Starte Kurs-Zuweisung für Student ID ' . $currentStudentId . ', Event ID ' . $event->id . ', Course ID ' . $event->course_id);

                $db->insert('tl_dc_course_students', [
                    'pid' => (int)$currentStudentId,
                    'tstamp' => time(),
                    'course_id' => (int)$event->course_id ?: (int)Input::post('course_id'),
                    'event_id' => (int)$event->id,
                    'status' => 'registered',
                    'registered_on' => (string)time(),
                    'published' => '1'
                ]);

                $newAssignmentId = (int)$db->lastInsertId();
                $logger->info('Kurs-Zuweisung erfolgreich angelegt. Neue ID: ' . $newAssignmentId);
            } catch (Exception $e) {
                System::getContainer()->get('monolog.logger.contao.general')->error('Fehler beim Anlegen der Kurs-Zuweisung: ' . $e->getMessage());
                $this->addHtml5Message('Fehler bei der Kursanmeldung.', 'error');
                return $template->getResponse();
            }

            // Debug-Log
            System::getContainer()->get('monolog.logger.contao.general')->info('Kursanmeldung erfolgreich: Student ID ' . $currentStudentId . ', Assignment ID ' . $newAssignmentId);

            // Übungen erzeugen
            $this->generateExercises($newAssignmentId, (int)$event->course_id);

            // Speichere die ID in der Session für Insert-Tags
            $request->getSession()->set('last_course_order', $newAssignmentId);

            // Bestätigungs-Meldung setzen
            $this->addHtml5Message('Erfolgreich zur Veranstaltung angemeldet.', 'confirm');

            // Weiterleitung zur Bestätigungsseite (jumpTo)
            $jumpTo = (int)($model->jumpTo ?? 0);
            if ($jumpTo > 0 && ($jumpToPage = PageModel::findByPk($jumpTo)) !== null) {
                return new RedirectResponse($jumpToPage->getFrontendUrl());
            }

            // Refresh Status für das aktuelle Template (falls kein Redirect zu anderer Seite erfolgt)
            $template->alreadyRegistered = true;
            $template->assignmentId = $newAssignmentId;

            // Redirect auf die aktuelle Seite um POST-Resubmission zu verhindern und Nachrichten anzuzeigen
            return new RedirectResponse($request->getUri());
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

    private function generateExercises(int $assignmentId, int $courseId): void
    {
        $db = System::getContainer()->get('database_connection');
        $logger = System::getContainer()->get('monolog.logger.contao.general');
        $logger->info('Erzeuge Übungen für Assignment ID ' . $assignmentId . ' und Course ID ' . $courseId);

        // 1. Alle Übungen des Kurs-Templates finden (über die Module)
        $exercises = $db->fetchAllAssociative("
            SELECT e.id
            FROM tl_dc_course_exercises e
            JOIN tl_dc_course_modules m ON e.pid = m.id
            WHERE m.pid = ?
        ", [$courseId]);

        $logger->info('Gefundene Übungen für Kurs ' . $courseId . ': ' . count($exercises));

        foreach ($exercises as $exercise) {
            try {
                // 2. Prüfen, ob die Übung für diese Zuweisung schon existiert (analog zum Backend)
                $check = $db->fetchOne("SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=?", [$assignmentId, $exercise['id']]);

                if (!$check) {
                    // 3. Übung als 'pending' anlegen
                    $db->insert('tl_dc_student_exercises', [
                        'pid' => $assignmentId,
                        'tstamp' => time(),
                        'exercise_id' => $exercise['id'],
                        'status' => 'pending',
                        'published' => '1'
                    ]);

                    $newExId = $db->lastInsertId();
                    $logger->info('Schüler-Übung angelegt: Assignment ' . $assignmentId . ', Exercise ' . $exercise['id'] . ', New ID ' . $newExId);
                }
            } catch (Exception $e) {
                $logger->error('Fehler beim Erzeugen der Schüler-Übung: ' . $e->getMessage());
            }
        }
    }
}
