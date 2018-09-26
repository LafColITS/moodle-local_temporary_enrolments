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

namespace local_temporary_enrolments;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/lib/moodlelib.php');
require_once($CFG->dirroot. '/enrol/manual/lib.php');
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');
require_once($CFG->dirroot. '/lib/accesslib.php');
use stdClass;

/**
 * This class defines callback functions that respond to Moodle events.
 *
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * Actions to take upon a user being assigned the 'temporary_enrolment' role
     * Most likely will be initial enrolment
     * Possibly could be role re-upping to reset expiration timer
     *
     * @param \core\event\role_assigned $event
     * @return void
     */
    public static function initialize($event) {
        global $DB;

        $role = get_temp_role();
        if (!get_config('local_temporary_enrolments', 'onoff') || $event->objectid != $role->id) {
            return;
        }

        // Break out event data into semantic/shorter variables.
        $assigner = $event->userid;
        $assignee = $event->relateduserid;
        $assignedrole = $event->objectid;
        $context = $event->contextid;
        $course = $event->courseid;
        $roleassignment = $event->other['id'];

        $allroles = $DB->get_records('role_assignments', array('userid' => $assignee, 'contextid' => $context));

        if (count($allroles) > 1) {
            role_unassign($role->id, $assignee, $context);
        } else {

            // Send STUDENT initial email.
            if (get_config('local_temporary_enrolments', 'studentinit_onoff')) {
                $which = 'studentinit';
                send_temporary_enrolments_email($assigner, $assignee, $course, $roleassignment, $which);
            }

            // Send TEACHER initial email.
            if (get_config('local_temporary_enrolments', 'teacherinit_onoff')) {
                $which = 'teacherinit';
                send_temporary_enrolments_email($assigner, $assignee, $course, $roleassignment, $which, 'assignerid');
            }

            // Set expiration time.
            add_to_custom_table($roleassignment, $assignedrole, $event->timecreated);
        }
    }

    /**
     * Actions to be taken when a temporary_enrolment user is enrolled fully
     *
     * @param \core\event\role_assigned $event
     * @return void
     */
    public static function upgrade($event) {
        global $DB;

        $role = get_temp_role();
        if (!get_config('local_temporary_enrolments', 'onoff') || $event->objectid == $role->id) {
            return;
        }

        // Break out event data into semantic/shorter variables.
        $assigner = $event->userid;
        $assignee = $event->relateduserid;
        $context = $event->contextid;
        $course = $event->courseid;
        $roleassignment = $event->other['id'];

        // Does student have temporary role?
        $where = array('userid' => $assignee, 'contextid' => $context, 'roleid' => $role->id);
        $hasrole = $DB->record_exists('role_assignments', $where);

        // If student has temp role...
        if ($hasrole) {
            // Send upgrade email.
            if (get_config('local_temporary_enrolments', 'upgrade_onoff')) {
                $which = 'upgrade';
                send_temporary_enrolments_email($assigner, $assignee, $course, $roleassignment, $which);
            }

            // Remove temp role and update the entry in our custom table.
            $roleassignment = $DB->get_record('role_assignments', $where); // We can reuse the WHERE array from earlier.
            $expiration = $DB->get_record('local_temporary_enrolments', array('roleassignid' => $roleassignment->id));
            $update = new stdClass();
            $update->id = $expiration->id;
            $update->upgraded = 1;
            $DB->update_record('local_temporary_enrolments', $update);
            role_unassign($role->id, $assignee, $context);
        }
    }

    /**
     * Actions to be taken on 'temporary_enrolment' role unassignment
     *
     * @param \core\event\role_unassigned $event
     * @return void
     */
    public static function expire($event) {
        global $DB;

        $role = get_temp_role();
        // Is this an event involving the temporary role?
        if ($event->objectid == $role->id) {

            // Break out event data into semantic/shorter variables.
            $assigner = $event->userid;
            $assignee = $event->relateduserid;
            $context = $event->contextid;
            $course = $event->courseid;
            $roleassignment = $event->other['id'];

            // Remove entry from our custom table (but save it for later in this function).
            $expiration = $DB->get_record('local_temporary_enrolments', array('roleassignid' => $roleassignment));
            if ($expiration) {
                $DB->delete_records('local_temporary_enrolments', array('id' => $expiration->id));
            }
        } else {
            return;
        }

        // Is the plugin turned on?
        if (!get_config('local_temporary_enrolments', 'onoff')) {
            return;
        }

        // Check if the enrolment was removed by upgrade(), and if not, send expiration email.
        if (gettype($expiration) == 'object' && !$expiration->upgraded && get_config('local_temporary_enrolments', 'expire_onoff')) {
            $which = 'expire';
            send_temporary_enrolments_email($assigner, $assignee, $course, $roleassignment, $which);
        }

        // Remove manual enrolment if there are no roles...
        $plugin = new \enrol_manual_plugin();
        $manualenrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $course));
        if (!$DB->record_exists('role_assignments',  array('userid' => $assignee, 'contextid' => $context))) {
            $plugin->unenrol_user($manualenrol, $assignee);
        } else {
            // ...or else if there are other enrolments.
            $sql = "SELECT * FROM {user_enrolments} ";
            $sql .= "INNER JOIN {enrol} ON {user_enrolments}.enrolid={enrol}.id ";
            $sql .= "WHERE {user_enrolments}.userid=$assignee AND {enrol}.courseid=$course";
            $userenrols = $DB->get_records_sql($sql);
            if (count($userenrols) > 1) {
                $plugin->unenrol_user($manualenrol, $assignee);
            }
        }
    }
}
