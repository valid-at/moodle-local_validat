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
use local_validat\local\http_response;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/fixtures/fake_http.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\backup_local_validat_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_local_validat_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_validat\issuer::class)]
/**
 * Backup and restore of the settings and certificates of a course.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_local_validat_plugin
 * @covers     \restore_local_validat_plugin
 * @covers     \local_validat\issuer
 */
final class backup_test extends \advanced_testcase {
    /** @var int 2026-03-01 23:30 UTC */
    const COMPLETED = 1772407800;

    /** @var string Class of the task that sends requests to valid.at */
    const TASK = '\local_validat\task\issue_certificate';

    /**
     * Course with certificates and two learners: one with an issued certificate, one whose request valid.at refused.
     *
     * @return array Course and the two learners
     */
    private function course_with_certificates(): array {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablecompletion', 1);
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'shortname' => 'EH-26']);
        issuer::save_settings($course->id, (object) [
            'enabled' => 1,
            'title' => 'Ersthelfer im Betrieb',
            'validmonths' => 24,
            'note' => 'Theorie und Praxis',
            'sendemail' => 1,
            'notify' => 0,
        ]);
        $client = new api_client('https://valid.at', 'vat_test', new fake_http([
            new http_response(201, json_encode([
                'code' => 'K7M2X9QD',
                'gueltigBis' => '2028-03-02',
                'pruefseite' => 'https://valid.at/K7M2X9QD',
                'inhaberLink' => 'https://valid.at/inhaber/abcdef',
            ])),
            new http_response(403, '{"fehler":"Das Kontingent ist aufgebraucht."}'),
        ]));
        $learners = [];
        foreach (['Maria', 'Karl'] as $i => $firstname) {
            $user = $this->getDataGenerator()->create_user(['firstname' => $firstname]);
            $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
            $completion = new \completion_completion(['course' => $course->id, 'userid' => $user->id]);
            $completion->mark_complete(self::COMPLETED + $i);
            $id = $DB->get_field('local_validat_issues', 'id', ['courseid' => $course->id, 'userid' => $user->id], MUST_EXIST);
            issuer::issue((int) $id, $client);
            $learners[] = $user;
        }
        return [$course, $learners[0], $learners[1]];
    }

    /**
     * Backs up a course.
     *
     * @param \stdClass $course Course
     * @param bool $users Whether the backup includes users
     * @param bool $anonymize Whether the users are anonymised
     * @return string Directory of the extracted backup
     */
    private function backup(\stdClass $course, bool $users, bool $anonymize = false): string {
        global $CFG, $USER;
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value($users);
        if ($anonymize) {
            $bc->get_plan()->get_setting('anonymize')->set_value(true);
        }
        $bc->execute_plan();
        $backupid = 'local_validat_' . $bc->get_backupid();
        $bc->get_results()['backup_destination']->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            make_backup_temp_directory($backupid)
        );
        $bc->destroy();
        return $backupid;
    }

    /**
     * Restores a backup into a new course, or into an existing one with its configuration overwritten.
     *
     * @param string $backupid Directory of the extracted backup
     * @param \stdClass|null $course Existing course, null for a new course
     * @return int Course
     */
    private function restore(string $backupid, ?\stdClass $course = null): int {
        global $USER;
        $target = $course ? \backup::TARGET_EXISTING_ADDING : \backup::TARGET_NEW_COURSE;
        $category = \core_course_category::get_default()->id;
        $courseid = $course ? (int) $course->id : (int) \restore_dbops::create_new_course('Kopie', 'EH-' . $backupid, $category);
        $rc = new \restore_controller($backupid, $courseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id, $target);
        if ($course) {
            $rc->get_plan()->get_setting('overwrite_conf')->set_value(true);
        }
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return $courseid;
    }

    /**
     * Settings of a course as saved by the course page.
     *
     * @param int $courseid Course
     * @return array
     */
    private function settings(int $courseid): array {
        global $DB;
        $settings = $DB->get_record('local_validat_courses', ['courseid' => $courseid], '*', MUST_EXIST);
        $fields = ['enabled', 'title', 'validmonths', 'note', 'sendemail', 'notify'];
        return array_intersect_key((array) $settings, array_flip($fields));
    }

    /**
     * With users, the settings and certificates move to the new course and nothing is requested again.
     */
    public function test_restore_with_users(): void {
        global $DB;
        [$course, $maria, $karl] = $this->course_with_certificates();
        $tasks = count(\core\task\manager::get_adhoc_tasks(self::TASK));

        $newcourseid = $this->restore($this->backup($course, true));

        $this->assertEquals($this->settings($course->id), $this->settings($newcourseid));
        $issues = $DB->get_records('local_validat_issues', ['courseid' => $newcourseid], '', 'userid, timecompleted, status, code, '
            . 'verifyurl, holderurl, validuntil, lasterror');
        $this->assertCount(2, $issues);
        $this->assertEquals((object) [
            'userid' => $maria->id,
            'timecompleted' => self::COMPLETED,
            'status' => issuer::STATUS_ISSUED,
            'code' => 'K7M2X9QD',
            'verifyurl' => 'https://valid.at/K7M2X9QD',
            'holderurl' => 'https://valid.at/inhaber/abcdef',
            'validuntil' => '2028-03-02',
            'lasterror' => null,
        ], $issues[$maria->id]);
        $this->assertSame(issuer::STATUS_FAILED, $issues[$karl->id]->status);
        $this->assertStringContainsString('Kontingent', $issues[$karl->id]->lasterror);

        // The restored completions have their certificates, so nothing is missing or sent.
        $this->assertSame(2, $DB->count_records_select(
            'course_completions',
            'course = ? AND timecompleted IS NOT NULL',
            [$newcourseid]
        ));
        $this->assertSame(0, issuer::count_missing($newcourseid));
        $this->assertSame(0, issuer::queue_existing($newcourseid));
        $this->assertCount($tasks, \core\task\manager::get_adhoc_tasks(self::TASK));
        $this->assertSame(2, $DB->count_records('local_validat_issues', ['courseid' => $course->id]));
    }

    /**
     * Without users, and with anonymised users, only the settings move to the new course.
     */
    public function test_restore_without_users_or_anonymised(): void {
        global $DB;
        [$course] = $this->course_with_certificates();
        foreach ([[false, false], [true, true]] as [$users, $anonymize]) {
            $newcourseid = $this->restore($this->backup($course, $users, $anonymize));
            $this->assertEquals($this->settings($course->id), $this->settings($newcourseid));
            $this->assertFalse($DB->record_exists('local_validat_issues', ['courseid' => $newcourseid]));
        }
    }

    /**
     * Restoring into the same course overwrites the settings and keeps one record per completion.
     */
    public function test_restore_into_the_same_course(): void {
        global $DB;
        [$course] = $this->course_with_certificates();
        $backupid = $this->backup($course, true);
        $before = $this->settings($course->id);
        $DB->set_field('local_validat_courses', 'enabled', 0, ['courseid' => $course->id]);

        $this->restore($backupid, $course);

        $this->assertEquals($before, $this->settings($course->id));
        $this->assertSame(1, $DB->count_records('local_validat_courses', ['courseid' => $course->id]));
        $this->assertSame(2, $DB->count_records('local_validat_issues', ['courseid' => $course->id]));
    }

    /**
     * Records from a backup are checked like answers of valid.at, and never sent.
     */
    public function test_restore_issue_checks_the_data(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $data = [
            'timecompleted' => self::COMPLETED,
            'status' => issuer::STATUS_REVOKED,
            'code' => 'K7M2X9QD',
            'verifyurl' => 'javascript:alert(1)',
            'holderurl' => 'https://valid.at/inhaber/abcdef',
            'validuntil' => 'bald',
            'attempts' => 1,
            'revokereason' => str_repeat('x', 400),
        ];

        $this->assertNull(issuer::restore_issue($course->id, $user->id, (object) (['status' => 'unbekannt'] + $data)));
        $this->assertNull(issuer::restore_issue($course->id, $user->id, (object) (['code' => 'k7m2'] + $data)));
        $this->assertNull(issuer::restore_issue($course->id, $user->id, (object) (['timecompleted' => 0] + $data)));

        $id = issuer::restore_issue($course->id, $user->id, (object) $data);
        $issue = $DB->get_record('local_validat_issues', ['id' => $id], '*', MUST_EXIST);
        $this->assertNull($issue->verifyurl);
        $this->assertSame('https://valid.at/inhaber/abcdef', $issue->holderurl);
        $this->assertNull($issue->validuntil);
        $this->assertSame(300, \core_text::strlen($issue->revokereason));

        // One record per completion; a waiting request needs no code yet.
        $this->assertNull(issuer::restore_issue($course->id, $user->id, (object) $data));
        $pending = ['timecompleted' => self::COMPLETED + 1, 'status' => issuer::STATUS_PENDING, 'code' => null];
        $this->assertNotNull(issuer::restore_issue($course->id, $user->id, (object) ($pending + $data)));
        $this->assertCount(0, \core\task\manager::get_adhoc_tasks(self::TASK));
    }
}
