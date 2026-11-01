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
use local_validat\local\http_response;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/fixtures/fake_http.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\local_validat\issuer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_validat\observer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_validat\task\issue_certificate::class)]
/**
 * Tests from course completion to certificate.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_validat\issuer
 * @covers     \local_validat\observer
 * @covers     \local_validat\task\issue_certificate
 */
final class issuer_test extends \advanced_testcase {
    /** @var int 2026-03-01 23:30 UTC, already 2 March in Vienna */
    const COMPLETED = 1772407800;

    /**
     * Course with certificates switched on and one enrolled learner.
     *
     * @param bool $enabled Whether the course issues certificates
     * @return array Course and learner
     */
    private function course_with_learner(bool $enabled = true): array {
        $this->resetAfterTest();
        $this->setTimezone('Europe/Vienna', 'Europe/Vienna');
        set_config('enablecompletion', 1);
        set_config('referenceprefix', 'Moodle', 'local_validat');
        $course = $this->getDataGenerator()->create_course(
            ['enablecompletion' => 1, 'fullname' => 'Erste Hilfe im Betrieb', 'shortname' => 'EH-26']
        );
        $user = $this->getDataGenerator()->create_user(
            ['firstname' => 'Maria', 'lastname' => 'Muster', 'email' => 'maria@example.com']
        );
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        issuer::save_settings($course->id, (object) [
            'enabled' => $enabled ? 1 : 0,
            'title' => 'Ersthelfer im Betrieb',
            'validmonths' => 24,
            'note' => '',
            'sendemail' => 1,
            'notify' => 1,
        ]);
        return [$course, $user];
    }

    /**
     * Client that returns the given answers.
     *
     * @param fake_http $http Transport
     * @return api_client
     */
    private function client(fake_http $http): api_client {
        return new api_client('https://valid.at', 'vat_test', $http);
    }

    /**
     * Answer of valid.at for an issued certificate.
     *
     * @return http_response
     */
    private function issued(): http_response {
        return new http_response(201, json_encode([
            'code' => 'K7M2X9QD',
            'status' => 'gueltig',
            'gueltigBis' => '2028-03-02',
            'pruefseite' => 'https://valid.at/K7M2X9QD',
            'inhaberLink' => 'https://valid.at/inhaber/abcdef',
        ]));
    }

