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
 * Restore of the certificate settings and certificates of a course.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the settings with the course settings, that is into a new course or when the restore overwrites the
 * course configuration. Certificates are restored with the users, mapped to the learners on this site, so issuing for
 * past completions does not request them again. A restore never sends anything to valid.at.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_validat_plugin extends restore_local_plugin {
    /**
     * Paths below the course.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        $paths = [new restore_path_element('local_validat_settings', $this->get_pathfor('/validat_settings'))];
        if ($this->get_setting_value('users')) {
            $paths[] = new restore_path_element('local_validat_issue', $this->get_pathfor('/validat_issues/validat_issue'));
        }
        return $paths;
    }

    /**
     * Settings of the course.
     *
     * @param array $data Record from the backup
     */
    public function process_local_validat_settings($data) {
        \local_validat\issuer::save_settings($this->task->get_courseid(), (object) $data);
    }

    /**
     * Certificate of a learner, skipped when the learner is not part of the restore.
     *
     * @param array $data Record from the backup
     */
    public function process_local_validat_issue($data) {
        $data = (object) $data;
        $userid = $this->get_mappingid('user', $data->userid);
        if ($userid) {
            \local_validat\issuer::restore_issue($this->task->get_courseid(), (int) $userid, $data);
        }
    }
}
