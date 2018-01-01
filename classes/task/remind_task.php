<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_provisional_enrolments
 * @copyright  2017 onwards Andrew Zito
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_provisional_enrolments\task;
require_once($CFG->dirroot. '/local/provisional_enrolments/lib.php');
use stdClass;

class remind_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('remind_task', 'local_provisional_enrolments');
    }

    public function execute() {
        global $DB, $CFG;

        if ($CFG->local_provisional_enrolments_onoff) {

            // Get temporary_enrollment role id.
            $role = $DB->get_record('role', array('shortname' => LOCAL_PROVISIONAL_ENROLMENTS_SHORTNAME));

            $roleassignments = $DB->get_records('role_assignments', array('roleid' => $role->id));
            foreach ($roleassignments as $roleassignment) {
                // Send reminder email.
                if ($CFG->local_provisional_enrolments_remind_onoff) {
                    $student = $DB->get_record('user', array('id' => $roleassignment->userid));
                    $context = $DB->get_record('context', array('id' => $roleassignment->contextid));
                    $course = $DB->get_record('course', array('id' => $context->instanceid));
                    $data = new stdClass();
                    $data->relateduserid = $student->id;
                    $data->userid = 1; // Just fake it to prevent errors in the email function.
                    $data->courseid = $course->id;
                    $data->other = array('id' => $roleassignment->id);
                    send_provisional_enrolments_email($data, 'remind');
                }
            }
        }
    }
}