    /**
     * A course completion is queued and issued by the task, exactly once.
     */
    public function test_completion_queues_and_task_issues(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();

        $completion = new \completion_completion(['course' => $course->id, 'userid' => $user->id]);
        $completion->mark_complete(self::COMPLETED);

        $issue = $DB->get_record('local_validat_issues', ['courseid' => $course->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertSame(issuer::STATUS_PENDING, $issue->status);
        $this->assertEquals(self::COMPLETED, $issue->timecompleted);
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks('\local_validat\task\issue_certificate'));

        $http = new fake_http([$this->issued()]);
        issuer::issue((int) $issue->id, $this->client($http));

        $body = json_decode($http->requests[0]->body, true);
        $this->assertSame([
            'inhaber' => 'Maria Muster',
            'nachweis' => 'Ersthelfer im Betrieb',
            'ausgestelltAm' => '2026-03-02',
            'pdf' => true,
            'benachrichtigen' => true,
            'inhaberEmail' => 'maria@example.com',
            'gueltigMonate' => 24,
            'referenz' => 'Moodle EH-26',
        ], $body);
        $key = 'Idempotency-Key: moodle-' . substr(sha1(get_site_identifier()), 0, 12) . '-' . $course->id . '-' . $user->id .
            '-' . self::COMPLETED;
        $this->assertContains($key, $http->requests[0]->headers);

        $issue = $DB->get_record('local_validat_issues', ['id' => $issue->id]);
        $this->assertSame(issuer::STATUS_ISSUED, $issue->status);
        $this->assertSame('K7M2X9QD', $issue->code);
        $this->assertSame('https://valid.at/K7M2X9QD', $issue->verifyurl);
        $this->assertSame('https://valid.at/inhaber/abcdef', $issue->holderurl);
        $this->assertSame('2028-03-02', $issue->validuntil);
        $this->assertNull($issue->lasterror);

        // The same completion never leads to a second request.
        $this->assertEquals($issue->id, issuer::queue($course->id, $user->id, self::COMPLETED));
        issuer::issue((int) $issue->id, $this->client($http));
        $this->assertCount(1, $http->requests);
    }

    /**
     * Without the email setting, no address is sent and valid.at sends no email.
     */
    public function test_without_email(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();
        $DB->set_field('local_validat_courses', 'sendemail', 0, ['courseid' => $course->id]);
        $id = issuer::queue($course->id, $user->id, self::COMPLETED);
        $http = new fake_http([$this->issued()]);
        issuer::issue($id, $this->client($http));
        $body = json_decode($http->requests[0]->body, true);
        $this->assertArrayNotHasKey('inhaberEmail', $body);
        $this->assertFalse($body['benachrichtigen']);
    }

    /**
     * Temporary problems keep the record waiting and let the task fail, so it is retried.
     */
    public function test_temporary_problem_is_retried_by_the_task(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();
        $id = issuer::queue($course->id, $user->id, self::COMPLETED);
        $http = new fake_http([new http_response(503, '{"fehler":"Wartung"}')]);
        try {
            issuer::issue($id, $this->client($http));
            $this->fail('Exception expected');
        } catch (api_exception $e) {
            $this->assertTrue($e->is_temporary());
        }
        $issue = $DB->get_record('local_validat_issues', ['id' => $id]);
        $this->assertSame(issuer::STATUS_PENDING, $issue->status);
        $this->assertEquals(1, $issue->attempts);
        $this->assertSame('HTTP 503: Wartung', $issue->lasterror);
    }

    /**
     * A refused request is marked as failed and can be tried again.
     */
    public function test_refused_request_can_be_tried_again(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();
        $id = issuer::queue($course->id, $user->id, self::COMPLETED);
        $http = new fake_http([new http_response(403, '{"fehler":"Die Schnittstelle ist im Paket Basis nicht enthalten."}')]);
        issuer::issue($id, $this->client($http));
        $issue = $DB->get_record('local_validat_issues', ['id' => $id]);
        $this->assertSame(issuer::STATUS_FAILED, $issue->status);
        $this->assertStringContainsString('Paket Basis', $issue->lasterror);

        $this->assertTrue(issuer::retry($id, $course->id));
        $this->assertSame(issuer::STATUS_PENDING, $DB->get_field('local_validat_issues', 'status', ['id' => $id]));
        $this->assertFalse(issuer::retry($id, $course->id + 1));
    }

    /**
     * Completions from before the course issued certificates are issued on request.
     */
    public function test_past_completions_are_issued_on_request(): void {
        [$course, $user] = $this->course_with_learner(false);
        $completion = new \completion_completion(['course' => $course->id, 'userid' => $user->id]);
        $completion->mark_complete(self::COMPLETED);
        $this->assertSame(1, issuer::count_missing($course->id));
        $this->assertSame(0, issuer::queue_existing($course->id));

        issuer::save_settings($course->id, (object) ['enabled' => 1, 'title' => 'Ersthelfer', 'validmonths' => 0, 'note' => '',
            'sendemail' => 1, 'notify' => 1]);
        $this->assertSame(1, issuer::queue_existing($course->id));
        $this->assertSame(0, issuer::count_missing($course->id));
        $this->assertSame(0, issuer::queue_existing($course->id));
    }

    /**
     * Revoking sends the reason to valid.at and marks the record.
     */
    public function test_revoke(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();
        $id = issuer::queue($course->id, $user->id, self::COMPLETED);
        issuer::issue($id, $this->client(new fake_http([$this->issued()])));

        $http = new fake_http([new http_response(200, '{"code":"K7M2X9QD","status":"widerrufen"}')]);
        issuer::revoke($id, $course->id, 'Irrtümlich ausgestellt', $this->client($http));
        $this->assertSame('https://valid.at/api/v1/aussteller/nachweise/K7M2X9QD/widerruf', $http->requests[0]->url);
        $this->assertSame('{"grund":"Irrtümlich ausgestellt"}', $http->requests[0]->body);
        $issue = $DB->get_record('local_validat_issues', ['id' => $id]);
        $this->assertSame(issuer::STATUS_REVOKED, $issue->status);
        $this->assertSame('Irrtümlich ausgestellt', $issue->revokereason);
    }

    /**
     * Deleting a course or a user removes the records of the plugin.
     */
    public function test_deleting_course_or_user_removes_records(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner();
        issuer::queue($course->id, $user->id, self::COMPLETED);
        delete_user($user);
        $this->assertFalse($DB->record_exists('local_validat_issues', ['userid' => $user->id]));

        delete_course($course, false);
        $this->assertFalse($DB->record_exists('local_validat_courses', ['courseid' => $course->id]));
    }

    /**
     * Whether the own profile shows "My certificates on valid.at".
     *
     * @param \stdClass $user Profile owner
     * @param bool $iscurrentuser Whether it is the own profile
     * @return bool
     */
    private function profile_has_link(\stdClass $user, bool $iscurrentuser = true): bool {
        global $CFG;
        require_once($CFG->dirroot . '/local/validat/lib.php');
        $tree = new \core_user\output\myprofile\tree();
        local_validat_myprofile_navigation($tree, $user, $iscurrentuser, null);
        return array_key_exists('local_validat', $tree->nodes);
    }

    /**
     * The own profile links to "My certificates on valid.at" for learners of courses with certificates and for everyone
     * with a certificate, also one that is not issued yet.
     */
    public function test_profile_link(): void {
        global $DB;
        [$course, $user] = $this->course_with_learner(false);
        $teacher = $this->getDataGenerator()->create_user();
        $outsider = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Course without certificates.
        $this->assertFalse(issuer::in_profile($user->id));
        $this->assertFalse($this->profile_has_link($user));

        // Course with certificates: everyone enrolled, before any completion.
        $DB->set_field('local_validat_courses', 'enabled', 1, ['courseid' => $course->id]);
        $this->assertTrue($this->profile_has_link($user));
        $this->assertTrue($this->profile_has_link($teacher));
        $this->assertFalse($this->profile_has_link($user, false));
        $this->assertFalse($this->profile_has_link($outsider));

        // Suspended enrolments do not count.
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        enrol_get_plugin('manual')->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
        $this->assertFalse(issuer::in_profile($user->id));

        // A certificate, even one still waiting for the task, keeps the link after the course stops issuing.
        $this->assertNotNull(issuer::queue($course->id, $user->id, self::COMPLETED));
        $DB->set_field('local_validat_courses', 'enabled', 0, ['courseid' => $course->id]);
        $this->assertTrue($this->profile_has_link($user));
        $this->assertFalse($this->profile_has_link($teacher));
    }
}
