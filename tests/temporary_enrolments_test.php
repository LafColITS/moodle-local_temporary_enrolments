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
 * Tests for local_temporary_enrolments
 *
 * @package    local_temporary_enrolments
 * @category   phpunit
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot. '/enrol/flatfile/lib.php');
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');
use local_temporary_enrolments\task\remind_task;
use local_temporary_enrolments\task\expire_task;

class local_temporary_enrolments_testcase extends advanced_testcase {

    /**
     * Make various data objects that all the functions need to use
     *
     * @return array of data objects
     */
    public function make() {
        set_config('local_temporary_enrolments_onoff', 1);
        unset_config('noemailever');

        $teacher = $this->getDataGenerator()->create_user(array(
        'username'  => 'teacher',
        'email'     => 'teacher@example.com',
        'firstname' => 'Tea',
        'lastname'  => 'Cher',
        ));
        $student = $this->getDataGenerator()->create_user(array(
            'username'  => 'student',
            'email'     => 'student@example.com',
            'firstname' => 'Stu',
            'lastname'  => 'Dent',
        ));
        $this->setUser($teacher);
        $course = $this->getDataGenerator()->create_course(array('shortname' => 'testcourse'));
        $role = $this->getDataGenerator()->create_role(array('shortname' => 'temporary_enrolment'));
        return array('teacher' => $teacher, 'student' => $student, 'course' => $course, 'role' => $role);
    }

    /**
     * Check email contents
     *
     * @return void
     */
    public function email_has($email, $body, $subject, $to) {
        $this->assertContains('Auto-Submitted: auto-generated', $email->header);
        $this->assertContains('noreply@', $email->from);
        foreach ($body as $s) {
            $this->assertContains($s, preg_replace('/\s*\n\s*/', ' ', $email->body));
        }
        $this->assertContains($subject, $email->subject);
        $this->assertContains($to, $email->to);
    }

    public function test_role_creation() {
        $this->resetAfterTest();
        global $DB;
        set_config('local_temporary_enrolments_onoff', true);
        create_custom_role();

        // Role should exist. Duh.
        $this->assertEquals(true, custom_role_exists());

        $temprole = $DB->get_record('role', array('shortname' => LOCAL_TEMPORARY_ENROLMENTS_SHORTNAME));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Only context level should be 50 (course).
        $contextlevels = $DB->get_records('role_context_levels', array('roleid' => $temprole->id));
        $this->assertEquals(1, count($contextlevels));
        $this->assertEquals(CONTEXT_COURSE, $contextlevels[array_keys($contextlevels)[0]]->contextlevel);

        // Get temporary role capabilities and student role capabilities from DB.
        $tempcapabilities = $DB->get_records('role_capabilities', array('roleid' => $temprole->id));
        $studentcapabilities = $DB->get_records('role_capabilities', array('roleid' => $studentrole->id));
        // Grab just the 'capability' and 'permission' properties of each DB object.
        $tempcapabilities = array_map(function($o) { return array($o->capability, $o->permission); }, $tempcapabilities);
        $studentcapabilities = array_map(function($o) { return array($o->capability, $o->permission); }, $studentcapabilities);
        // Sort alphabetically by 'capability' (which is now index 0) (also conveniently makes top-level indexes equal).
        usort($tempcapabilities, function($a, $b) { return strcmp($a[0], $b[0]); });
        usort($studentcapabilities, function($a, $b) { return strcmp($a[0], $b[0]); });

        // Capabilities should be the same.
        $this->assertEquals($tempcapabilities, $studentcapabilities);
    }

