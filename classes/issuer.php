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

namespace local_validat;

use local_validat\local\api_client;
use local_validat\local\api_exception;
use local_validat\local\payload;

/**
 * Issues certificates on valid.at for course completions.
 *
 * A completion is recorded first and sent by an ad hoc task, so a slow or
 * unreachable valid.at never delays Moodle. Temporary problems are retried by
 * the task system; permanent ones are shown on the course page, where a
 * manager can try again after fixing the cause.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issuer {
    /** @var string Waiting for the task */
    public const STATUS_PENDING = 'pending';
    /** @var string Certificate exists on valid.at */
    public const STATUS_ISSUED = 'issued';
    /** @var string valid.at refused the request */
    public const STATUS_FAILED = 'failed';
    /** @var string Certificate revoked on valid.at */
    public const STATUS_REVOKED = 'revoked';

    /** @var string[] All states */
    public const STATES = [self::STATUS_PENDING, self::STATUS_ISSUED, self::STATUS_FAILED, self::STATUS_REVOKED];

    /**
     * Settings of a course, or defaults for a course without settings.
     *
     * @param \stdClass $course Course
     * @return \stdClass
     */
    public static function settings(\stdClass $course): \stdClass {
        global $DB;
        $settings = $DB->get_record('local_validat_courses', ['courseid' => $course->id]);
        if ($settings) {
            return $settings;
        }
        return (object) [
            'id' => 0,
            'courseid' => $course->id,
            'enabled' => 0,
            'title' => \core_text::substr($course->fullname, 0, payload::MAX_TITLE),
            'validmonths' => 0,
            'note' => '',
            'sendemail' => (int) get_config('local_validat', 'sendemail'),
            'notify' => (int) get_config('local_validat', 'notify'),
        ];
    }

    /**
     * Saves the settings of a course.
     *
     * @param int $courseid Course
     * @param \stdClass $data Data from the form
     */
    public static function save_settings(int $courseid, \stdClass $data): void {
        global $DB;
        $record = (object) [
            'courseid' => $courseid,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'title' => payload::cut((string) $data->title, payload::MAX_TITLE),
            'validmonths' => max(0, (int) $data->validmonths),
            'note' => payload::cut((string) ($data->note ?? ''), payload::MAX_NOTE, false),
            'sendemail' => empty($data->sendemail) ? 0 : 1,
            'notify' => empty($data->sendemail) || empty($data->notify) ? 0 : 1,
            'timemodified' => time(),
        ];
        $existing = $DB->get_record('local_validat_courses', ['courseid' => $courseid]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_validat_courses', $record);
        } else {
            $record->timecreated = $record->timemodified;
            $DB->insert_record('local_validat_courses', $record);
        }
    }

    /**
     * Records a course completion and queues the request to valid.at.
     *
     * @param int $courseid Course
     * @param int $userid Learner
     * @param int $timecompleted Time of the completion
     * @return int|null Id of the record, null when the course does not issue certificates
     */
    public static function queue(int $courseid, int $userid, int $timecompleted): ?int {
        global $DB;
        if (!$DB->record_exists('local_validat_courses', ['courseid' => $courseid, 'enabled' => 1])) {
            return null;
        }
        $now = time();
        $existing = $DB->get_record(
            'local_validat_issues',
            ['courseid' => $courseid, 'userid' => $userid, 'timecompleted' => $timecompleted]
        );
        if ($existing) {
            if ($existing->status === self::STATUS_ISSUED || $existing->status === self::STATUS_REVOKED) {
                return (int) $existing->id;
            }
            $DB->update_record(
                'local_validat_issues',
                (object) ['id' => $existing->id, 'status' => self::STATUS_PENDING, 'lasterror' => null, 'timemodified' => $now]
            );
            $id = (int) $existing->id;
        } else {
            $id = (int) $DB->insert_record('local_validat_issues', (object) [
                'courseid' => $courseid,
                'userid' => $userid,
                'timecompleted' => $timecompleted,
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        $task = new task\issue_certificate();
        $task->set_custom_data(['issueid' => $id]);
        $task->set_component('local_validat');
        \core\task\manager::queue_adhoc_task($task, true);
        return $id;
    }

    /**
     * Queues certificates for completions from before the course issued certificates.
     *
     * @param int $courseid Course
     * @return int Number of queued completions
     */
    public static function queue_existing(int $courseid): int {
        global $DB;
        $completions = $DB->get_records_sql(
            "SELECT cc.id, cc.userid, cc.timecompleted
               FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
              WHERE cc.course = :courseid
                AND cc.timecompleted IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1
                      FROM {local_validat_issues} i
                     WHERE i.courseid = cc.course
                       AND i.userid = cc.userid
                       AND i.timecompleted = cc.timecompleted
                )",
            ['courseid' => $courseid]
        );
        $count = 0;
        foreach ($completions as $completion) {
            if (self::queue($courseid, (int) $completion->userid, (int) $completion->timecompleted)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Number of completions without a certificate.
     *
     * @param int $courseid Course
     * @return int
     */
    public static function count_missing(int $courseid): int {
        global $DB;
        return $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
              WHERE cc.course = :courseid
                AND cc.timecompleted IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1
                      FROM {local_validat_issues} i
                     WHERE i.courseid = cc.course
                       AND i.userid = cc.userid
                       AND i.timecompleted = cc.timecompleted
                )",
            ['courseid' => $courseid]
        );
    }

    /**
     * Tries a refused or still waiting request again, for example after the API key was corrected.
     *
     * @param int $issueid Record
     * @param int $courseid Course the record must belong to
     * @return bool Whether it was queued
     */
    public static function retry(int $issueid, int $courseid): bool {
        global $DB;
        $issue = $DB->get_record('local_validat_issues', ['id' => $issueid, 'courseid' => $courseid]);
        if (!$issue || ($issue->status !== self::STATUS_FAILED && $issue->status !== self::STATUS_PENDING)) {
            return false;
        }
        return self::queue($courseid, (int) $issue->userid, (int) $issue->timecompleted) !== null;
    }

    /**
     * Adds a record from a course backup. Nothing is sent to valid.at: the record stands for a certificate that exists
     * there already, or for a request that a manager can try again on the course page.
     *
     * @param int $courseid Course the backup is restored into
     * @param int $userid Learner on this site
     * @param \stdClass $data Record from the backup
     * @return int|null Id of the new record, null when the completion has a record already or the data is not valid
     */
    public static function restore_issue(int $courseid, int $userid, \stdClass $data): ?int {
        global $DB;
        $status = (string) ($data->status ?? '');
        $timecompleted = (int) ($data->timecompleted ?? 0);
        $code = (string) ($data->code ?? '');
        $code = preg_match('/^[A-Z0-9]{8}$/', $code) ? $code : null;
        if (!in_array($status, self::STATES, true) || $timecompleted <= 0) {
            return null;
        }
        if (($status === self::STATUS_ISSUED || $status === self::STATUS_REVOKED) && $code === null) {
            return null;
        }
        $completion = ['courseid' => $courseid, 'userid' => $userid, 'timecompleted' => $timecompleted];
        if ($DB->record_exists('local_validat_issues', $completion)) {
            return null;
        }
        $validuntil = (string) ($data->validuntil ?? '');
        $now = time();
        return (int) $DB->insert_record('local_validat_issues', (object) ($completion + [
            'status' => $status,
            'code' => $code,
            'verifyurl' => self::web_url($data->verifyurl ?? null),
            'holderurl' => self::web_url($data->holderurl ?? null),
            'validuntil' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $validuntil) ? $validuntil : null,
            'attempts' => min(9999, max(0, (int) ($data->attempts ?? 0))),
            'lasterror' => isset($data->lasterror) ? (string) $data->lasterror : null,
            'revokereason' => isset($data->revokereason) ? \core_text::substr((string) $data->revokereason, 0, 300) : null,
            'timecreated' => (int) ($data->timecreated ?? 0) ?: $now,
            'timemodified' => (int) ($data->timemodified ?? 0) ?: $now,
        ]));
    }

    /**
     * Sends the request for one completion to valid.at. Called by the task.
     *
     * @param int $issueid Record
     * @param api_client|null $client Client, by default with the settings of the site
     * @throws api_exception For temporary problems, so the task system tries again later
     */
    public static function issue(int $issueid, ?api_client $client = null): void {
        global $DB;
        $issue = $DB->get_record('local_validat_issues', ['id' => $issueid]);
        if (!$issue || $issue->status !== self::STATUS_PENDING) {
            return;
        }
        $settings = $DB->get_record('local_validat_courses', ['courseid' => $issue->courseid]);
        $user = $DB->get_record('user', ['id' => $issue->userid, 'deleted' => 0]);
        if (!$settings || !$settings->enabled || !$user) {
            self::fail($issue, get_string('error_notavailable', 'local_validat'));
            return;
        }
        $course = $DB->get_record('course', ['id' => $issue->courseid], 'id, shortname');
        if (!$course) {
            self::fail($issue, get_string('error_notavailable', 'local_validat'));
            return;
        }

        $email = null;
        if ($settings->sendemail && validate_email($user->email)) {
            $email = $user->email;
        }
        $prefix = trim((string) get_config('local_validat', 'referenceprefix'));
        $data = payload::build(
            fullname($user),
            $email,
            $settings->title,
            (int) $issue->timecompleted,
            \core_date::get_server_timezone(),
            (int) $settings->validmonths,
            trim($prefix . ' ' . $course->shortname),
            (string) $settings->note,
            (bool) $settings->notify
        );
        $key = payload::idempotency_key(
            get_site_identifier(),
            (int) $issue->courseid,
            (int) $issue->userid,
            (int) $issue->timecompleted
        );

        try {
            $client = $client ?? api_client::from_config();
            $result = $client->issue($data, $key);
        } catch (api_exception $e) {
            if ($e->is_temporary()) {
                $DB->update_record('local_validat_issues', (object) [
                    'id' => $issue->id,
                    'attempts' => $issue->attempts + 1,
                    'lasterror' => self::error_text($e),
                    'timemodified' => time(),
                ]);
                throw $e;
            }
            self::fail($issue, self::error_text($e));
            return;
        }

        $code = isset($result['code']) && is_string($result['code']) ? $result['code'] : '';
        if (!preg_match('/^[A-Z0-9]{8}$/', $code)) {
            self::fail($issue, get_string('error_answer', 'local_validat'));
            return;
        }
        $DB->update_record('local_validat_issues', (object) [
            'id' => $issue->id,
            'status' => self::STATUS_ISSUED,
            'code' => $code,
            'verifyurl' => self::web_url($result['pruefseite'] ?? null),
            'holderurl' => self::web_url($result['inhaberLink'] ?? null),
            'validuntil' => isset($result['gueltigBis']) && is_string($result['gueltigBis'])
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', $result['gueltigBis']) ? $result['gueltigBis'] : null,
            'attempts' => $issue->attempts + 1,
            'lasterror' => null,
            'timemodified' => time(),
        ]);
    }

    /**
     * Revokes an issued certificate on valid.at.
     *
     * @param int $issueid Record
     * @param int $courseid Course the record must belong to
     * @param string $reason Reason, shown on the verification page
     * @param api_client|null $client Client, by default with the settings of the site
     * @throws api_exception When valid.at refuses or cannot be reached
     */
    public static function revoke(int $issueid, int $courseid, string $reason, ?api_client $client = null): void {
        global $DB;
        $issue = $DB->get_record('local_validat_issues', ['id' => $issueid, 'courseid' => $courseid], '*', MUST_EXIST);
        if ($issue->status !== self::STATUS_ISSUED || empty($issue->code)) {
            return;
        }
        $client = $client ?? api_client::from_config();
        $client->revoke($issue->code, $reason);
        $DB->update_record('local_validat_issues', (object) [
            'id' => $issue->id,
            'status' => self::STATUS_REVOKED,
            'revokereason' => \core_text::substr($reason, 0, 300),
            'timemodified' => time(),
        ]);
    }

    /**
     * Error for people: what valid.at answered, or that it could not be reached.
     *
     * @param api_exception $e Error
     * @return string
     */
    public static function error_text(api_exception $e): string {
        if ($e->status === 0) {
            return trim(get_string('error_unreachable', 'local_validat') . ' ' . $e->getMessage());
        }
        return $e->describe();
    }

    /**
     * Whether the own profile links to "My certificates on valid.at": the user has a certificate, also one that is not
     * issued yet, or is enrolled in a course that issues certificates.
     *
     * @param int $userid User
     * @return bool
     */
    public static function in_profile(int $userid): bool {
        global $DB;
        if ($DB->record_exists('local_validat_issues', ['userid' => $userid])) {
            return true;
        }
        return $DB->record_exists_sql(
            "SELECT 1
               FROM {local_validat_courses} vc
               JOIN {enrol} e ON e.courseid = vc.courseid AND e.status = :instanceenabled
               JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = :useractive
              WHERE vc.enabled = 1 AND ue.userid = :userid",
            ['instanceenabled' => ENROL_INSTANCE_ENABLED, 'useractive' => ENROL_USER_ACTIVE, 'userid' => $userid]
        );
    }

    /**
     * Code as printed on the PDF and the verification page, for example K7M2-X9QD.
     *
     * @param string $code Code with eight characters
     * @return string
     */
    public static function format_code(string $code): string {
        return strlen($code) === 8 ? substr($code, 0, 4) . '-' . substr($code, 4) : $code;
    }

    /**
     * Marks a request as refused.
     *
     * @param \stdClass $issue Record
     * @param string $error Reason
     */
    private static function fail(\stdClass $issue, string $error): void {
        global $DB;
        $DB->update_record('local_validat_issues', (object) [
            'id' => $issue->id,
            'status' => self::STATUS_FAILED,
            'attempts' => $issue->attempts + 1,
            'lasterror' => $error,
            'timemodified' => time(),
        ]);
    }

    /**
     * Address from the answer, only if it is a web address.
     *
     * @param mixed $url Value from the answer
     * @return string|null
     */
    private static function web_url($url): ?string {
        if (!is_string($url) || strlen($url) > 255 || !preg_match('~^https?://[^\s"<>]+$~', $url)) {
            return null;
        }
        return $url;
    }
}
