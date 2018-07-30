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

    public $eventid = 100;

    /**
     * Make various data objects that all the functions need to use
     *
     * @return array of data objects
     */
    public function setUp() {
        global $DB;

        // Config.
        unset_config('noemailever');

        $this->data = array();

        // Temporary enrolment role.
        $this->data['temprole'] = $this->getDataGenerator()->create_role(array('shortname' => 'test_temporary_role'));
        set_config('roleid', $this->data['temprole'], 'local_temporary_enrolments');

        // Test teacher user.
        $this->data['teacher'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'teacher',
            'email'     => 'teacher@example.com',
            'firstname' => 'Tea',
            'lastname'  => 'Cher',
        ));

        // Test student user.
        $this->data['student'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'student',
            'email'     => 'student@example.com',
            'firstname' => 'Stu',
            'lastname'  => 'Dent',
        ));

        // Test course.
        $this->data['course'] = $this->getDataGenerator()->create_course(array('shortname' => 'testcourse'));
        // Manual enrolment entry.
        $this->data['manualenrol'] = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $this->data['course']->id));
        // Self enrolment entry.
        $this->data['selfenrol'] = $DB->get_record('enrol', array('enrol' => 'self', 'courseid' => $this->data['course']->id));
        // Manual enrolment plugin (will use manual enrolment entry to enrol users in course context).
        $this->data['me_plugin'] = new enrol_manual_plugin();
        // Self enrolment plugin (will use self enrolment entry to enrol users in course context).
        $this->data['se_plugin'] = new enrol_self_plugin();
        // Course context for use in enrolments.
        $this->data['coursecontext'] = \context_course::instance($this->data['course']->id);
        // The student role.
        $this->data['studentrole'] = $DB->get_record('role', array('shortname' => 'student'))->id;

        $this->setUser($this->data['teacher']);
    }

    /**
     * Reset anything that might be left between loops.
     * Most of the tests loop twice, to test with the plugin on and with it off.
     * This ensures (hopefully) that nothing interferes between iterations.
     *
     * @return array of data objects
     */
    public function reset() {
        global $DB;
        $resetsink = $this->redirectEmails();

        // Unenrol any users that are enrolled.
        $roleassignments = $DB->get_records('role_assignments');
        if ($roleassignments) {
            foreach ($roleassignments as $ra) {
                $this->data['me_plugin']->unenrol_user($this->data['manualenrol'], $ra->userid);
                $this->data['se_plugin']->unenrol_user($this->data['selfenrol'], $ra->userid);
            }
        }

        // Rip out any leftover data.
        $DB->delete_records('role_assignments');
        $DB->delete_records('user_enrolments');
        $DB->delete_records('local_temporary_enrolments');

        // Config defaults.
        set_config('onoff', 1, 'local_temporary_enrolments');
        set_config('studentinit_onoff', 1, 'local_temporary_enrolments');
        set_config('teacherinit_onoff', 1, 'local_temporary_enrolments');
        set_config('expire_onoff', 1, 'local_temporary_enrolments');
        set_config('remind_onoff', 1, 'local_temporary_enrolments');
        set_config('upgrade_onoff', 1, 'local_temporary_enrolments');
        set_config('length', 1209600, 'local_temporary_enrolments');
        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        $update = new stdClass();
        $update->id = $task->id;
        $update->minute = '0';
        $update->hour = '8';
        $update->day = '*/2';
        $update->month = '*';
        $update->dayofweek = '*';
        $DB->update_record('task_scheduled', $update);

        $resetsink->close();
    }

    /**
     * Check email contents
     *
     * @return void
     */
    public function emailHas($email, $body, $subject, $to) {
        $this->assertContains('Auto-Submitted: auto-generated', $email->header);
        $this->assertContains('noreply@', $email->from);
        foreach ($body as $s) {
            $this->assertContains($s, preg_replace('/\s*\n\s*/', ' ', $email->body));
        }
        $this->assertContains($subject, $email->subject);
        $this->assertContains($to, $email->to);
    }

    /**
     * Does initialize() insert an expiration entry into our custom table?
     * Does it NOT do so when the plugin is off?
     *
     * @return void
     */
    public function test_init_insert_expiration_entry() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => 123,
                    'component' => 'manual',
                ),
            ));

            $customtable = $DB->get_records('local_temporary_enrolments');

            // Table should be empty.
            $this->assertEquals(0, count($customtable));

            // Assign temp role.
            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();

            $customtable = $DB->get_records('local_temporary_enrolments');
            // Plugin off...
            if ($i == 0) {
                // Should still be empty.
                $this->assertEquals(0, count($customtable));
            // Plugin on...
            } else {
                // Now should have 1 entry.
                $this->assertEquals(1, count($customtable));
                // Testing stored values.
                $expiration = $customtable[array_keys($customtable)[0]];
                $this->assertEquals($event->other['id'], $expiration->roleassignid);
                $this->assertEquals(0, $expiration->upgraded);
            }
            $this->reset();
        }
    }

    /**
     * Does selecting a marker role with existing assignments corrrectly switch out custom table entries?
     * And... a bunch of other stuff
     *
     * @return void
     */
    public function test_existing_assignments_behavior() {
        $this->resetAfterTest();
        global $DB;

        set_config('onoff', 1, 'local_temporary_enrolments');

        $students = array();

        $students['Hermione'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'hermione',
            'email'     => 'hermione@hogwarts.owl',
            'firstname' => 'Hermione',
            'lastname'  => 'Granger',
        ));
        $students['Harry'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'daboywholived',
            'email'     => 'hpindahouse@hogwarts.owl',
            'firstname' => 'Harry',
            'lastname'  => 'Potter',
        ));
        $students['Ron'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'ronronron',
            'email'     => 'roonilwazlib@hogwarts.owl',
            'firstname' => 'Ronald',
            'lastname'  => 'Weasley',
        ));
        $students['Luna'] = $this->getDataGenerator()->create_user(array(
            'username'  => 'luna',
            'email'     => 'crumplehornedluna@hogwarts.owl',
            'firstname' => 'Luna',
            'lastname'  => 'Lovegood',
        ));

        $testrole1 = $this->data['temprole'];
        $testrole2 = $this->getDataGenerator()->create_role(array('shortname' => 'test_temporary_role2'));

        // Enrol half as temp role 1, half as temp role 2.
        $sink = $this->redirectEmails();
        $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $students['Harry']->id, $testrole1);
        $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $students['Hermione']->id, $testrole1);
        $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $students['Ron']->id, $testrole2);
        $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $students['Luna']->id, $testrole2);
        $sink->close();

        // Right now temp role is test_role_1, right?
        $currententries = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(2, count($currententries));
        $currententries = $DB->get_records('local_temporary_enrolments', array('roleid' => $testrole1));
        $this->assertEquals(2, count($currententries));

        // And if we switch up the config and run handle_existing_assignments...
        set_config('roleid', $testrole2, 'local_temporary_enrolments');
        // Temp role is now test_role2.
        $sink = $this->redirectEmails();
        handle_existing_assignments();
        $sink->close();

        $currententries = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(2, count($currententries));
        $currententries = $DB->get_records('local_temporary_enrolments', array('roleid' => $testrole2));
        $this->assertEquals(2, count($currententries));
        // ... it correctly grabs new role assignments, yay!

        // What about emails?
        set_config('roleid', $testrole1, 'local_temporary_enrolments');

        $sink = $this->redirectEmails();
        handle_existing_assignments();
        $sink->close();
        $results = $sink->get_messages();

        $this->assertEquals(2, count($results));

        // Get whichever email was for Harry so we know what to check for.
        $check = array_filter($results, function($email) {
            return strpos($email->body, 'Harry') !== false;
        });

        $body = array('Dear Harry', 'temporary access to the Moodle site for '.$this->data['course']->fullname);
        $subject = 'Temporary enrolment granted for '.$this->data['course']->fullname;
        $this->emailHas(reset($check), $body, $subject, 'hpindahouse@hogwarts.owl');

        // And if the email option is turned off?
        set_config('existingassignments_email', 0, 'local_temporary_enrolments');
        set_config('roleid', $testrole2, 'local_temporary_enrolments');

        $sink = $this->redirectEmails();
        handle_existing_assignments();
        $sink->close();
        $results = $sink->get_messages();

        $this->assertEquals(count($results), 0);

        // Start time: at creation.
        set_config('existingassignments_start', 0, 'local_temporary_enrolments');
        set_config('roleid', $testrole1, 'local_temporary_enrolments');

        $sink = $this->redirectEmails();
        handle_existing_assignments();
        $sink->close();

        $currententries = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(2, count($currententries));
        foreach ($currententries as $entry) {
            $assignment = $DB->get_record('role_assignments', array('id' => $entry->roleassignid));
            $this->assertEquals($assignment->timemodified, $entry->timestart);
        }

        // Start time: now.
        sleep(10); // To ensure a time gap between role assignment and this bit of the test.
        set_config('existingassignments_start', 1, 'local_temporary_enrolments');
        set_config('roleid', $testrole2, 'local_temporary_enrolments');

        $sink = $this->redirectEmails();
        handle_existing_assignments();
        $sink->close();

        $currententries = $DB->get_records('local_temporary_enrolments');
        $this->assertEquals(2, count($currententries));
        $now = time();
        foreach ($currententries as $entry) {
            $assignment = $DB->get_record('role_assignments', array('id' => $entry->roleassignid));
            $this->assertNotEquals($assignment->timemodified, $entry->timestart);
            $timediff = $entry->timestart - $now;
            $this->assertLessThan(5, $timediff); // Will probably be 0, but give it a bit of wiggle room just in case.
        }
    }

    /**
     * Does initialize() automatically remove temporary enrolments if there is already another role?
     * Does it NOT do that when the plugin is off?
     *
     * @return void
     */
    public function test_init_remove_redundant_temp_enrol() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            // Self enrol student.
            $sink = $this->redirectEmails();
            $this->data['se_plugin']->enrol_user($this->data['selfenrol'], $this->data['student']->id, $this->data['studentrole']);
            $sink->close();

            $roles = $DB->get_records('role_assignments');
            $this->assertEquals(1, count($roles));

            // When we try to manually enrol same user as temp, temp role should be removed (if plugin on).
            $sink = $this->redirectEmails();
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();

            $roles = $DB->get_records('role_assignments');
            $expected = $i == 1 ? 1 : 2;
            $this->assertEquals($expected, count($roles));

            $this->reset();
        }
    }

    /**
     * Does initialize() correctly email student and teacher?
     * Does it correctly NOT do so when the plugin is off?
     * How about when one or the other of the emails is turned off?
     *
     * @return void
     */
    public function test_init_emails() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => 123,
                    'component' => 'manual',
                ),
            ));

            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();
            $results = $sink->get_messages();

            $expected = $i == 1 ? 2 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Stu', 'temporary access to the Moodle site for '.$this->data['course']->fullname);
                $this->emailHas($results[0], $body, 'Temporary enrolment granted for '.$this->data['course']->fullname, 'student@');
                $body = array('Dear Tea', 'You have granted Stu Dent temporary access to '.$this->data['course']->fullname);
                $subject = 'Temporary enrolment granted to Stu Dent for '.$this->data['course']->fullname;
                $this->emailHas($results[1], $body, $subject, 'teacher@');
            }

            // Studentinit turned off.
            set_config('studentinit_onoff', 0, 'local_temporary_enrolments');
            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => 124,
                    'component' => 'manual',
                ),
            ));

            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();
            $results = $sink->get_messages();

            $expected = $i == 1 ? 1 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Tea', 'You have granted Stu Dent temporary access to '.$this->data['course']->fullname);
                $subject = 'Temporary enrolment granted to Stu Dent for '.$this->data['course']->fullname;
                $this->emailHas($results[0], $body, $subject, 'teacher@');
            }

            // Teacherinit turned off.
            set_config('teacherinit_onoff', 0, 'local_temporary_enrolments');
            set_config('studentinit_onoff', 1, 'local_temporary_enrolments');
            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => 125,
                    'component' => 'manual',
                ),
            ));

            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();
            $results = $sink->get_messages();

            $expected = $i == 1 ? 1 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Stu', 'temporary access to the Moodle site for '.$this->data['course']->fullname);
                $this->emailHas($results[0], $body, 'Temporary enrolment granted for '.$this->data['course']->fullname, 'student@');
            }

            // Both initial emails off.
            set_config('studentinit_onoff', 0, 'local_temporary_enrolments');
            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
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

            $this->reset();
        }
    }

    /**
     * Does changing the reminder frequency in config update the DB (regardless of plugin on/off)?
     *
     * @return void
     */
    public function test_remind_frequency_update() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
            $this->assertEquals(0, $task->minute);
            $this->assertEquals(8, $task->hour);
            $this->assertEquals('*/2', $task->day);

            set_config('remind_freq', 4, 'local_temporary_enrolments');
            update_remind_freq($task, get_config('local_temporary_enrolments', 'remind_freq'));

            $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
            $this->assertEquals('*/4', $task->day);

            $this->reset();
        }
    }

    /**
     * Does remind_task() correctly send a reminder email?
     * Does it correctly NOT send, when plugin is off?
     *
     * @return void
     */
    public function test_remind_emails() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $sink = $this->redirectEmails();
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();

            $task = new remind_task();

            $sink = $this->redirectEmails();
            $task->execute();
            $sink->close();

            $results = $sink->get_messages();

            $expected = $i == 1 ? 1 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Stu', 'temporary enrolment in '.$this->data['course']->fullname." will expire", "in 14 days");
                $this->emailHas($results[0], $body, 'Temporary enrolment reminder for '.$this->data['course']->fullname, 'student');
            }

            // Remind emails off.
            set_config('remind_onoff', 0, 'local_temporary_enrolments');
            $sink = $this->redirectEmails();
            $task->execute();
            $sink->close();
            $results = $sink->get_messages();
            $this->assertEquals(0, count($results));

            $this->reset();
        }
    }

    /**
     * Does expire() remove the manual enrolment entry (based on situation)?
     * Does it NEVER do so when the plugin is off?
     *
     * @return void
     */
    public function test_expire_remove_enrol() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            $sink = $this->redirectEmails();
            set_config('onoff', $i, 'local_temporary_enrolments');

            // No roles left: remove.
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            role_unassign($this->data['temprole'], $this->data['student']->id, $this->data['coursecontext']->id);
            $userenrols = $DB->get_records('user_enrolments');
            $expected = $i == 1 ? 0 : 1;
            $this->assertEquals($expected, count($userenrols));

            // Roles left, no other enrols: don't remove.
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            role_assign($this->data['studentrole'], $this->data['student']->id, $this->data['coursecontext']->id);
            // Upgrade unassigns temp role here.
            $userenrols = $DB->get_records('user_enrolments');
            $this->assertEquals(1, count($userenrols));

            // Roles left, multiple enrols: remove.
            $this->data['se_plugin']->enrol_user($this->data['selfenrol'], $this->data['student']->id, $this->data['studentrole']);
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            // initialize() will immediately unassign temp role because there is already a role there.
            $userenrols = $DB->get_records('user_enrolments');
            $expected = $i == 1 ? 1 : 2;
            $this->assertEquals($expected, count($userenrols));

            $this->reset();
            $sink->close();
        }
    }

    /**
     * Does expire() remove the expiration entry from our custom table?
     * Does it STILL remove it even if the plugin is off?
     *
     * @return void
     */
    public function test_expire_remove_expiration_entry() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', 1, 'local_temporary_enrolments');

            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);

            $customtable = $DB->get_records('local_temporary_enrolments');
            $this->assertEquals(1, count($customtable));

            set_config('onoff', $i, 'local_temporary_enrolments');
            role_unassign($this->data['temprole'], $this->data['student']->id, $this->data['coursecontext']->id);
            $customtable = $DB->get_records('local_temporary_enrolments');
            $this->assertEquals(0, count($customtable));
        }
    }

    /**
     * Do temporary roles automatically expire as expected?
     * Do they NOT expire when the plugin is off?
     *
     * @return void
     */
    public function test_expire_automatic() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            $sink = $this->redirectEmails();
            set_config('onoff', $i, 'local_temporary_enrolments');
            set_config('length', 5, 'local_temporary_enrolments');

            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $roleassignments = $DB->get_records('role_assignments');
            $this->assertEquals(1, count($roleassignments));

            sleep(5);
            $task = new expire_task();
            $task->execute();

            $roleassignments = $DB->get_records('role_assignments');
            $expected = $i == 1 ? 0 : 1;
            $this->assertEquals($expected, count($roleassignments));

            $this->reset();
            $sink->close();
        }
    }

    /**
     * Is update_length() executed correctly?
     *
     * @return void
     */
    public function test_length_update() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            $sink = $this->redirectEmails();
            set_config('onoff', 1, 'local_temporary_enrolments');

            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);

            $expire = $DB->get_record('local_temporary_enrolments', array());
            $length = get_config('local_temporary_enrolments', 'length');
            $this->assertEquals($length, ($expire->timeend - $expire->timestart));

            set_config('onoff', $i, 'local_temporary_enrolments');

            $newlength = 100;
            update_length($newlength);

            $expire = $DB->get_record('local_temporary_enrolments', array());
            $this->assertEquals($newlength, ($expire->timeend - $expire->timestart));

            $this->reset();
            $sink->close();
        }
    }

    /**
     * Does expire() send expiration email?
     * Does it NOT send when plugin is off?
     *
     * @return void
     */
    public function test_expire_email() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $sink = $this->redirectEmails();
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();

            $roleassign = $DB->get_record(
                'role_assignments',
                array(
                    'roleid' => $this->data['temprole'],
                    'contextid' => $this->data['coursecontext']->id,
                    'userid' => $this->data['student']->id
                )
            );

            $event = \core\event\role_unassigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => $roleassign->id,
                    'component' => 'manual',
                ),
            ));

            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();
            $results = $sink->get_messages();

            $expected = $i == 1 ? 1 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Stu', 'access to ' . $this->data['course']->fullname);
                $subject = 'Temporary enrolment for ' . $this->data['course']->fullname . ' expired';
                $this->emailHas($results[0], $body, $subject, 'student@');
            }

            // Expire email off.
            set_config('expire_onoff', 0, 'local_temporary_enrolments');
            $sink = $this->redirectEmails();
            $this->data['me_plugin']->unenrol_user($this->data['manualenrol'], $this->data['student']->id);
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();
            $roleassign = $DB->get_record(
                'role_assignments',
                array(
                    'roleid' => $this->data['temprole'],
                    'contextid' => $this->data['coursecontext']->id,
                    'userid' => $this->data['student']->id
                )
            );

            $event = \core\event\role_unassigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['temprole'],
                'relateduserid' => $this->data['student']->id,
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

            $this->reset();
        }
    }

    /**
     * Does upgrade() unassign the temporary role?
     * Does it NOT unassign the temporary role if the plugin is off?
     *
     * @return void
     */
    public function test_upgrade_remove_role() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            $sink = $this->redirectEmails();
            set_config('onoff', $i, 'local_temporary_enrolments');

            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);

            $roleassignments = $DB->get_records('role_assignments');
            $this->assertEquals(1, count($roleassignments));

            $this->data['se_plugin']->enrol_user($this->data['selfenrol'], $this->data['student']->id, $this->data['studentrole']);

            $roleassignments = $DB->get_records('role_assignments');
            $expected = $i == 1 ? 1 : 2;
            $this->assertEquals($expected, count($roleassignments));

            $sink->close();
            $this->reset();
        }
    }

    /**
     * Does upgrade() correctly send email?
     * Does it NOT send when the plugin is off?
     *
     * @return void
     */
    public function test_upgrade_email() {
        $this->resetAfterTest();
        global $DB;

        for ($i = 0; $i <= 1; $i++) {
            set_config('onoff', $i, 'local_temporary_enrolments');

            $sink = $this->redirectEmails();
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();

            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['studentrole'],
                'relateduserid' => $this->data['student']->id,
                'other' => array(
                    'id' => 123,
                    'component' => 'flatfile',
                ),
            ));

            $sink = $this->redirectEmails();
            $event->trigger();
            $sink->close();
            $results = $sink->get_messages();

            $expected = $i == 1 ? 1 : 0;
            $this->assertEquals($expected, count($results));
            if ($i == 1) {
                $body = array('Dear Stu', 'access to ' . $this->data['course']->fullname);
                $subject = 'Temporary enrolment for ' . $this->data['course']->fullname . ' upgraded!';
                $this->emailHas($results[0], $body, $subject, 'student@');
            }

            // Upgrade email off.
            set_config('upgrade_onoff', 0, 'local_temporary_enrolments');
            $sink = $this->redirectEmails();
            $this->data['me_plugin']->enrol_user($this->data['manualenrol'], $this->data['student']->id, $this->data['temprole']);
            $sink->close();
            $event = \core\event\role_assigned::create(array(
                'context' => $this->data['coursecontext'],
                'objectid' => $this->data['studentrole'],
                'relateduserid' => $this->data['student']->id,
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

            $this->reset();
        }
    }
}
