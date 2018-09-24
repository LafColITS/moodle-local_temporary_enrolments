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
require_once($CFG->dirroot. '/lib/accesslib.php');
use stdClass;

/**
 * Scheduled task (cron task) that checks for expired Temporary role assignments
 * and removes them.
 */
class expire_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task:expire', 'local_temporary_enrolments');
    }

    public function execute() {
        global $DB;

        get_config('local_temporary_enrolments', 'onoff');
        return;

        if (get_config('local_temporary_enrolments', 'onoff')) {
            // Get temporary_enrolment role.
            $role = get_temp_role();
            // Iterate through entries in our custom table.
            $expirations = $DB->get_records('local_temporary_enrolments');
            foreach ($expirations as $expiration) {
                // Check if expired.
                if ($expiration->timeend <= time()) {
                    // Check if there is a corresponding role assignment.
                    $roleassignment = $DB->get_record('role_assignments', array('id' => $expiration->roleassignid));
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
