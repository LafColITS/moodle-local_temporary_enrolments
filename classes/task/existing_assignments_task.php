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
 * Defines adhoc task class for existing assignment handling.
 *
 * @package    local_temporary_enrolments
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_temporary_enrolments\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');
require_once($CFG->dirroot. '/lib/moodlelib.php');

/**
 * Adhoc task that handles pre-existing role assignments (for when the temporary
 * marker role is changed).
 *
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class existing_assignments_task extends \core\task\adhoc_task {

    /**
     * Get component name for this adhoc task.
     *
     * @return string Component name.
     */
    public function get_component() {
        return 'local_temporary_enrolments';
    }

    /**
     * Execute adhoc task.
     */
    public function execute() {
        global $DB;

        // If existing assignments management is turned off, abort.
        // I use a DB query here to avoid a weird caching issue.
        $onoff = $DB->get_record('config_plugins',
                array('plugin' => 'local_temporary_enrolments', 'name' => 'existing_assignments'));
        if ( ! $onoff->value) {
            return true;
        }

        $customdata = $this->get_custom_data();

        $newroleid = $customdata->newroleid;

        // Add existing role assignments.
        $toadd = $DB->get_records('role_assignments', array('roleid' => $newroleid));
        $now = time();
        $timestart = get_config('local_temporary_enrolments', 'existing_assignments_start');
        $sendemail = get_config('local_temporary_enrolments', 'existing_assignments_email');

        foreach ($toadd as $assignment) {
            if ($timestart == 0) { // User has selected "assignment creation" as start time.
                $starttime = $assignment->timemodified;
            } else { // User has selected "now" as start time.
                $starttime = $now;
            }
            add_to_custom_table($assignment->id, $assignment->roleid, $starttime);
            if ($sendemail) {
                $assignerid = 1;
                $assigneeid = $assignment->userid;
                $context = $DB->get_record('context', array('id' => $assignment->contextid));
                $courseid = $context->instanceid;
                $raid = $assignment->id;
                $which = 'studentinit';
                send_temporary_enrolments_email($assignerid, $assigneeid, $courseid, $raid, $which);
            }
        }
    }
}
