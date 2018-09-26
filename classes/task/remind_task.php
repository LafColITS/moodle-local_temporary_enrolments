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
 * @package    local_temporary_enrolments
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_temporary_enrolments\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');

/**
 * Scheduled task (cron task) that sends out reminder emails.
 *
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remind_task extends \core\task\scheduled_task {

    /**
     * Get name of scheduled task.
     *
     * @return string The name of the scheduled task.
     */
    public function get_name() {
        return get_string('task:remind', 'local_temporary_enrolments');
    }

    /**
     * Execute scheduled task.
     */
    public function execute() {
        global $DB;

        if (get_config('local_temporary_enrolments', 'onoff')) {

            // Get temporary_enrolment role id.
            $role = get_temp_role();

            // Iterate over temporary role assignments.
            $roleassignments = $DB->get_records('role_assignments', array('roleid' => $role->id));
            foreach ($roleassignments as $roleassignment) {
                // Send reminder email.
                $managedbyplugin = $DB->count_records('local_temporary_enrolments', array('roleassignid' => $roleassignment->id));
                if (get_config('local_temporary_enrolments', 'remind_onoff') && $managedbyplugin > 0) {
                    $student = $DB->get_record('user', array('id' => $roleassignment->userid));
                    $context = $DB->get_record('context', array('id' => $roleassignment->contextid));
                    $course = $DB->get_record('course', array('id' => $context->instanceid));

                    $assignerid = 1; // Fake the 'from' user to prevent errors.
                    $assigneeid = $student->id;
                    $courseid = $course->id;
                    $raid = $roleassignment->id;
                    $which = 'remind';
                    send_temporary_enrolments_email($assignerid, $assigneeid, $courseid, $raid, $which);
                }
            }
        }
    }
}
