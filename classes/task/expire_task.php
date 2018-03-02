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
 * @copyright  2017 onwards Andrew Zito
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_temporary_enrolments\task;
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');
require_once($CFG->dirroot. '/lib/accesslib.php');
use stdClass;

class expire_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('expire_task', 'local_temporary_enrolments');
    }

    public function execute() {
        global $DB, $CFG;

        if ($CFG->local_temporary_enrolments_onoff) {

            // Get temporary_enrolment role.
            $role = $DB->get_record('role', array('shortname' => LOCAL_TEMPORARY_ENROLMENTS_SHORTNAME));

            $expirations = $DB->get_records('local_temporary_enrolments');
            // Iterate through expiration entries in our custom table.
            foreach ($expirations as $expiration) {
                // Check if expired.
                if ($expiration->timeend <= time()) {
                    $roleassignment = $DB->get_record('role_assignments', array('id' => $expiration->roleassignid));
                    // Check if there is a corresponding role assignment.
                    if ($roleassignment) {
                        // Remove it.
                        role_unassign($role->id, $roleassignment->userid, $roleassignment->contextid);
                        $DB->delete_records('local_temporary_enrolments', array('id' => $expiration->id));
                    } else { // If there isn't a role assignment, delete the extraneous custom table entry.
                        $DB->delete_records('local_temporary_enrolments', array('id' => $expiration->id));
                    }
                }
            }
        }
    }
}
