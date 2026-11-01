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
 * Certificates of the current user, with links to the verification page and the PDF.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_validat\issuer;

require_login(null, false);
if (isguestuser()) {
    redirect(new moodle_url('/'));
}

$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/validat/my.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mycertificates', 'local_validat'));
$PAGE->set_heading(fullname($USER));

// Also certificates that are not issued yet, so that a learner sees the completion right away.
$records = $DB->get_records_sql(
    "SELECT i.id, i.courseid, i.timecompleted, i.status, i.code, i.verifyurl, i.holderurl, i.validuntil,
            i.revokereason, c.fullname AS coursename
       FROM {local_validat_issues} i
       JOIN {course} c ON c.id = i.courseid
      WHERE i.userid = :userid
   ORDER BY i.timecompleted DESC, i.id DESC",
    ['userid' => $USER->id]
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mycertificates', 'local_validat'));
echo html_writer::tag('p', get_string('my_intro', 'local_validat'));

if (!$records) {
    echo html_writer::tag('p', get_string('my_none', 'local_validat'));
}

$dateformat = get_string('strftimedate', 'langconfig');
$newwindow = html_writer::span(' (' . get_string('opensinnewwindow') . ')', 'sr-only visually-hidden');
foreach ($records as $record) {
    $coursecontext = context_course::instance($record->courseid, IGNORE_MISSING);
    $title = format_string($record->coursename, true, ['context' => $coursecontext ?: $context]);

    // Issued or revoked: the certificate exists on valid.at and has a code and a verification page.
    $onvalidat = in_array($record->status, [issuer::STATUS_ISSUED, issuer::STATUS_REVOKED], true);
    $details = [get_string('my_completed', 'local_validat', userdate($record->timecompleted, $dateformat))];
    if (!$onvalidat) {
        $details[] = get_string('my_notyet', 'local_validat');
    } else if ($record->status === issuer::STATUS_REVOKED) {
        $details[] = get_string('status_revoked', 'local_validat') .
            ($record->revokereason ? ': ' . s($record->revokereason) : '');
    } else if ($record->validuntil) {
        [$year, $month, $day] = array_map('intval', explode('-', $record->validuntil));
        $details[] = get_string('validuntil', 'local_validat', userdate(make_timestamp($year, $month, $day, 12), $dateformat));
    } else {
        $details[] = get_string('validity_none', 'local_validat');
    }
    if ($onvalidat && $record->code) {
        $details[] = get_string('code', 'local_validat') . ' ' . issuer::format_code($record->code);
    }

    $links = [];
    if ($onvalidat && $record->verifyurl) {
        $links[] = html_writer::link(
            $record->verifyurl,
            get_string('my_verify', 'local_validat') . $newwindow,
            ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener noreferrer']
        );
    }
    if ($record->holderurl && $record->status === issuer::STATUS_ISSUED) {
        $links[] = html_writer::link(
            $record->holderurl,
            get_string('my_holder', 'local_validat') . $newwindow,
            ['class' => 'btn btn-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']
        );
    }

    echo $OUTPUT->box_start('generalbox mb-3');
    echo $OUTPUT->heading($title, 3);
    echo html_writer::tag('p', implode(' · ', $details));
    if ($links) {
        echo html_writer::div(implode(' ', $links), 'd-flex flex-wrap gap-2');
    }
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
