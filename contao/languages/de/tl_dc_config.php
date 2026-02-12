<?php

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_dc_config']['title_legend'] = "Basis Einstellungen";
$GLOBALS['TL_LANG']['tl_dc_config']['manufacturer_legend'] = "Template mit Herstellern wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['equipment_legend'] = "Template mit Equipment wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['sizes_legend'] = "Template mit Größen wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['types_legend'] = "Template mit Typen wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['course_legend'] = "Template mit Kursen wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['regulator_legend'] = "Template mit Atemreglern wählen";
$GLOBALS['TL_LANG']['tl_dc_config']['template_legend'] = "Templates Konfiguration";
$GLOBALS['TL_LANG']['tl_dc_config']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_dc_config']['reservation_legend'] = 'Reservierung';
$GLOBALS['TL_LANG']['tl_dc_config']['check_legend'] = 'Prüfungen';
$GLOBALS['TL_LANG']['tl_dc_config']['invoice_legend'] = 'Rechnungseinstellungen';
$GLOBALS['TL_LANG']['tl_dc_config']['tuv_legend'] = 'TÜV-Einstellungen';
$GLOBALS['TL_LANG']['tl_dc_config']['conditions_legend'] = 'Bedingungen';
$GLOBALS['TL_LANG']['tl_dc_config']['api_legend'] = 'API-Einstellungen';

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_config']['new'] = ['Neue Konfiguration', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['edit'] = ['Bearbeiten', 'Die Template-Konfiguration mit der ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_dc_config']['copy'] = ['Duplizieren', 'Die Template-Konfiguration mit der ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_dc_config']['delete'] = ['Löschen', 'Die Template-Konfiguration mit der ID %s löschen'];
$GLOBALS['TL_LANG']['tl_dc_config']['show'] = ['Anzeigen', 'Die Details der Template-Konfiguration mit der ID %s anzeigen'];

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_config']['title'] = ['Titel', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['alias'] = ['Alias', 'Der Alias ist eine eindeutige Referenz, die anstelle der numerischen ID aufgerufen werden kann.'];
$GLOBALS['TL_LANG']['tl_dc_config']['templatePath'] = ['Templatepfad', 'Bitte den Speicherort der Templates angeben'];
$GLOBALS['TL_LANG']['tl_dc_config']['manufacturersFile'] = ['Hersteller-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['courseTypesFile'] = ['Kurs-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['courseCategoriesFile'] = ['Kurskategorie-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['invoiceTemplate'] = ['PDF-Rechnungsvorlage', 'Bitte wählen Sie eine PDF-Datei aus, die als Briefpapier für die Rechnung verwendet werden soll.'];
$GLOBALS['TL_LANG']['tl_dc_config']['invoiceText'] = ['Zusatztext Rechnung', 'Dieser Text wird der generierten PDF-Rechnung hinzugefügt. Insert-Tags können verwendet werden.'];
$GLOBALS['TL_LANG']['tl_dc_config']['pdfFolder'] = ['Speicherort PDF-Dateien', 'Bitte wählen Sie den Ordner aus, in dem die generierten PDF-Dateien gespeichert werden sollen. Wird kein Ordner ausgewählt, werden sie unter "files" gespeichert.'];
$GLOBALS['TL_LANG']['tl_dc_config']['tuvListFormat'] = ['TÜV-Listenformat', 'Bitte wählen Sie das Format für die TÜV-Geräteliste aus.'];
$GLOBALS['TL_LANG']['tl_dc_config']['tuvListFolder'] = ['Speicherort TÜV-Liste', 'Bitte wählen Sie den Ordner aus, in dem die TÜV-Liste gespeichert werden soll. Wird kein Ordner ausgewählt, wird sie unter "files" gespeichert.'];
$GLOBALS['TL_LANG']['tl_dc_config']['sizesFile'] = ['Größen-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['typesFile'] = ['Ausrüstungs-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['regulatorsFile'] = ['Atemregler-Template', 'Eine neue Template-Konfiguration anlegen'];
$GLOBALS['TL_LANG']['tl_dc_config']['published'] = ['Veröffentlicht', 'Markieren Sie das Equipment als veröffentlicht.'];
$GLOBALS['TL_LANG']['tl_dc_config']['start'] = ['Startdatum', 'Geben Sie ein Startdatum an.'];
$GLOBALS['TL_LANG']['tl_dc_config']['stop'] = ['Enddatum', 'Geben Sie ein Enddatum an.'];
$GLOBALS['TL_LANG']['tl_dc_config']['reservationMessage'] = ['Mitteilung Reservierung', 'Text, der bei einer Reservierung von Ausrüstung angezeigt wird.'];
$GLOBALS['TL_LANG']['tl_dc_config']['reservationInfo'] = ['Info-Mail-Adresse', 'Geben Sie eine, oder mehrere durch komma getrennte, E-Mail-Adresse an, die über neue Reservierungen informiert werden soll.'];
$GLOBALS['TL_LANG']['tl_dc_config']['reservationInfoText'] = ['Info-Mail-Text', 'GText der MAail, die bei einer Reservierung an die angegebenen Adressen gesendet wird.'];
$GLOBALS['TL_LANG']['tl_dc_config']['rentalConditions'] = ['Leihbedingungen', 'Bedingungen für die Nutzung der ausgelihenen Assets.'];
$GLOBALS['TL_LANG']['tl_dc_config']['addManufacturer'] = ['Hersteller hinzufügen', 'Hersteller im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addEquipment'] = ['Ausrüstung hinzufügen', 'Ausrüstung im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addSizes'] = ['Größen hinzufügen', 'Größen im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addTypes'] = ['Typen hinzufügen', 'Typen im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addCourses'] = ['Kurse hinzufügen', 'Kurse im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addRegulators'] = ['Atemregler hinzufügen', 'Atemregler im Template hinzufügen'];
$GLOBALS['TL_LANG']['tl_dc_config']['activateApi'] = ['API aktivieren', 'Die Nutzung der API für die iOS App aktivieren.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiLogo'] = ['App-Logo', 'Bitte wählen Sie ein Logo aus, das in der App angezeigt werden soll.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiText'] = ['Info-Text App', 'Dieser Text wird auf der Startseite der App angezeigt.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiNewsArchive'] = ['News-Archiv', 'Bitte wählen Sie das News-Archiv aus, dessen Nachrichten in der App angezeigt werden sollen.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiImprint'] = ['Impressum (App)', 'Dieser Text wird in der App im Bereich Impressum angezeigt.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiPrivacy'] = ['Datenschutzhinweise (App)', 'Dieser Text wird in der App im Bereich Datenschutz angezeigt.'];
$GLOBALS['TL_LANG']['tl_dc_config']['apiTerms'] = ['Nutzungsbedingungen (App)', 'Dieser Text wird in der App im Bereich Nutzungsbedingungen angezeigt.'];
$GLOBALS['TL_LANG']['tl_dc_config']['instructor_groups'] = ['Instruktoren-Gruppen', 'Bitte wählen Sie die Mitgliedergruppen aus, die als Instruktoren gelten.'];
$GLOBALS['TL_LANG']['tl_dc_config']['addReservations'] = ['Reservierungen hinzufügen', 'Das Modul Reservierungen im Backend anzeigen'];
$GLOBALS['TL_LANG']['tl_dc_config']['addChecks'] = ['Prüfungen hinzufügen', 'Das Modul Prüfungen im Backend anzeigen'];
$GLOBALS['TL_LANG']['tl_dc_config']['default'] = ['Default-Template', 'Eine neue Template-Konfiguration anlegen'];

/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['noConfigFound'] = 'Es wurde keine Konfiguration gefunden. Bitte erstellen Sie zuerst eine Konfiguration in den Einstellungen.';
