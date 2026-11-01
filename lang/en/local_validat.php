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
 * English strings for local_validat.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Create the key on valid.at under Account, API (Konto, Schnittstelle). It starts with vat_ and needs the Pro or Enterprise plan.';
$string['apiurl'] = 'Address of valid.at';
$string['apiurl_desc'] = 'Normally https://valid.at. Change it only for a test system.';
$string['backfill'] = 'Issue for past completions';
$string['backfill_confirm'] = 'Issue certificates on valid.at for {$a} past completions? Each one counts towards your quota.';
$string['backfill_info'] = '{$a} learners completed this course before it issued certificates.';
$string['backfill_queued'] = '{$a} certificates are queued and will be issued within the next minutes.';
$string['certificate'] = 'Certificate';
$string['check'] = 'Connection to valid.at';
$string['check_failed'] = 'The connection failed: {$a}';
$string['check_issuer'] = 'Issuer';
$string['check_key'] = 'Name of the key';
$string['check_notverified'] = 'Not yet. Certificates can be issued once valid.at has verified the issuer.';
$string['check_ok'] = 'The connection works.';
$string['check_plan'] = 'Plan';
$string['check_quota'] = 'Certificates this year';
$string['check_quota_agreed'] = 'as agreed';
$string['check_quota_value'] = '{$a->used} of {$a->limit}';
$string['check_verified'] = 'Identity verified by valid.at';
$string['code'] = 'Code';
$string['completed'] = 'Completed';
$string['course_intro'] = 'When a learner completes this course, valid.at issues a certificate that anyone can verify with its code or QR code. The learner receives the PDF by email and finds it under My certificates on valid.at in the profile.';
$string['coursenav'] = 'Certificates on valid.at';
$string['enabled'] = 'Certificates';
$string['enabled_label'] = 'Issue a certificate on valid.at when a learner completes the course';
$string['error_answer'] = 'valid.at sent an unexpected answer.';
$string['error_nokey'] = 'No API key for valid.at is set.';
$string['error_notavailable'] = 'The course, its settings or the learner no longer exist, or certificates are switched off for the course.';
$string['error_reason'] = 'Please give a reason with 3 to 300 characters.';
$string['error_title'] = 'Please name the kind of certificate.';
$string['error_unreachable'] = 'valid.at could not be reached.';
$string['error_validity'] = 'Please choose a validity from the list.';
$string['issues'] = 'Certificates ({$a})';
$string['issues_none'] = 'No certificates yet.';
$string['learner'] = 'Learner';
$string['my_completed'] = 'Completed on {$a}';
$string['my_holder'] = 'PDF and name display';
$string['my_intro'] = 'Your certificates are registered on valid.at. Show the code, the QR code on the PDF or the link to the verification page to anyone who wants to check them.';
$string['my_none'] = 'You have no certificates yet.';
$string['my_notyet'] = 'Not issued yet';
$string['my_verify'] = 'Open verification page';
$string['mycertificates'] = 'My certificates on valid.at';
$string['note'] = 'Additional text on the PDF';
$string['note_help'] = 'Optional, at most 600 characters, for example the duration or the contents of the course.';
$string['notify'] = 'Email from valid.at';
$string['notify_desc'] = 'Default for new courses: valid.at sends the learner an email with the certificate and the link to the PDF.';
$string['notify_label'] = 'valid.at sends the learner an email with the certificate';
$string['pluginname'] = 'valid.at certificates';
$string['privacy:metadata:issues'] = 'Which certificate on valid.at belongs to which course completion.';
$string['privacy:metadata:issues:code'] = 'Code of the certificate on valid.at.';
$string['privacy:metadata:issues:courseid'] = 'Completed course.';
$string['privacy:metadata:issues:holderurl'] = 'Personal page of the learner on valid.at with the PDF.';
$string['privacy:metadata:issues:revokereason'] = 'Reason for revoking the certificate.';
$string['privacy:metadata:issues:status'] = 'Whether the certificate is waiting, issued, refused or revoked.';
$string['privacy:metadata:issues:timecompleted'] = 'Time of the course completion.';
$string['privacy:metadata:issues:userid'] = 'Learner.';
$string['privacy:metadata:issues:validuntil'] = 'Expiry date of the certificate.';
$string['privacy:metadata:issues:verifyurl'] = 'Public verification page of the certificate.';
$string['privacy:metadata:validat'] = 'For each course completion, a certificate is issued on valid.at, a service of dawox GmbH, Austria. valid.at shows it on a verification page to anyone who has the code. Certificates are revoked there, not deleted; deleting data in Moodle does not change them.';
$string['privacy:metadata:validat:course'] = 'Short name of the course as internal reference.';
$string['privacy:metadata:validat:email'] = 'Email address of the learner, if the course sends it, for the certificate and reminders before expiry.';
$string['privacy:metadata:validat:fullname'] = 'Full name of the learner.';
$string['privacy:metadata:validat:timecompleted'] = 'Date of the course completion as date of issue.';
$string['privacy:metadata:validat:title'] = 'Kind of certificate set for the course.';
$string['referenceprefix'] = 'Prefix for the reference';
$string['referenceprefix_desc'] = 'Together with the short name of the course, it is printed small on the PDF, for example "Moodle FIRSTAID-26".';
$string['retry'] = 'Try again';
$string['retry_notpossible'] = 'This certificate cannot be requested again.';
$string['retry_queued'] = 'The certificate is requested again within the next minutes.';
$string['revoke'] = 'Revoke';
$string['revoke_failed'] = 'valid.at did not revoke the certificate: {$a}';
$string['revoke_intro'] = 'The certificate stays registered on valid.at and shows "Revoked" with date and reason from now on. This cannot be undone.';
$string['revoked'] = 'The certificate is revoked.';
$string['revokereason'] = 'Reason';
$string['revokereason_help'] = 'Appears on the verification page, for example "Issued in error" or "Replaced by a new certificate".';
$string['revoketitle'] = 'Revoke certificate';
$string['sendemail'] = 'Email address';
$string['sendemail_desc'] = 'Default for new courses: the email address of the learner is sent to valid.at, for the certificate by email and reminders before expiry.';
$string['sendemail_label'] = 'Send the email address of the learner to valid.at';
$string['settings'] = 'Settings';
$string['settings_intro'] = 'Issues a certificate on valid.at for every course completion in courses where this is switched on (course navigation, Certificates on valid.at). <a href="{$a}">Test the connection</a> after saving the key.';
$string['status'] = 'Status';
$string['status_failed'] = 'Refused';
$string['status_issued'] = 'Issued';
$string['status_pending'] = 'Waiting';
$string['status_revoked'] = 'Revoked';
$string['summary_off'] = 'This course does not issue certificates on valid.at.';
$string['summary_on'] = 'This course issues the certificate "{$a}" on valid.at.';
$string['task_issue'] = 'Issue certificate on valid.at';
$string['title'] = 'Kind of certificate';
$string['title_help'] = 'Appears on the certificate and the verification page, for example "First aider" or "Electrically instructed person". At most 200 characters.';
$string['validat:manage'] = 'Change settings, revoke and issue certificates on valid.at';
$string['validat:view'] = 'View certificates on valid.at in a course';
$string['validity'] = 'Validity';
$string['validity_help'] = 'Counted from the day of completion. valid.at shows the certificate as expired afterwards and, with the Pro plan, reminds the learner beforehand.';
$string['validity_months'] = '{$a} months';
$string['validity_none'] = 'No expiry';
$string['validuntil'] = 'Valid until {$a}';
$string['warning_completion'] = 'Course completion is not set up. Certificates are issued only when learners complete the course.';
$string['warning_completion_link'] = 'Set up course completion';
$string['warning_nokey'] = 'No API key for valid.at is set yet.';
