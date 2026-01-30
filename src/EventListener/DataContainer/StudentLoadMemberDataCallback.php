<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Message;

class StudentLoadMemberDataCallback
{
    #[AsCallback(table: 'tl_dc_students', target: 'config.onload')]
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        // Passwort-Anzeige aus Session (falls gerade angelegt)
        if (isset($_SESSION['NEW_STUDENT_PASSWORD'][$dc->id])) {
            $password = $_SESSION['NEW_STUDENT_PASSWORD'][$dc->id];
            unset($_SESSION['NEW_STUDENT_PASSWORD'][$dc->id]);

            Message::addRaw('<div class="tl_info" style="border: 2px solid #86af35; padding: 20px; font-size: 1.2em;">
                <strong>WICHTIG: Neues Mitglied angelegt!</strong><br>
                Das vorläufige Passwort lautet: <code style="background:#eee; padding:2px 5px; border:1px solid #ccc;">' . $password . '</code>
            </div>');
        }

        $db = Database::getInstance();
        $objStudent = $db->prepare("SELECT * FROM tl_dc_students WHERE id=?")
            ->limit(1)
            ->execute($dc->id);

        if ($objStudent->numRows < 1) {
            return;
        }

        // Wenn ein Mitglied ausgewählt ist, darf allowLogin nicht aktivierbar sein
        if ($objStudent->memberId > 0) {
            $GLOBALS['TL_DCA']['tl_dc_students']['fields']['allowLogin']['eval']['disabled'] = true;

            // Falls allowLogin gesetzt war, deaktivieren wir es, da die Anmeldung über das Mitgliedskonto erfolgt
            if ($objStudent->allowLogin) {
                $db->prepare("UPDATE tl_dc_students SET allowLogin='0' WHERE id=?")
                    ->execute($dc->id);
                $objStudent->allowLogin = '0';
            }
        }

        // Wenn allowLogin aktiviert ist, darf kein bestehendes Mitglied gewählt werden
        if ($objStudent->allowLogin) {
            $GLOBALS['TL_DCA']['tl_dc_students']['fields']['memberId']['eval']['disabled'] = true;
        }

        if (!$objStudent->memberId) {
            return;
        }

        // Falls das Formular abgeschickt wurde, nichts überschreiben (außer bei memberId Änderung via submitOnChange)
        // Aber in Contao ist onload_callback vor dem Laden der Daten.
        // Wenn memberId gerade geändert wurde, wollen wir die Daten des neuen Members laden.

        $objMember = $db->prepare("SELECT * FROM tl_member WHERE id=?")
            ->limit(1)
            ->execute($objStudent->memberId);

        if ($objMember->numRows < 1) {
            return;
        }

        // Wir prüfen, ob die Felder leer sind oder ob wir sie forcieren wollen.
        // Die Anforderung sagt "einfach zu einem Tauchschüler machen".
        // Wenn memberId gesetzt ist, sollten die Daten synchron sein.

        $update = [];
        $fields = [
            'firstname', 'lastname', 'gender', 'language', 'dateOfBirth',
            'street', 'postal', 'city', 'state', 'country', 'email', 'phone', 'mobile'
        ];

        foreach ($fields as $field) {
            if ($objStudent->$field != $objMember->$field) {
                $update[$field] = $objMember->$field;
            }
        }

        if (!empty($update)) {
            $db->prepare("UPDATE tl_dc_students SET " . implode('=?, ', array_keys($update)) . "=? WHERE id=?")
                ->execute(...array_merge(array_values($update), [$dc->id]));

            // Wir müssen die Werte auch im aktuellen Request-Objekt (POST) anpassen,
            // damit sie im Formular sofort korrekt angezeigt werden, falls sie gerade erst geladen wurden.
            foreach ($update as $key => $value) {
                if (!isset($_POST[$key])) {
                    $_POST[$key] = $value;
                }
            }
        }
    }
}
