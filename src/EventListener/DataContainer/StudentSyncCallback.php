<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Message;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Synchronisiert Daten zwischen tl_dc_students und tl_member.
 */
class StudentSyncCallback
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    )
    {
    }

    /**
     * Wird beim Absenden des DCA-Formulars tl_dc_students aufgerufen.
     */
    #[AsCallback(table: 'tl_dc_students', target: 'config.onsubmit')]
    public function __invoke(DataContainer $dc): void
    {
        if (Environment::get('isAjaxRequest')) {
            return;
        }

        if (!$dc->activeRecord) {
            return;
        }

        $db = Database::getInstance();
        $student = $dc->activeRecord;

        // 1. Wenn kein Login erlaubt ist, Member deaktivieren
        // Ausnahme: Wenn es ein bestehendes Mitglied ist, deaktivieren wir es nicht einfach,
        // sondern entziehen nur die Login-Berechtigung falls sie über den Schüler gesteuert wurde.
        if (!$student->allowLogin) {
            if ($student->memberId > 0) {
                // Nur wenn wir sicher sind, dass wir das Mitglied steuern dürfen.
                // In diesem Fall belassen wir es dabei, dass die Verknüpfung bestehen bleibt.
            }
            return;
        }

        // 2. Daten-Array vorbereiten
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

        // 3. Prüfen, ob das Mitglied existiert (entweder über ID oder über Username)
        $memberId = (int)$student->memberId;
        $exists = false;

        if ($memberId > 0) {
            $objCheck = $db->prepare("SELECT id FROM tl_member WHERE id=?")->execute($memberId);
            if ($objCheck->numRows > 0) {
                $exists = true;
            }
        }

        if (!$exists && $student->username) {
            $objCheck = $db->prepare("SELECT id FROM tl_member WHERE username=?")->execute($student->username);
            if ($objCheck->numRows > 0) {
                $exists = true;
                $memberId = (int)$objCheck->id;
            }
        }

        // 3.1 Falls ein neues Mitglied angelegt werden soll, aber die Email schon existiert
        if (!$exists && $student->email) {
            $objCheck = $db->prepare("SELECT id FROM tl_member WHERE email=?")->execute($student->email);
            if ($objCheck->numRows > 0) {
                $exists = true;
                $memberId = (int)$objCheck->id;
            }
        }

        // 4. Update oder Insert
        if ($exists) {
            // Mitglied aktualisieren
            $params = array_values($set);
            $params[] = $memberId;

            $db->prepare("UPDATE tl_member SET " . implode('=?, ', array_keys($set)) . "=? WHERE id=?")
                ->execute(...$params);

            // Falls die ID im Schüler-Datensatz noch nicht oder falsch war, jetzt korrigieren
            if ((int)$student->memberId !== $memberId) {
                // Wir nutzen Database::getInstance()->prepare() direkt, um sicherzugehen, dass keine Records verloren gehen.
                // Ein einfaches Update auf tl_dc_students sollte tl_dc_course_students (als ctable) nicht beeinflussen,
                // solange kein DC_Table act=delete oder ähnliches getriggert wird.
                $db->prepare("UPDATE tl_dc_students SET memberId=? WHERE id=?")
                    ->execute($memberId, $student->id);
            }
        } else {
            // Mitglied neu anlegen
            $password = bin2hex(random_bytes(6));
            $set['dateAdded'] = time();

            $userContext = new class implements PasswordAuthenticatedUserInterface {
                public function getPassword(): ?string
                {
                    return null;
                }
            };
            $set['password'] = $this->passwordHasher->hashPassword($userContext, $password);

            $db->prepare("INSERT INTO tl_member (" . implode(', ', array_keys($set)) . ") VALUES (" . implode(', ', array_fill(0, count($set), '?')) . ")")
                ->execute(...array_values($set));

            $newMemberId = (int)$db->insertId;

            // ID im Schüler-Datensatz speichern
            if ((int)$student->memberId !== $newMemberId) {
                $db->prepare("UPDATE tl_dc_students SET memberId=? WHERE id=?")
                    ->execute($newMemberId, $student->id);
            }

            $_SESSION['NEW_STUDENT_PASSWORD'][$student->id] = $password;

            Message::addRaw('<div class="tl_info" style="border: 2px solid #86af35; padding: 20px; font-size: 1.2em;">
                <strong>WICHTIG: Neues Mitglied angelegt!</strong><br>
                Das vorläufige Passwort lautet: <code style="background:#eee; padding:2px 5px; border:1px solid #ccc;">' . $password . '</code>
            </div>');

        }
    }
}
