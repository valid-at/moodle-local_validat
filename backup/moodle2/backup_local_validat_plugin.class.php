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
 * Backup of the certificate settings and certificates of a course.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the settings of the course to every course backup, and the certificates of the learners when the backup
 * includes users. Anonymised backups leave the certificates out, because the verification page shows the real name.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_validat_plugin extends backup_local_plugin {
    /**
     * Elements below the course.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $settings = new backup_nested_element('validat_settings', ['id'], [
            'enabled', 'title', 'validmonths', 'note', 'sendemail', 'notify', 'timecreated', 'timemodified',
        ]);
        $wrapper->add_child($settings);
        $settings->set_source_table('local_validat_courses', ['courseid' => backup::VAR_COURSEID]);

        if ($this->get_setting_value('users') && !$this->get_setting_value('anonymize')) {
            $issues = new backup_nested_element('validat_issues');
            $issue = new backup_nested_element('validat_issue', ['id'], [
                'userid', 'timecompleted', 'status', 'code', 'verifyurl', 'holderurl', 'validuntil', 'attempts',
                'lasterror', 'revokereason', 'timecreated', 'timemodified',
            ]);
            $wrapper->add_child($issues);
            $issues->add_child($issue);
            $issue->set_source_table('local_validat_issues', ['courseid' => backup::VAR_COURSEID], 'id ASC');
            $issue->annotate_ids('user', 'userid');
        }

        return $plugin;
    }
}
