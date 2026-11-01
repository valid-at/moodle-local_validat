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
 * Navigation for local_validat.
 *
 * @package    local_validat
 * @copyright  2026 dawox GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds "Certificates on valid.at" to the course navigation for teachers and managers.
 *
 * @param navigation_node $navigation Course navigation
 * @param stdClass $course Course
 * @param context_course $context Course context
 */
function local_validat_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    if (!has_capability('local/validat:view', $context)) {
        return;
    }
    $navigation->add(
        get_string('coursenav', 'local_validat'),
        new moodle_url('/local/validat/course.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_validat',
        new pix_icon('i/badge', '')
    );
}

/**
 * Adds "My certificates on valid.at" to the own profile of learners in courses with certificates and of everyone who has
 * a certificate.
 *
 * @param core_user\output\myprofile\tree $tree Profile tree
 * @param stdClass $user Profile owner
 * @param bool $iscurrentuser Whether it is the own profile
 * @param stdClass|null $course Course, if viewed in a course
 */
function local_validat_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (!$iscurrentuser || isguestuser($user) || !\local_validat\issuer::in_profile($user->id)) {
        return;
    }
    $tree->add_node(new core_user\output\myprofile\node(
        'miscellaneous',
        'local_validat',
        get_string('mycertificates', 'local_validat'),
        null,
        new moodle_url('/local/validat/my.php')
    ));
}
