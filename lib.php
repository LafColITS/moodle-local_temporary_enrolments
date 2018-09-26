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
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/lib/accesslib.php');
require_once($CFG->dirroot. '/admin/roles/classes/define_role_table_advanced.php');
require_once($CFG->dirroot. '/lib/moodlelib.php');

/**
 * Send an email (init, remind, upgrade, or expire).
 *
 * @param int $assignerid The id of the user who assigned the role.
 * @param int $assigneeid The id of the user whom the role was assigned to.
 * @param int $courseid The id of the course in which the role was assigned.
 * @param int $raid The id of the role assignment record.
 * @param string $which Which email to send: [ studentinit | teacherinit | remind | upgrade | expire ]
 * @param string $sendto Who to send it to: [ assigneeid | assignerid ] (defaults to assignerid)
 *
 * @return void
 */
function send_temporary_enrolments_email($assignerid, $assigneeid, $courseid, $raid, $which, $sendto='assigneeid') {
    global $DB;

    // Build 'from' object.
    $noreplyuser = \core_user::get_noreply_user();
    $from = new stdClass();
    $from->customheaders = 'Auto-Submitted: auto-generated';
    $from->maildisplay = true; // Required to prevent Notice.
    $from->email = $noreplyuser->email; // Required to prevent Notice.

    // Get related data.
    $course = $DB->get_record('course', array('id' => $courseid));
    $student = $DB->get_record('user', array('id' => $assigneeid));
    $teacher = $DB->get_record('user', array('id' => $assignerid));
    if ($DB->record_exists('local_temporary_enrolments', array('roleassignid' => $raid))) {
        $expiration = $DB->get_record('local_temporary_enrolments', array('roleassignid' => $raid));
    } else {
        $expiration = new stdClass();
        $expiration->timeend = 0;
    }

    $replace = array(
        '/\{TEACHER\}/' => $teacher->firstname,
        '/\{STUDENTFIRST\}/' => $student->firstname,
        '/\{STUDENTLAST\}/' => $student->lastname,
        '/\{STUDENTFULL\}/' => fullname($student),
        '/\{COURSE\}/' => $course->fullname,
        '/\{TIMELEFT\}/' => round(($expiration->timeend - time()) / 86400),
        '/\{SUBJECT: (.*)\}\s+/' => '',
    );

    // Get raw email content.
    $message = get_config('local_temporary_enrolments', $which . '_content');

     // Subject.
    $subject = array();
    preg_match("/\{SUBJECT: (.*)\}\s+/", $message, $subject); // Pull SUBJECT line out of message content.

    // Build final email body and subject and to address.
    $subject = preg_replace(array_keys($replace), array_values($replace), $subject[1]);
    $message = preg_replace(array_keys($replace), array_values($replace), $message);
    $to = $DB->get_record('user', array('id' => $$sendto));

    // Send email.
    email_to_user($to, $from, $subject, $message);
}

/**
 * Add a role assignment to the custom table.
 *
 * @param int $raid Role assignment id.
 * @param int $raroleid Role id.
 * @param int $timecreated Time the role assignment was created.
 *
 * @return boolean|int The id of the inserted record, or false on failure.
 */
function add_to_custom_table($raid, $roleid, $timecreated) {
    global $DB;

    // Abort if this role assignment is already stored.
    $dupe = $DB->get_record('local_temporary_enrolments', array('roleassignid' => $raid, 'roleid' => $roleid));
    if ($dupe) {
        return false;
    }

    $insert = new stdClass();
    $insert->roleassignid = $raid;
    $insert->roleid = $roleid; // Stored so we can easily check that table is up to date if role settings are changed.
    $length = get_config('local_temporary_enrolments', 'length');
    $insert->timeend = $timecreated + $length;
    $insert->timestart = $timecreated;
    return $DB->insert_record('local_temporary_enrolments', $insert);
}

/**
 * Retrieve and return the role record for the current
 * temporary marker role.
 *
 * @return object Role data.
 */
function get_temp_role() {
    global $DB;

    // I use a DB query here to avoid a weird caching issue.
    if ($id = $DB->get_record('config_plugins', array('plugin' => 'local_temporary_enrolments', 'name' => 'roleid') )->value ) {
        return $DB->get_record('role', array('id' => $id));
    }
}

/**
 * Handle change in the temporary enrolment length setting.
 *
 * @return void
 */
function handle_update_length() {
    $length = get_config('local_temporary_enrolments', 'length');
    update_length($length);
}

/**
 * Handle change in the reminder email frequency setting.
 *
 * @return void
 */
function handle_update_reminder_freq() {
    $remindfreq = get_config('local_temporary_enrolments', 'remind_freq');
    update_remind_freq($remindfreq);
}

/**
 * Remove all entries from the custom table, optionally by role id.
 *
 * @param int|string $roleid (optional) The role id to filter by. Optional.
 *
 * @return void
 */
function wipe_table($roleid = null) {
    global $DB;

    if ($roleid !== null) {
        $DB->delete_records_select('local_temporary_enrolments', "roleid <> $roleid");
    } else {
        $DB->delete_records('local_temporary_enrolments');
    }
}

/**
 * Set up adhoc task for existing assignments.
 *
 * @param int|string $roleid The id of the new temporary marker role.
 *
 * @return adhoc_task The adhoc task object.
 */
function make_task($roleid) {
    $task = new \local_temporary_enrolments\task\existing_assignments_task();
    $taskdata = new stdClass();
    $taskdata->newroleid = $roleid;
    $task->set_custom_data($taskdata);
    return $task;
}

/**
 * Handle change in temporary marker role id setting.
 *
 * @return void
 */
function handle_update_roleid() {
    $newroleid = required_param('s_local_temporary_enrolments_roleid', PARAM_ALPHANUMEXT);
    $oldroleid = get_temp_role()->id;

    // Delete custom table entries (for old role only).
    wipe_table($oldroleid);

    // If there's already an existing task, remove it.
    $existingtask = \core\task\manager::get_adhoc_tasks('\local_temporary_enrolments\task\existing_assignments_task');
    if (gettype($existingtask) == 'object') {
        \core\task\manager::adhoc_task_complete($existingtask);
    }

    // Make a new task and schedule it.
    $task = make_task($newroleid);
    \core\task\manager::queue_adhoc_task($task);
}

/**
 * Update the frequency of reminder emails in the database (based on config).
 *
 * @param int $newfreq New value for reminder frequency.
 *
 * @return void
 */
function update_remind_freq($newfreq) {
    $remind = \core\task\manager::get_scheduled_task('local_temporary_enrolments\task\remind_task');
    $remind->set_day("*/$newfreq");
    \core\task\manager::configure_scheduled_task($remind);
}

/**
 * Update the remaining length of a temporary enrolment.
 *
 * @param object $newlength New length of temporary enrolments.
 *
 * @return void
 */
function update_length($newlength) {
    global $DB;

    $expirations = $DB->get_records('local_temporary_enrolments');
    foreach ($expirations as $expiration) {
        $newtimeend = $expiration->timestart + $newlength;
        $update = new stdClass();
        $update->id = $expiration->id;
        $update->timeend = $newtimeend;
        $DB->update_record('local_temporary_enrolments', $update);
    }
}
