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
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot. '/lib/accesslib.php');
require_once($CFG->dirroot. '/admin/roles/classes/define_role_table_advanced.php');
require_once($CFG->dirroot. '/lib/moodlelib.php');

define("LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_SHORTNAME", "temporary_enrolment");
define("LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_FULLNAME", "Temporarily Enrolled");

/**
 * Send an email (init, remind, upgrade, or expire).
 *
 * @param object $data This should be an event object (or in the case of remind_task(), a fake event object).
 * @param string $which studentinit | teacherinit | remind | upgrade | expire
 * @param string $sendto relateduserid | userid (defaults to student, can be set to 'userid' to send to teacher)
 *
 * @return void
 */
function send_temporary_enrolments_email($data, $which, $sendto='relateduserid') {
    global $DB, $CFG;

    // Build 'from' object.
    $noreplyuser = \core_user::get_noreply_user();
    $from = new stdClass();
    $from->customheaders = 'Auto-Submitted: auto-generated';
    $from->maildisplay = true; // Required to prevent Notice.
    $from->email = $noreplyuser->email; // Required to prevent Notice.

    // Get related data.
    $course = $DB->get_record('course', array('id' => $data->courseid));
    $student = $DB->get_record('user', array('id' => $data->relateduserid));
    $teacher = $DB->get_record('user', array('id' => $data->userid));
    if ($DB->record_exists('local_temporary_enrolments', array('roleassignid' => $data->other['id']))) {
        $expiration = $DB->get_record('local_temporary_enrolments', array('roleassignid' => $data->other['id']));
    } else {
        $expiration = new stdClass();
        $expiration->timeend = 0;
    }

    // Patterns and replaces for email generation.
    $patterns = array(
        '/\{TEACHER\}/',
        '/\{STUDENTFIRST\}/',
        '/\{STUDENTLAST\}/',
        '/\{STUDENTFULL\}/',
        '/\{COURSE\}/',
        '/\{TIMELEFT\}/',
        '/\{SUBJECT: (.*)\}\s+/',
    );
    $replaces = array(
        $teacher->firstname,
        $student->firstname,
        $student->lastname,
        fullname($student),
        $course->fullname,
        (round(($expiration->timeend - time()) / 86400)),
        '',
    );

    // Get raw email content.
    $message = $CFG->{'local_temporary_enrolments_'.$which.'_content'};

     // Subject.
    $subject = array();
    preg_match("/\{SUBJECT: (.*)\}\s+/", $message, $subject); // Pull SUBJECT line out of message content.

    // Build final email body and subject and to address.
    $subject = preg_replace($patterns, $replaces, $subject[1]);
    $message = preg_replace($patterns, $replaces, $message);
    $to = $DB->get_record('user', array('id' => $data->{$sendto}));

    // Send email.
    email_to_user($to, $from, $subject, $message);
}

/**
 * Check if temporary enrolment role exists
 *
 * @return boolean exists or not
 */
function custom_role_exists() {
    global $DB;

    if ($DB->record_exists('role', array('shortname' => LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_SHORTNAME))) {
        return true;
    }
    return false;
}

function get_temp_role() {
  global $DB;

  $shortname = $DB->get_record('config', array('name' => 'local_temporary_enrolments_rolename'));
  if ($shortname) {
    return $DB->get_record('role', array('shortname' => $shortname));
  }
}

/**
 * Create temporary_enrolment role
 *
 * @return void
 */
function create_custom_role() {
    global $DB;

    // Create the role entry.
    $description = "A role for temporary course enrolment, used by the Temporary Enrolments plugin.";
    create_role(LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_FULLNAME, LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_SHORTNAME, $description, 'student');
    $role = $DB->get_record('role', array('shortname' => LOCAL_TEMPORARY_ENROLMENTS_CUSTOM_SHORTNAME));

    // Set context levels (50 only).
    set_role_contextlevels($role->id, array(CONTEXT_COURSE));

    $context = context_system::instance();

    // Loop through student capabilities and assign them to temporary_enrolment.
    $capabilities = get_default_capabilities('student');
    foreach ($capabilities as $name => $val) {
        assign_capability($name, $val, $role->id, $context->id);
    }
}

/**
 * Update the frequency of reminder emails in the database (based on config)
 *
 * @param object $task Should be a the remind_task entry from task_scheduled table
 * @param object $newfreq Should be the config entry for remind frequency
 *
 * @return void
 */
function update_remind_freq($task, $newfreq) {
    global $DB;
    $update = new stdClass();
    $update->id = $task->id;
    $update->day = '*/'.$newfreq->value;
    $DB->update_record('task_scheduled', $update);
}

/**
 * Update the remaining length of a temporary enrolment
 *
 * @param object $newlength Should be the updated length of the temporary enrolment
 *
 * @return void
 */function update_length($newlength) {
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
