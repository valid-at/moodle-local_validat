# Nachweise mit valid.at für Moodle (`local_validat`)

Schließt jemand einen Moodle-Kurs ab, stellt valid.at automatisch einen fälschungssicheren Nachweis aus. Er lässt sich
mit Code oder QR-Code unter valid.at prüfen. Die Teilnehmenden erhalten das PDF per E-Mail und finden ihre Nachweise in
Moodle im Profil unter „Meine Nachweise auf valid.at“.

## Voraussetzungen

- Moodle 4.1 bis 5.3, mit der PHP-Version und Datenbank, die die jeweilige Moodle-Version verlangt.
- Ein geprüftes Konto auf valid.at im Paket Pro oder Enterprise mit einem Schlüssel für die Schnittstelle. Den
  Schlüssel erstellen Sie auf valid.at unter Konto, Schnittstelle.
- Der Cron von Moodle läuft. Nachweise werden über eine Aufgabe im Hintergrund ausgestellt, meist innerhalb einer
  Minute nach dem Abschluss.

## Einrichten

1. Das ZIP unter <https://valid.at/moodle> herunterladen und unter Website-Administration, Plugins, Plugin
   installieren hochladen. Oder den Ordner `validat` nach `local/validat` kopieren; seit Moodle 5.1 liegen Plugins im
   Ordner `public`, also `public/local/validat`. Danach die Aktualisierung bestätigen.
2. Unter Website-Administration, Plugins, Lokale Plugins, Nachweise mit valid.at den Schlüssel eintragen und mit
   „Verbindung testen“ prüfen.
3. Im Kurs den Kursabschluss einrichten, falls noch nicht geschehen.
4. Im Kurs unter „Mehr“, Nachweise auf valid.at:
   - Nachweise einschalten.
   - Die Art des Nachweises angeben, zum Beispiel „Ersthelfer im Betrieb“.
   - Die Gültigkeit wählen.
   - Bei Bedarf einen Zusatztext für das PDF eintragen.

## So funktioniert es

- **Ausstellen:** Beim Ereignis „Kurs abgeschlossen“ merkt das Plugin den Abschluss vor. Eine Hintergrundaufgabe
  sendet ihn an valid.at mit:
  - vollständigem Namen,
  - E-Mail-Adresse, falls eingeschaltet,
  - Art des Nachweises,
  - Datum des Abschlusses,
  - Gültigkeit,
  - interner Nummer aus Vorsatz und Kurzname des Kurses.
- **Keine doppelten Nachweise:** Jeder Abschluss trägt einen eindeutigen `Idempotency-Key`. Auch nach einem
  Verbindungsabbruch entsteht kein zweiter Nachweis.
- **Neuer Abschluss:** Setzt ein Kurs den Abschluss zurück und schließt jemand ihn erneut ab, gibt es einen neuen
  Nachweis, zum Beispiel für jährliche Unterweisungen.
- **Fehler:**
  - Ist valid.at nicht erreichbar oder überlastet, versucht Moodle es später erneut.
  - Lehnt valid.at ab, etwa wegen eines erschöpften Kontingents, steht der Grund auf der Kursseite. Dort lässt sich
    der Nachweis erneut anfordern.
- **Kursseite „Nachweise auf valid.at“:**
  - Liste aller Nachweise mit Stand und Code, der zur Prüfseite verlinkt.
  - Widerrufen mit Grund.
  - Nachweise für Abschlüsse aus der Zeit vor dem Einschalten ausstellen.
- **Sichern und Wiederherstellen:**
  - Kurssicherungen enthalten die Einstellungen. Beim Wiederherstellen in einen neuen Kurs und beim Duplizieren
    bleiben sie erhalten.
  - Sicherungen mit Nutzerdaten enthalten auch die Nachweise, anonymisierte nicht. Nach dem Wiederherstellen stehen
    sie wieder im Kurs und werden daher nicht ein zweites Mal ausgestellt. Das Wiederherstellen sendet nichts an
    valid.at.
- **Rechte:**
  - Lehrende sehen die Liste (`local/validat:view`).
  - Einstellen, Widerrufen und Nachtragen dürfen standardmäßig nur Manager (`local/validat:manage`), weil es im Namen
    des Ausstellers geschieht.

## Datenschutz

- An valid.at gehen:
  - Name,
  - E-Mail-Adresse, falls im Kurs eingeschaltet,
  - Art des Nachweises,
  - Datum des Abschlusses,
  - Kurzname des Kurses.
- Moodle speichert je Abschluss Code, Prüfseite und den persönlichen Link der Person.
- Das Plugin unterstützt die Datenschutz-Schnittstelle von Moodle für Auskunft und Löschung.
- Nachweise auf valid.at werden widerrufen, nicht gelöscht. Das Löschen in Moodle ändert sie nicht.

## Lizenz

GNU GPL v3 oder später, wie Moodle selbst, siehe [LICENSE](LICENSE).
