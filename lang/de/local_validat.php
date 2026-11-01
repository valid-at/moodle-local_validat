<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German strings for local_validat.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'Schlüssel für die Schnittstelle';
$string['apikey_desc'] = 'Den Schlüssel erstellen Sie auf valid.at unter Konto, Schnittstelle. Er beginnt mit vat_ und setzt das Paket Pro oder Enterprise voraus.';
$string['apiurl'] = 'Adresse von valid.at';
$string['apiurl_desc'] = 'Normalerweise https://valid.at. Nur für ein Testsystem ändern.';
$string['backfill'] = 'Für bisherige Abschlüsse ausstellen';
$string['backfill_confirm'] = 'Für {$a} bisherige Abschlüsse Nachweise auf valid.at ausstellen? Jeder zählt zu Ihrem Kontingent.';
$string['backfill_info'] = '{$a} Teilnehmende haben diesen Kurs abgeschlossen, bevor er Nachweise ausgestellt hat.';
$string['backfill_queued'] = '{$a} Nachweise sind vorgemerkt und werden in den nächsten Minuten ausgestellt.';
$string['certificate'] = 'Nachweis';
$string['check'] = 'Verbindung zu valid.at';
$string['check_failed'] = 'Die Verbindung ist fehlgeschlagen: {$a}';
$string['check_issuer'] = 'Aussteller';
$string['check_key'] = 'Bezeichnung des Schlüssels';
$string['check_notverified'] = 'Noch nicht. Nachweise lassen sich ausstellen, sobald valid.at den Aussteller geprüft hat.';
$string['check_ok'] = 'Die Verbindung funktioniert.';
$string['check_plan'] = 'Paket';
$string['check_quota'] = 'Nachweise in diesem Jahr';
$string['check_quota_agreed'] = 'nach Vereinbarung';
$string['check_quota_value'] = '{$a->used} von {$a->limit}';
$string['check_verified'] = 'Identität von valid.at geprüft';
$string['code'] = 'Code';
$string['completed'] = 'Abgeschlossen';
$string['course_intro'] = 'Schließt jemand diesen Kurs ab, stellt valid.at einen Nachweis aus, den alle mit Code oder QR-Code prüfen können. Die Teilnehmenden erhalten das PDF per E-Mail und finden es in ihrem Profil unter Meine Nachweise auf valid.at.';
$string['coursenav'] = 'Nachweise auf valid.at';
$string['enabled'] = 'Nachweise';
$string['enabled_label'] = 'Bei Kursabschluss einen Nachweis auf valid.at ausstellen';
$string['error_answer'] = 'valid.at hat unerwartet geantwortet.';
$string['error_nokey'] = 'Es ist kein Schlüssel für valid.at eingetragen.';
$string['error_notavailable'] = 'Kurs, Einstellungen oder Person gibt es nicht mehr, oder der Kurs stellt keine Nachweise mehr aus.';
$string['error_reason'] = 'Bitte einen Grund mit 3 bis 300 Zeichen angeben.';
$string['error_title'] = 'Bitte die Art des Nachweises angeben.';
$string['error_unreachable'] = 'valid.at war nicht erreichbar.';
$string['error_validity'] = 'Bitte eine Gültigkeit aus der Liste wählen.';
$string['issues'] = 'Nachweise ({$a})';
$string['issues_none'] = 'Noch keine Nachweise.';
$string['learner'] = 'Person';
$string['my_completed'] = 'Abgeschlossen am {$a}';
$string['my_holder'] = 'PDF und Namensanzeige';
$string['my_intro'] = 'Ihre Nachweise sind auf valid.at registriert. Zeigen Sie Code, QR-Code auf dem PDF oder den Link zur Prüfseite allen, die sie prüfen möchten.';
$string['my_none'] = 'Sie haben noch keine Nachweise.';
$string['my_notyet'] = 'Noch nicht ausgestellt';
$string['my_verify'] = 'Prüfseite öffnen';
$string['mycertificates'] = 'Meine Nachweise auf valid.at';
$string['note'] = 'Zusatztext auf dem PDF';
$string['note_help'] = 'Freiwillig, höchstens 600 Zeichen, zum Beispiel Dauer oder Inhalte des Kurses.';
$string['notify'] = 'E-Mail von valid.at';
$string['notify_desc'] = 'Voreinstellung für neue Kurse: valid.at schickt den Teilnehmenden eine E-Mail mit dem Nachweis und dem Link zum PDF.';
$string['notify_label'] = 'valid.at schickt den Teilnehmenden eine E-Mail mit dem Nachweis';
$string['pluginname'] = 'Nachweise mit valid.at';
$string['privacy:metadata:issues'] = 'Welcher Nachweis auf valid.at zu welchem Kursabschluss gehört.';
$string['privacy:metadata:issues:code'] = 'Code des Nachweises auf valid.at.';
$string['privacy:metadata:issues:courseid'] = 'Abgeschlossener Kurs.';
$string['privacy:metadata:issues:holderurl'] = 'Persönliche Seite der Person auf valid.at mit dem PDF.';
$string['privacy:metadata:issues:revokereason'] = 'Grund für den Widerruf.';
$string['privacy:metadata:issues:status'] = 'Ob der Nachweis wartet, ausgestellt, abgelehnt oder widerrufen ist.';
$string['privacy:metadata:issues:timecompleted'] = 'Zeitpunkt des Kursabschlusses.';
$string['privacy:metadata:issues:userid'] = 'Person, die den Kurs abgeschlossen hat.';
$string['privacy:metadata:issues:validuntil'] = 'Ablaufdatum des Nachweises.';
$string['privacy:metadata:issues:verifyurl'] = 'Öffentliche Prüfseite des Nachweises.';
$string['privacy:metadata:validat'] = 'Für jeden Kursabschluss wird auf valid.at, einem Dienst der dawox GmbH in Österreich, ein Nachweis ausgestellt. valid.at zeigt ihn allen, die den Code kennen, auf einer Prüfseite. Nachweise werden dort widerrufen, nicht gelöscht; das Löschen von Daten in Moodle ändert sie nicht.';
$string['privacy:metadata:validat:course'] = 'Kurzname des Kurses als interne Nummer.';
$string['privacy:metadata:validat:email'] = 'E-Mail-Adresse der Person, falls der Kurs sie mitschickt, für den Nachweis per E-Mail und Erinnerungen vor Ablauf.';
$string['privacy:metadata:validat:fullname'] = 'Vollständiger Name der Person.';
$string['privacy:metadata:validat:timecompleted'] = 'Datum des Kursabschlusses als Ausstellungsdatum.';
$string['privacy:metadata:validat:title'] = 'Art des Nachweises, wie im Kurs eingestellt.';
$string['referenceprefix'] = 'Vorsatz für die interne Nummer';
$string['referenceprefix_desc'] = 'Steht zusammen mit dem Kurznamen des Kurses klein auf dem PDF, zum Beispiel „Moodle ERSTEHILFE-26“.';
$string['retry'] = 'Erneut versuchen';
$string['retry_notpossible'] = 'Dieser Nachweis lässt sich nicht erneut anfordern.';
$string['retry_queued'] = 'Der Nachweis wird in den nächsten Minuten erneut angefordert.';
$string['revoke'] = 'Widerrufen';
$string['revoke_failed'] = 'valid.at hat den Nachweis nicht widerrufen: {$a}';
$string['revoke_intro'] = 'Der Nachweis bleibt auf valid.at registriert und zeigt ab sofort „Widerrufen“ mit Datum und Grund. Das lässt sich nicht rückgängig machen.';
$string['revoked'] = 'Der Nachweis ist widerrufen.';
$string['revokereason'] = 'Grund';
$string['revokereason_help'] = 'Erscheint auf der Prüfseite, zum Beispiel „Irrtümlich ausgestellt“ oder „Durch neuen Nachweis ersetzt“.';
$string['revoketitle'] = 'Nachweis widerrufen';
$string['sendemail'] = 'E-Mail-Adresse';
$string['sendemail_desc'] = 'Voreinstellung für neue Kurse: Die E-Mail-Adresse der Teilnehmenden geht an valid.at, für den Nachweis per E-Mail und Erinnerungen vor Ablauf.';
$string['sendemail_label'] = 'E-Mail-Adresse der Teilnehmenden an valid.at übermitteln';
$string['settings'] = 'Einstellungen';
$string['settings_intro'] = 'Stellt für jeden Abschluss eines Kurses, in dem es eingeschaltet ist, einen Nachweis auf valid.at aus (im Kurs unter Nachweise auf valid.at). <a href="{$a}">Verbindung testen</a>, nachdem Sie den Schlüssel gespeichert haben.';
$string['status'] = 'Stand';
$string['status_failed'] = 'Abgelehnt';
$string['status_issued'] = 'Ausgestellt';
$string['status_pending'] = 'Wartet';
$string['status_revoked'] = 'Widerrufen';
$string['summary_off'] = 'Dieser Kurs stellt keine Nachweise auf valid.at aus.';
$string['summary_on'] = 'Dieser Kurs stellt den Nachweis „{$a}“ auf valid.at aus.';
$string['task_issue'] = 'Nachweis auf valid.at ausstellen';
$string['title'] = 'Art des Nachweises';
$string['title_help'] = 'Steht auf dem Nachweis und der Prüfseite, zum Beispiel „Ersthelfer im Betrieb“ oder „Elektrotechnisch unterwiesene Person (EuP)“. Höchstens 200 Zeichen.';
$string['validat:manage'] = 'Nachweise auf valid.at einstellen, widerrufen und nachträglich ausstellen';
$string['validat:view'] = 'Nachweise auf valid.at in einem Kurs sehen';
$string['validity'] = 'Gültigkeit';
$string['validity_help'] = 'Gerechnet ab dem Tag des Abschlusses. Danach zeigt valid.at den Nachweis als abgelaufen und erinnert im Paket Pro vorher daran.';
$string['validity_months'] = '{$a} Monate';
$string['validity_none'] = 'Unbefristet';
$string['validuntil'] = 'Gültig bis {$a}';
$string['warning_completion'] = 'Der Kursabschluss ist nicht eingerichtet. Nachweise entstehen erst, wenn jemand den Kurs abschließt.';
$string['warning_completion_link'] = 'Kursabschluss einrichten';
$string['warning_nokey'] = 'Es ist noch kein Schlüssel für valid.at eingetragen.';