    /**
     * Does initialize() insert an expiration entry into our custom table?
     *
     * @return void
     */
    public function test_init_insert_expiration_entry() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $context = \context_course::instance($data['course']->id);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'manual',
            ),
        ));

        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(0, count($customtable)); // Table should be empty.

        $event->trigger();

        $customtable = $DB->get_records('local_temporary_enrolments');
        $expiration = $customtable[array_keys($customtable)[0]];
        $this->assertEquals(1, count($customtable)); // Now should have 1 entry.
        $this->assertEquals($event->other['id'], $expiration->roleassignid);
        $this->assertEquals(0, $expiration->upgraded);
    }

    /**
     * With the plugin turned OFF:
     * Does initialize() NOT insert an expiration entry into our custom table?
     *
     * @return void
     */
    public function test_init_insert_expiration_entry_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $context = \context_course::instance($data['course']->id);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'manual',
            ),
        ));

        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(0, count($customtable)); // Table should be empty.

        $event->trigger();

        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(0, count($customtable)); // Table should STILL be empty.
    }

    /**
     * Does initialize() automatically remove temporary enrolments if there is already another role?
     *
     * @return void
     */
    public function test_init_remove_redundant_temp_enrol() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $selfenrol = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $se = new enrol_self_plugin();
        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $se->enrol_user($selfenrol, $data['student']->id, $studentrole->id);
        $roles = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roles));
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $this->assertEquals(1, count($roles));
    }

    /**
     * Does initialize() correctly email student and teacher?
     *
     * @return void
     */
    public function test_init_emails() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'manual',
            ),
        ));

        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();

        $body = array('Dear Stu', 'temporary access to the Moodle site for '.$data['course']->fullname);
        $this->email_has($results[0], $body, 'Temporary enrolment granted for '.$data['course']->fullname, 'student@');

        $body = array('Dear Tea', 'You have granted Stu Dent temporary access to '.$data['course']->fullname);
        $this->email_has($results[1], $body, 'Temporary enrolment granted to Stu Dent for '.$data['course']->fullname, 'teacher@');

        // Studentinit turned off.
        set_config('local_temporary_enrolments_studentinit_onoff', 0);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 124,
                'component' => 'manual',
            ),
        ));
        $sink = $this->redirectEmails();
        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(1, count($results));
        $body = array('Dear Tea', 'You have granted Stu Dent temporary access to '.$data['course']->fullname);
        $this->email_has($results[0], $body, 'Temporary enrolment granted to Stu Dent for '.$data['course']->fullname, 'teacher@');

        // Teacherinit turned off.
        set_config('local_temporary_enrolments_teacherinit_onoff', 0);
        set_config('local_temporary_enrolments_studentinit_onoff', 1);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 125,
                'component' => 'manual',
            ),
        ));
        $sink = $this->redirectEmails();
        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(1, count($results));
        $body = array('Dear Stu', 'temporary access to the Moodle site for '.$data['course']->fullname);
        $this->email_has($results[0], $body, 'Temporary enrolment granted for '.$data['course']->fullname, 'student@');

        // Both initial emails off.
        set_config('local_temporary_enrolments_studentinit_onoff', 0);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 126,
                'component' => 'manual',
            ),
        ));
        $sink = $this->redirectEmails();
        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * With plugin turned OFF:
     * Does initialize() NOT email student and teacher?
     *
     * @return void
     */
    public function test_init_emails_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'manual',
            ),
        ));

        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * Does changing the reminder frequency in config update the DB?
     *
     * @return void
     */
    public function test_remind_frequency_update() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        $this->assertEquals(0, $task->minute);
        $this->assertEquals(8, $task->hour);
        $this->assertEquals('*/2', $task->day);

        set_config('local_temporary_enrolments_remind_freq', 4);
        update_remind_freq($task, $DB->get_record('config', array('name' => 'local_temporary_enrolments_remind_freq')));

        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        $this->assertEquals('*/4', $task->day);
    }

    /**
     * With plugin turned OFF:
     * Does changing the reminder frequency in config STILL update the DB?
     *
     * @return void
     */
    public function test_remind_frequency_update_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        set_config('local_temporary_enrolments_onoff', 0);

        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        $this->assertEquals(0, $task->minute);
        $this->assertEquals(8, $task->hour);
        $this->assertEquals('*/2', $task->day);

        set_config('local_temporary_enrolments_remind_freq', 4);
        update_remind_freq($task, $DB->get_record('config', array('name' => 'local_temporary_enrolments_remind_freq')));

        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        $this->assertEquals('*/4', $task->day);
    }

    /**
     * Does remind_task() correctly send a reminder email?
     *
     * @return void
     */
    public function test_remind_emails() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $task = new remind_task();
        $task->execute();

        $sink->close();
        $results = $sink->get_messages();

        $body = array('Dear Stu', 'temporary enrolment in '.$data['course']->fullname." will expire", "in 14 days");
        $this->email_has($results[0], $body, 'Temporary enrolment reminder for '.$data['course']->fullname, 'student@');

        // Remind emails off.
        set_config('local_temporary_enrolments_remind_onoff', 0);
        $sink = $this->redirectEmails();
        $task->execute();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * With plugin turned OFF:
     * Does remind_task() NOT send a reminder email?
     *
     * @return void
     */
    public function test_remind_emails_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $task = new remind_task();
        $task->execute();

        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * Does expire() remove the manual enrolment entry (based on situation)?
     *
     * @return void
     */
    public function test_expire_remove_enrol() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $selfenrol = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $se = new enrol_self_plugin();
        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // No roles left: remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        role_unassign($data['role'], $data['student']->id, $context->id);
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(0, count($userenrols));

        // Roles left, no other enrols: don't remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        role_assign($studentrole->id, $data['student']->id, $context->id); // And then upgrade() will unassign temp role.
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($userenrols));

        // Roles left, multiple enrols: remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']); // And then initialize() will immediately unassign temp role because there is already a role there.
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($userenrols));
    }

    /**
     * With plugin turned OFF:
     * Does expire() NEVER remove the manual enrolment entry?
     *
     * @return void
     */
    public function test_expire_remove_enrol_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $selfenrol = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $se = new enrol_self_plugin();
        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // No roles left: DON'T remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        role_unassign($data['role'], $data['student']->id, $context->id);
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($userenrols));

        // Roles left, no other enrols: DON'T remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        role_assign($studentrole->id, $data['student']->id, $context->id);
        role_unassign($data['role'], $data['student']->id, $context->id);
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($userenrols));

        // Roles left, multiple enrols: DON'T remove.
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $se->enrol_user($selfenrol, $data['student']->id, $studentrole->id);
        role_unassign($data['role'], $data['student']->id, $context->id);
        $userenrols = $DB->get_records('user_enrolments');
        $this->assertEquals(2, count($userenrols));
    }

    /**
     * Does expire() remove the expiration entry from our custom table?
     *
     * @return void
     */
    public function test_expire_remove_expiration_entry() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $context = \context_course::instance($data['course']->id);

        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(1, count($customtable));

        role_unassign($data['role'], $data['student']->id, $context->id);
        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(0, count($customtable));
    }

    /**
     * With plugin turned OFF:
     * Does expire() STILL remove the expiration entry from our custom table?
     *
     * @return void
     */
    public function test_expire_remove_expiration_entry_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $context = \context_course::instance($data['course']->id);

        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(1, count($customtable));

        set_config('local_temporary_enrolments_onoff', 0);

        role_unassign($data['role'], $data['student']->id, $context->id);
        $customtable = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(0, count($customtable));
    }

    /**
     * Do temporary roles automatically expire as expected?
     *
     * @return void
     */
    public function test_expire_automatic() {
        $this->resetAfterTest();
        global $DB, $CFG;
        set_config('local_temporary_enrolments_length', 5);

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $context = \context_course::instance($data['course']->id);

        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));

        sleep(5);
        $task = new expire_task();
        $task->execute();

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(0, count($roleassignments));
    }

    /**
     * With plugin turned OFF:
     * Do temporary roles NOT automatically expire?
     *
     * @return void
     */
    public function test_expire_automatic_off() {
        $this->resetAfterTest();
        global $DB, $CFG;
        set_config('local_temporary_enrolments_length', 5);

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $context = \context_course::instance($data['course']->id);

        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));

        sleep(5);
        $task = new expire_task();
        $task->execute();

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));
    }

    /**
     * Is update_length() executed correctly?
     *
     * @return void
     */
    public function test_length_update() {
        $this->resetAfterTest();
        global $DB;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $expire = $DB->get_record('local_temporary_enrolments', array());
        $length = $DB->get_record('config', array('name' => 'local_temporary_enrolments_length'));
        $this->assertEquals($length->value, ($expire->timeend - $expire->timestart));

        $newlength = 100;
        update_length($newlength);

        $expire = $DB->get_record('local_temporary_enrolments', array());
        $this->assertEquals($newlength, ($expire->timeend - $expire->timestart));
    }

    /**
     * Does expire() send expiration email?
     *
     * @return void
     */
    public function test_expire_email() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $roleassign = $DB->get_record('role_assignments', array('roleid' => $data['role'], 'contextid' => $context->id, 'userid' => $data['student']->id));
        $event = \core\event\role_unassigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => $roleassign->id,
                'component' => 'manual',
            ),
        ));

        $event->trigger();

        $sink->close();
        $results = $sink->get_messages();

        $body = array('Dear Stu', 'access to '.$data['course']->fullname);
        $this->email_has($results[0], $body, 'Temporary enrolment for '.$data['course']->fullname.' expired', 'student@');

        // Expire email off.
        set_config('local_temporary_enrolments_expire_onoff', 0);
        $sink = $this->redirectEmails();
        $e->unenrol_user($enrol, $data['student']->id);
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $sink->close();
        $roleassign = $DB->get_record('role_assignments', array('roleid' => $data['role'], 'contextid' => $context->id, 'userid' => $data['student']->id));
        $event = \core\event\role_unassigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => $roleassign->id,
                'component' => 'manual',
            ),
        ));
        $sink = $this->redirectEmails();
        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * With plugin turned OFF:
     * Does expire() NOT send expiration email?
     *
     * @return void
     */
    public function test_expire_email_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $roleassign = $DB->get_record('role_assignments', array('roleid' => $data['role'], 'contextid' => $context->id, 'userid' => $data['student']->id));
        $event = \core\event\role_unassigned::create(array(
            'context' => $context,
            'objectid' => $data['role'],
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => $roleassign->id,
                'component' => 'manual',
            ),
        ));

        $event->trigger();

        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * Does upgrade() unassign the temporary role?
     *
     * @return void
     */
    public function test_upgrade_remove_role() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $selfenrol = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $se = new enrol_self_plugin();
        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));

        $se->enrol_user($selfenrol, $data['student']->id, $studentrole->id);

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));
        $this->assertEquals($studentrole->id , $roleassignments[array_keys($roleassignments)[0]]->roleid);
    }

    /**
     * With plugin turned OFF:
     * Does upgrade() NOT unassign the temporary role?
     *
     * @return void
     */
    public function test_upgrade_remove_role_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $selfenrol = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $se = new enrol_self_plugin();
        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roleassignments));

        $se->enrol_user($selfenrol, $data['student']->id, $studentrole->id);

        $roleassignments = $DB->get_records('role_assignments');
        $this->assertEquals(2, count($roleassignments));
    }

    /**
     * Does upgrade() correctly send email?
     *
     * @return void
     */
    public function test_upgrade_email() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $studentrole->id,
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'flatfile',
            ),
        ));

        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();

        $this->assertEquals(1, count($results)); // Make sure it sent upgrade email and NOT expire email as well.

        $body = array('Dear Stu', 'access to '.$data['course']->fullname);
        $this->email_has($results[0], $body, 'Temporary enrolment for '.$data['course']->fullname.' upgraded!', 'student@');

        // Upgrade email off.
        set_config('local_temporary_enrolments_upgrade_onoff', 0);
        $sink = $this->redirectEmails();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);
        $sink->close();
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $studentrole->id,
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 124,
                'component' => 'flatfile',
            ),
        ));
        $sink = $this->redirectEmails();
        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();
        $this->assertEquals(0, count($results));
    }

    /**
     * With plugin turned OFF:
     * Does upgrade() NOT send email?
     *
     * @return void
     */
    public function test_upgrade_email_off() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $data = $this->make();

        set_config('local_temporary_enrolments_onoff', 0);

        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data['course']->id));
        $e = new enrol_manual_plugin();
        $e->enrol_user($enrol, $data['student']->id, $data['role']);

        $sink = $this->redirectEmails();

        $context = \context_course::instance($data['course']->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $studentrole->id,
            'relateduserid' => $data['student']->id,
            'other' => array(
                'id' => 123,
                'component' => 'flatfile',
            ),
        ));

        $event->trigger();
        $sink->close();
        $results = $sink->get_messages();

        $this->assertEquals(0, count($results));
    }
}
