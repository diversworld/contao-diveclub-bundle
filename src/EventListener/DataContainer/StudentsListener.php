<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Date;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class StudentsListener
{
    public function __construct(
        private readonly Connection                  $connection,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RequestStack                $requestStack
    )
    {
    }

    /* tl_dc_students */

    #[AsCallback(table: 'tl_dc_students', target: 'list.label.label')]
    public function onStudentLabel(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            if ($args[2] && is_numeric($args[2])) {
                $args[2] = Date::parse(Config::get('dateFormat'), (int)$args[2]);
            }
            return $args;
        }

        $dob = ($row['dateOfBirth'] && is_numeric($row['dateOfBirth'])) ? Date::parse(Config::get('dateFormat'), (int)$row['dateOfBirth']) : ($row['dateOfBirth'] ?: '-');
        return sprintf('%s, %s (%s)', $row['lastname'], $row['firstname'], $dob);
    }

    #[AsCallback(table: 'tl_dc_students', target: 'config.onload')]
    public function onStudentLoad(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        if (isset($_SESSION['NEW_STUDENT_PASSWORD'][$dc->id])) {
            $password = $_SESSION['NEW_STUDENT_PASSWORD'][$dc->id];
            unset($_SESSION['NEW_STUDENT_PASSWORD'][$dc->id]);

            Message::addRaw('<div class="tl_info" style="border: 2px solid #86af35; padding: 20px; font-size: 1.2em;">
                <strong>WICHTIG: Neues Mitglied angelegt!</strong><br>
                Das vorläufige Passwort lautet: <code style="background:#eee; padding:2px 5px; border:1px solid #ccc;">' . $password . '</code>
            </div>');
        }

        $rowStudent = $this->connection->fetchAssociative("SELECT * FROM tl_dc_students WHERE id=? LIMIT 1", [$dc->id]);
        if (!$rowStudent) {
            return;
        }

        if (Input::post('memberId') && is_numeric(Input::post('memberId'))) {
            $postMemberId = (int)Input::post('memberId');
            if ($postMemberId > 0 && $postMemberId !== (int)$rowStudent['memberId']) {
                // Validate new memberId exists
                $exists = $this->connection->fetchOne("SELECT id FROM tl_member WHERE id=? LIMIT 1", [$postMemberId]);
                if ($exists) {
                    $this->connection->update('tl_dc_students', ['memberId' => $postMemberId], ['id' => $dc->id]);
                    $rowStudent['memberId'] = $postMemberId;
                }
            } elseif ($postMemberId === 0 && (int)$rowStudent['memberId'] > 0) {
                // Unlink member
                $this->connection->update('tl_dc_students', ['memberId' => 0], ['id' => $dc->id]);
                $rowStudent['memberId'] = 0;
            }
        }

        if ((int)$rowStudent['memberId'] > 0) {
            $GLOBALS['TL_DCA']['tl_dc_students']['fields']['allowLogin']['eval']['disabled'] = true;
            if ($rowStudent['allowLogin']) {
                $this->connection->update('tl_dc_students', ['allowLogin' => '0'], ['id' => $dc->id]);
                $rowStudent['allowLogin'] = '0';
            }
        }

        if ($rowStudent['allowLogin']) {
            $GLOBALS['TL_DCA']['tl_dc_students']['fields']['memberId']['eval']['disabled'] = true;
        }

        if (!(int)$rowStudent['memberId']) {
            return;
        }

        $rowMember = $this->connection->fetchAssociative("SELECT * FROM tl_member WHERE id=? LIMIT 1", [$rowStudent['memberId']]);
        if (!$rowMember) {
            return;
        }

        $update = [];
        $fields = ['firstname', 'lastname', 'gender', 'language', 'dateOfBirth', 'street', 'postal', 'city', 'state', 'country', 'email', 'phone', 'mobile'];

        foreach ($fields as $field) {
            if ($rowStudent[$field] != $rowMember[$field]) {
                $update[$field] = $rowMember[$field];
            }
        }

        if (!empty($update)) {
            $this->connection->update('tl_dc_students', $update, ['id' => $dc->id]);
            foreach ($update as $key => $value) {
                if (!isset($_POST[$key])) {
                    $_POST[$key] = $value;
                }
            }
        }
    }

    #[AsCallback(table: 'tl_dc_students', target: 'config.onsubmit')]
    public function onStudentSubmit(DataContainer $dc): void
    {
        if (Environment::get('isAjaxRequest') || !$dc->activeRecord) {
            return;
        }

        $student = $dc->activeRecord;
        if (!$student->allowLogin) {
            return;
        }

        $set = [
            'tstamp' => time(),
            'firstname' => $student->firstname,
            'lastname' => $student->lastname,
            'gender' => $student->gender,
            'language' => $student->language,
            'dateOfBirth' => $student->dateOfBirth,
            'street' => $student->street,
            'postal' => $student->postal,
            'city' => $student->city,
            'state' => $student->state,
            'country' => $student->country,
            'email' => $student->email,
            'phone' => $student->phone,
            'mobile' => $student->mobile,
            'username' => $student->username,
            'groups' => $student->memberGroups,
            'login' => '1',
            'disable' => '0'
        ];

        $memberId = (int)$student->memberId;
        $exists = false;

        if ($memberId > 0) {
            $exists = (bool)$this->connection->fetchOne("SELECT id FROM tl_member WHERE id=?", [$memberId]);
        }

        if (!$exists && $student->username) {
            $id = $this->connection->fetchOne("SELECT id FROM tl_member WHERE username=?", [$student->username]);
            if ($id) {
                $exists = true;
                $memberId = (int)$id;
            }
        }

        if (!$exists && $student->email) {
            $id = $this->connection->fetchOne("SELECT id FROM tl_member WHERE email=?", [$student->email]);
            if ($id) {
                $exists = true;
                $memberId = (int)$id;
            }
        }

        if ($exists) {
            $this->connection->update('tl_member', $set, ['id' => $memberId]);
            if ((int)$student->memberId !== $memberId) {
                $this->connection->update('tl_dc_students', ['memberId' => $memberId], ['id' => $student->id]);
            }
        } else {
            $password = bin2hex(random_bytes(6));
            $set['dateAdded'] = time();
            $userContext = new class implements PasswordAuthenticatedUserInterface {
                public function getPassword(): ?string
                {
                    return null;
                }
            };
            $set['password'] = $this->passwordHasher->hashPassword($userContext, $password);

            $this->connection->insert('tl_member', $set);
            $newMemberId = (int)$this->connection->lastInsertId();

            $this->connection->update('tl_dc_students', ['memberId' => $newMemberId], ['id' => $student->id]);
            $_SESSION['NEW_STUDENT_PASSWORD'][$student->id] = $password;

            Message::addRaw('<div class="tl_info" style="border: 2px solid #86af35; padding: 20px; font-size: 1.2em;">
                <strong>WICHTIG: Neues Mitglied angelegt!</strong><br>
                Das vorläufige Passwort lautet: <code style="background:#eee; padding:2px 5px; border:1px solid #ccc;">' . $password . '</code>
            </div>');
        }
    }

    /* tl_dc_course_students */

    #[AsCallback(table: 'tl_dc_course_students', target: 'list.label.label')]
    public function onCourseStudentLabel(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        $courseTitle = $this->connection->fetchOne("SELECT title FROM tl_dc_dive_course WHERE id = ?", [$row['course_id']]) ?: '-';
        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'][$row['status']] ?? (string)$row['status'];
        $dateLabel = (!empty($row['registered_on']) && is_numeric($row['registered_on'])) ? Date::parse(Config::get('dateFormat'), (int)$row['registered_on']) : '-';
        //$birthDateLabel = (!empty($row['birthDate']) && is_numeric($row['birthDate'])) ? Date::parse(Config::get('dateFormat'), (int)$row['birthDate']) : '-';
        $payedLabel = !empty($row['payed']) ? 'ja' : 'nein';

        if (is_array($args)) {
            $args[0] = $courseTitle;
            $args[1] = $statusLabel;
            //$args[2] = $birthDateLabel;
            $args[2] = $payedLabel;
            $args[3] = $dateLabel;
            return $args;
        }

        return sprintf('%s — Status: %s (Angemeldet am: %s), Bezahlt: %s', $courseTitle, $statusLabel, $dateLabel, $payedLabel);
    }

    #[AsCallback(table: 'tl_dc_course_students', target: 'config.onsubmit')]
    public function onCourseStudentSubmit(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $assignmentId = (int)($dc->id ?: $dc->activeRecord->id);
        $courseTemplateId = (int)$dc->activeRecord->course_id;

        if ((int)$dc->activeRecord->event_id > 0) {
            $eventCourseId = (int)$this->connection->fetchOne("SELECT course_id FROM tl_dc_course_event WHERE id=?", [(int)$dc->activeRecord->event_id]);
            if ($eventCourseId > 0) {
                $courseTemplateId = $eventCourseId;
                if (!(int)$dc->activeRecord->course_id) {
                    $this->connection->update('tl_dc_course_students', ['course_id' => $courseTemplateId], ['id' => $assignmentId]);
                }
            }
        }

        if ($courseTemplateId <= 0) {
            return;
        }

        $moduleExercises = [];
        $allModules = [];

        if ((int)$dc->activeRecord->event_id > 0) {
            $scheduleRows = $this->connection->fetchAllAssociative(
                "SELECT s.id, s.module_id, s.planned_at, s.instructor FROM tl_dc_course_event_schedule s WHERE s.pid = ? ORDER BY s.planned_at ASC, s.sorting ASC",
                [(int)$dc->activeRecord->event_id]
            );

            foreach ($scheduleRows as $objSchedule) {
                $moduleId = (int)$objSchedule['module_id'];
                if ($moduleId <= 0) continue;
                if (!isset($allModules[$moduleId])) {
                    $allModules[$moduleId] = ['planned_at' => $objSchedule['planned_at'], 'instructor' => $objSchedule['instructor']];
                }

                $scheduleExRows = $this->connection->fetchAllAssociative("SELECT exercise_id, title, planned_at, instructor FROM tl_dc_event_schedule_exercises WHERE pid=? ORDER BY sorting", [(int)$objSchedule['id']]);
                if (!empty($scheduleExRows)) {
                    foreach ($scheduleExRows as $objScheduleEx) {
                        $exId = (int)$objScheduleEx['exercise_id'];
                        $key = $moduleId . '_' . $exId;
                        if (!isset($moduleExercises[$key])) {
                            $moduleExercises[$key] = ['id' => $exId, 'module_id' => $moduleId, 'planned_at' => $objScheduleEx['planned_at'] ?: $objSchedule['planned_at'], 'instructor' => $objScheduleEx['instructor'] ?: $objSchedule['instructor']];
                        }
                    }
                } else {
                    $modExIds = $this->connection->fetchFirstColumn("SELECT id FROM tl_dc_course_exercises WHERE pid = ? ORDER BY sorting", [$moduleId]);
                    foreach ($modExIds as $modExId) {
                        $exId = (int)$modExId;
                        $key = $moduleId . '_' . $exId;
                        if (!isset($moduleExercises[$key])) {
                            $moduleExercises[$key] = ['id' => $exId, 'module_id' => $moduleId, 'planned_at' => $objSchedule['planned_at'], 'instructor' => $objSchedule['instructor']];
                        }
                    }
                }
            }
        }

        if (empty($allModules)) {
            $modules = $this->connection->fetchAllAssociative("SELECT id FROM tl_dc_course_modules WHERE pid = ? ORDER BY sorting", [$courseTemplateId]);
            foreach ($modules as $mod) {
                $moduleId = (int)$mod['id'];
                $allModules[$moduleId] = ['planned_at' => '', 'instructor' => ''];
                $modExRows = $this->connection->fetchAllAssociative("SELECT id FROM tl_dc_course_exercises WHERE pid = ? ORDER BY sorting", [$moduleId]);
                foreach ($modExRows as $objModEx) {
                    $exId = (int)$objModEx['id'];
                    $key = $moduleId . '_' . $exId;
                    $moduleExercises[$key] = ['id' => $exId, 'module_id' => $moduleId, 'planned_at' => '', 'instructor' => ''];
                }
            }
        }

        $sorting = 128;
        foreach ($allModules as $moduleId => $modData) {
            $exercisesForThisModule = [];
            foreach ($moduleExercises as $exData) {
                if ($exData['module_id'] === $moduleId) {
                    $exercisesForThisModule[] = $exData;
                }
            }

            if (empty($exercisesForThisModule)) {
                $this->upsertStudentExercise($assignmentId, 0, $moduleId, $modData['planned_at'], $modData['instructor'], $sorting);
                $sorting += 128;
            } else {
                foreach ($exercisesForThisModule as $exData) {
                    $this->upsertStudentExercise($assignmentId, $exData['id'], $moduleId, $exData['planned_at'], $exData['instructor'], $sorting);
                    $sorting += 128;
                }
            }
        }
    }

    private function upsertStudentExercise(int $assignmentId, int $exerciseId, int $moduleId, $plannedAt, $instructor, int $sorting): void
    {
        $checkId = $this->connection->fetchOne("SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=? AND module_id=?", [$assignmentId, $exerciseId, $moduleId]);
        if (!$checkId) {
            $this->connection->insert('tl_dc_student_exercises', [
                'pid' => $assignmentId, 'tstamp' => time(), 'sorting' => $sorting, 'exercise_id' => $exerciseId, 'module_id' => $moduleId, 'status' => 'pending', 'dateCompleted' => (int)$plannedAt, 'instructor' => $instructor, 'published' => 1
            ]);
        } else {
            $this->connection->executeStatement("UPDATE tl_dc_student_exercises SET dateCompleted=?, instructor=? WHERE id=? AND (dateCompleted=0 OR dateCompleted IS NULL)", [(int)$plannedAt, $instructor, (int)$checkId]);
        }
    }

    /* tl_dc_student_exercises */

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'list.label.label')]
    public function onStudentExerciseLabel(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if ($row['exercise_id'] > 0) {
            $objInfo = $this->connection->fetchAssociative("
                SELECT e.title AS exTitle, m.title AS modTitle FROM tl_dc_course_exercises e JOIN tl_dc_course_modules m ON m.id = ? WHERE e.id = ?",
                [(int)$row['module_id'], (int)$row['exercise_id']]);
            $title = $objInfo['exTitle'] ?? '';
        } else {
            $objInfo = $this->connection->fetchAssociative("SELECT title AS modTitle FROM tl_dc_course_modules WHERE id = ?", [(int)$row['module_id']]);
            $title = 'Modul-Abschluss';
        }

        if (!$objInfo) return $label;

        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][$row['status']] ?? $row['status'];
        $color = ($row['status'] === 'ok') ? '#2fb31b' : (($row['status'] === 'pending') ? '#ff8000' : '#ff0000');

        $out = sprintf(
            '<span style="color:#999; width:150px; display:inline-block;">[%s]</span> <span style="width:250px; display:inline-block;"><strong>%s</strong></span> — <span style="color:%s; font-weight:bold;">%s</span>',
            $objInfo['modTitle'], $title, $color, $statusLabel
        );

        if (is_array($args)) {
            $args[0] = $out;
            $args[1] = sprintf('<span style="color:%s; font-weight:bold;">%s</span>', $color, $statusLabel);
            return $args;
        }

        return $out;
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'config.onload')]
    public function onStudentExerciseLoad(DataContainer $dc): void
    {
        if (Input::get('key') === 'completeExercise' && Input::get('rid')) {
            $this->completeExercise((int)Input::get('rid'));
        }
    }

    public function completeExercise(int $id): void
    {
        $tokenManager = System::getContainer()->get('contao.csrf.token_manager');
        $tokenId = (string)System::getContainer()->getParameter('contao.csrf_token_name');
        $rt = (string)Input::get($tokenId) ?: (string)Input::get('rt');

        if ($rt === '' || !$tokenManager->isTokenValid(new CsrfToken($tokenId, $rt))) {
            throw new AccessDeniedException('Invalid request token.');
        }

        $exercise = $this->connection->fetchAssociative('SELECT pid,status FROM tl_dc_student_exercises WHERE id=?', [$id]);

        if (!$exercise) {
            Backend::redirect('contao');
        }

        $currentStatus = (string)$exercise['status'];
        $time = time();

        if ($currentStatus === 'ok') {
            $this->connection->executeStatement("UPDATE tl_dc_student_exercises SET status='pending', dateCompleted=0, tstamp=? WHERE id=?", [$time, $id]);
        } else {
            $this->connection->executeStatement("UPDATE tl_dc_student_exercises SET status='ok', dateCompleted=?, tstamp=? WHERE id=?", [$time, $time, $id]);
        }

        if (null === $this->requestStack->getCurrentRequest()) {
            Backend::redirect('contao');
        }

        Backend::redirect(Backend::addToUrl('', true, ['key', 'rid', 'rt', $tokenId]));
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'list.operations.complete.button')]
    public function showCompleteButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        $tokenManager = System::getContainer()->get('contao.csrf.token_manager');
        $tokenId = (string)System::getContainer()->getParameter('contao.csrf_token_name');
        $url = Backend::addToUrl('id=' . (int)$row['pid'] . '&key=completeExercise&rid=' . (int)$row['id'] . '&' . $tokenId . '=' . $tokenManager->getDefaultTokenValue(), true, ['id', 'rid', $tokenId]);
        $isCompleted = ($row['status'] ?? '') === 'ok';
        $buttonLabel = $isCompleted ? 'Übung zurücksetzen' : 'Übung abschließen';
        $buttonTitle = $isCompleted ? 'Status auf Wartend zurücksetzen und Abschlussdatum entfernen' : 'Status auf OK setzen und Datum eintragen';
        $buttonIcon = $isCompleted ? 'undo.svg' : (string)$icon;

        return sprintf('<a href="%s" title="%s" %s>%s</a> ', $url, StringUtil::specialchars($buttonTitle), $attributes, Image::getHtml($buttonIcon, $buttonLabel));
    }
}
