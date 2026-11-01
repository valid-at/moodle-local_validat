# valid.at certificates for Moodle (`local_validat`)

When a learner completes a Moodle course, this plugin issues a tamper-proof certificate on
[valid.at](https://valid.at). Anyone can verify it by its code or QR code on valid.at. The learner receives the
PDF by email and finds all certificates in the Moodle profile under "My certificates on valid.at".

Deutsche Anleitung: [README.de.md](README.de.md)

## Requirements

- Moodle 4.1 to 5.3, with the PHP version and database that the Moodle version requires.
- **An account on valid.at with the Pro or Enterprise plan.** valid.at is a paid service of dawox GmbH, Austria. The
  issuer is verified against the company register before it can issue certificates.
- An API key. Create it on valid.at under Account, API (Konto, Schnittstelle). It starts with `vat_`.
- Cron must run. Certificates are issued by an ad hoc task, usually within a minute after the completion.

## Installation and setup

1. Install the plugin as `local/validat`:
   - Download the ZIP from <https://valid.at/moodle> and upload it under Site administration, Plugins, Install
     plugins. Then confirm the upgrade.
   - Or unpack it to `local/validat`. Since Moodle 5.1, plugins live in the folder `public`, so the path is
     `public/local/validat`.
2. Under Site administration, Plugins, Local plugins, valid.at certificates:
   - Enter the API key.
   - Use "Test the connection" to check issuer, plan and quota.
3. Set up course completion in the course.
4. In the course, open More, Certificates on valid.at:
   - Switch certificates on.
   - Enter the kind of certificate, for example "First aider".
   - Choose the validity.
   - Optionally add a text for the PDF.

## How it works

- **Issuing**
  - The plugin records the event "Course completed".
  - An ad hoc task sends the completion to the valid.at API with:
    - the full name,
    - the email address (can be switched off per course),
    - the kind of certificate,
    - the date of completion,
    - the validity,
    - a reference made of a prefix and the course short name.
- **No duplicates:** Every completion carries a unique `Idempotency-Key`. A lost answer never leads to a second
  certificate.
- **Repeated completion:** When course completion is reset and the learner completes again, a new certificate is
  issued. This suits annual trainings.
- **Errors**
  - If valid.at is unreachable or busy, Moodle retries the task later.
  - If valid.at refuses, for example because the quota is used up, the reason is shown on the course page. The
    certificate can be requested again there.
- **Course page "Certificates on valid.at"**
  - All certificates with status and a link to the verification page.
  - Revoke with a reason.
  - Issue certificates for completions from before the course issued certificates.
- **Backup and restore**
  - Course backups include the settings. Restoring into a new course, and duplicating a course, keeps them.
  - Backups with users also include the certificates, except anonymised ones. After a restore they appear in the
    course again, so they are not issued a second time. A restore never sends anything to valid.at.
- **Capabilities**
  - Teachers can view the list (`local/validat:view`).
  - Changing settings, revoking and issuing for past completions act in the name of the issuer. By default only
    managers may do this (`local/validat:manage`).

## Privacy

- Sent to valid.at:
  - full name,
  - email address, if switched on for the course,
  - kind of certificate,
  - date of completion,
  - course short name.
- Stored in Moodle per completion: code, verification page and personal link of the learner.
- The plugin implements the Moodle privacy API for export and deletion.
- Certificates on valid.at are revoked, not deleted. Deleting data in Moodle does not change them.

## Development

- PHPUnit tests are in `tests/`.
- [`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci)
  on Moodle 4.1 to 5.3. It checks code style, PHPDoc, upgrade savepoints and the PHPUnit tests.
- API documentation: <https://valid.at/schnittstelle> and OpenAPI 3.1 at <https://valid.at/api/v1/openapi.json>.

## License

GNU GPL v3 or later, see [LICENSE](LICENSE). Copyright 2026 dawox GmbH.
