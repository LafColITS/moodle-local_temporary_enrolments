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
 * Privacy implementation for tool_coursedaes.
 *
 * @package   local_temporary_enrolments
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \local_temporary_enrolments\privacy\provider;

class local_temporary_enrolments_privacy_testcase extends \core_privacy\tests\provider_testcase {

    private $role;
    private $users;
    private $courses;
    private $course_contexts;

    /**
     * Make various data objects that all the functions need to use
     *
     * @return array of data objects
     */
    public function setUp() {
        global $DB;

        $this->resetAfterTest(true);

        set_config('onoff', 1, 'local_temporary_enrolments');

        // Temporary enrolment role.
        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'test_temporary_role']);
        $this->role = $DB->get_record('role', ['id' => $roleid]);
        set_config('roleid', $roleid, 'local_temporary_enrolments');

        // Teacher.
        $this->users[0] = $this->getDataGenerator()->create_user([
            'username'  => 'fury',
            'email'     => 'furyn@shield.gov',
            'firstname' => 'Nick',
            'lastname'  => 'Fury',
        ]);

        // Students.
        $this->users[1] = $this->getDataGenerator()->create_user([
            'username'  => 'capmarvel',
            'email'     => 'danversc@shield.gov',
            'firstname' => 'Carol',
            'lastname'  => 'Danvers',
        ]);

        $this->users[2] = $this->getDataGenerator()->create_user([
            'username'  => 'spiderman',
            'email'     => 'parkerp@shield.gov',
            'firstname' => 'Peter',
            'lastname'  => 'Parker',
        ]);

        $this->users[3] = $this->getDataGenerator()->create_user([
            'username'  => 'hulk',
            'email'     => 'bannerb@shield.gov',
            'firstname' => 'Bruce',
            'lastname'  => 'Banner',
        ]);

        $this->users[4] = $this->getDataGenerator()->create_user([
            'username'  => 'therealcap',
            'email'     => 'rogerss@shield.gov',
            'firstname' => 'Steve',
            'lastname'  => 'Rogers',
        ]);

        // Courses.
        $this->courses[1] = $this->getDataGenerator()->create_course(['shortname' => 'course1']);
        $this->courses[2] = $this->getDataGenerator()->create_course(['shortname' => 'course2']);
        $this->courses[3] = $this->getDataGenerator()->create_course(['shortname' => 'course3']);

        // Course contexts.
        $this->course_contexts[1] = \context_course::instance($this->courses[1]->id);
        $this->course_contexts[2] = \context_course::instance($this->courses[2]->id);
        $this->course_contexts[3] = \context_course::instance($this->courses[3]->id);

        // Set user so we have complete data on role assign events.
        $this->setUser($this->users[0]);

        // Enrolments for Course 1 (all):
        $this->enrol($this->users[1], $this->courses[1]);
        $this->enrol($this->users[2], $this->courses[1]);
        $this->enrol($this->users[3], $this->courses[1]);

        // Enrolments for Course 2 (some):
        $this->enrol($this->users[1], $this->courses[2]);
        $this->enrol($this->users[2], $this->courses[2]);
        $this->enrol($this->users[3], $this->courses[2], $DB->get_record('role', ['shortname' => 'student']));

        // Enrolments for Course 3 (none):
        // ...
    }

    /**
     * Custom utility function for enrolling a user in a course.
     *
     * @param User      $user   The user to enrol.
     * @param Course    $course The course in which to enrol the user.
     * @param Role|null $role   The role with which to enrol the user.
     *
     * @return void
     */
    private function enrol($user, $course, $role = null) {
        global $DB;

        // Default to the temp role.
        if (!isset($role)) {
            $role = $this->role;
        }

        // Manual enrolment entry.
        $manualenrol = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $course->id]);

        // Manual enrolment plugin (uses manual enrolment entry to enrol users in course context).
        $meplugin = new enrol_manual_plugin();

        // Enrol the user in the course.
        $meplugin->enrol_user($manualenrol, $user->id, $role->id);
    }

    /**
     * Assert the state of personal data, that is, which users have data in which courses.
     *
     * @param array $state An array of the form $userid => [$courseid, $courseid, ...] where
     * the course ids represent courses in which the user DOES have data, and absent course ids
     * represent courses in which they do not.
     *
     * @return void
     */
    private function assert_exported_state($state) {
        $allcourses = [1,2,3];

        foreach ($state as $user => $courses) {
            $this->tearDown();
            $this->export_all_data_for_user($this->users[$user]->id, 'local_temporary_enrolments');

            // Courses that SHOULD have data.
            foreach ($courses as $course) {
                $message = "Data not found for user $user in course $course";
                $writer = \core_privacy\local\request\writer::with_context($this->course_contexts[$course]);
                $this->assertNotEmpty($writer->get_data([get_string('pluginname', 'local_temporary_enrolments')]), $message);
                $this->assertNotEmpty($writer->get_all_metadata([get_string('pluginname', 'local_temporary_enrolments')]), $message);
            }

            // Courses that should NOT have data.
            $anticourses = array_diff($allcourses, $courses);
            foreach ($anticourses as $anticourse) {
                $message = "Erroneous data found for user $user in course $anticourse";
                $writer = \core_privacy\local\request\writer::with_context($this->course_contexts[$anticourse]);
                $this->assertEmpty($writer->get_data([get_string('pluginname', 'local_temporary_enrolments')]), $message);
                $this->assertEmpty($writer->get_all_metadata([get_string('pluginname', 'local_temporary_enrolments')]), $message);
            }
        }
        $this->tearDown();
    }

    /**
     * Test the 'get_contexts_for_userid' function.
     */
    public function test_get_contexts_for_userid() {
        global $DB;

        // User 1 (Courses 1 and 2):
        $contextlist = $this->get_contexts_for_userid($this->users[1]->id, 'local_temporary_enrolments');
        $contexts    = $contextlist->get_contextids();
        $this->assertCount(2, $contexts);
        $this->assertContains($this->course_contexts[1]->id, $contexts);
        $this->assertContains($this->course_contexts[2]->id, $contexts);

        // User 2 (Courses 1 and 2)::
        $contextlist = $this->get_contexts_for_userid($this->users[2]->id, 'local_temporary_enrolments');
        $contexts    = $contextlist->get_contextids();
        $this->assertCount(2, $contexts);
        $this->assertContains($this->course_contexts[1]->id, $contexts);
        $this->assertContains($this->course_contexts[2]->id, $contexts);

        // User 3 (Course 1)::
        $contextlist = $this->get_contexts_for_userid($this->users[3]->id, 'local_temporary_enrolments');
        $contexts    = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $this->assertContains($this->course_contexts[1]->id, $contexts);

        // User 4 (no courses):
        $contextlist = $this->get_contexts_for_userid($this->users[4]->id, 'local_temporary_enrolments');
        $contexts    = $contextlist->get_contextids();
        $this->assertCount(0, $contexts);
    }

    /**
     * Test the 'get_users_in_context' function.
     */
    public function test_get_users_in_context() {
        $provider = new provider();

        // Course 1 (Users 1, 2, and 3):
        $userlist = new \core_privacy\local\request\userlist($this->course_contexts[1], 'local_temporary_enrolments');
        $provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(3, $userids);
        $this->assertContains($this->users[1]->id, $userids);
        $this->assertContains($this->users[2]->id, $userids);
        $this->assertContains($this->users[3]->id, $userids);

        // Course 2  (Users 1 and 2):
        $userlist = new \core_privacy\local\request\userlist($this->course_contexts[2], 'local_temporary_enrolments');
        $provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(2, $userids);
        $this->assertContains($this->users[1]->id, $userids);
        $this->assertContains($this->users[2]->id, $userids);

        // Course 3 (no users):
        $userlist = new \core_privacy\local\request\userlist($this->course_contexts[3], 'local_temporary_enrolments');
        $provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(0, $userids);
    }

    /**
     * Test exporting data for a user who is temporarily enrolled in multiple courses.
     */
    public function test_export_user_data() {
        global $DB;

        // Do users have data in the correct contexts?
        $state = [
            1 => [1, 2], // user => [course, course].
            2 => [1, 2],
            3 => [1],
            4 => [],
        ];
        $this->assert_exported_state($state);

        // And let's spot check some data as well.
        $this->export_all_data_for_user($this->users[1]->id, 'local_temporary_enrolments');
        $writer = \core_privacy\local\request\writer::with_context($this->course_contexts[1]);
        $data = $writer->get_data([get_string('pluginname', 'local_temporary_enrolments')]);

        $roleassignment = $DB->get_record('role_assignments', [
            'userid'    => $this->users[1]->id,
            'contextid' => $this->course_contexts[1]->id,
        ]);

        $this->assertObjectHasAttribute('temporary_enrolment',  $data);
        $this->assertObjectHasAttribute('roleassignid',         $data->temporary_enrolment);
        $this->assertObjectHasAttribute('roleid',               $data->temporary_enrolment);
        $this->assertObjectHasAttribute('rolename',             $data->temporary_enrolment);
        $this->assertObjectHasAttribute('timestart',            $data->temporary_enrolment);
        $this->assertObjectHasAttribute('timeend',              $data->temporary_enrolment);

        $this->assertEquals($roleassignment->id,    $data->temporary_enrolment->roleassignid);
        $this->assertEquals($this->role->id,        $data->temporary_enrolment->roleid);
        $this->assertEquals($this->role->shortname, $data->temporary_enrolment->rolename);

        $this->assertRegExp(
            '/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), \d\d (\w+) \d\d\d\d, \d:\d\d (AM|PM)/',
            $data->temporary_enrolment->timestart
        );
        $this->assertRegExp(
            '/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), \d\d (\w+) \d\d\d\d, \d:\d\d (AM|PM)/',
            $data->temporary_enrolment->timeend
        );
    }

    /**
     * Test deleting all data for a given user in all contexts.
     */
    public function test_delete_user_data_in_all_contexts() {
        // User 1 has regular data...
        $this->assert_exported_state([
            1 => [1, 2], // This one.
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);

        // Delete all data for User 1:
        $contextlist = $this->get_contexts_for_userid($this->users[1]->id, 'local_temporary_enrolments');
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($this->users[1]->id),
            'local_temporary_enrolments',
            $contextlist->get_contextids()
        );
        $classname = $this->get_provider_classname('local_temporary_enrolments');
        $classname::_delete_data_for_user($approvedcontextlist);

        // ...and now User 1 should not have data.
        $this->assert_exported_state([
            1 => [], // This one.
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);
    }

    /**
     * Test deleting all data for a given user in a limited list of contexts.
     */
    public function test_delete_user_data_in_some_contexts() {
        // User 1 has regular data...
        $this->assert_exported_state([
            1 => [1, 2], // This one.
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);

        // Delete data for User 1 in Course 2 only:
        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($this->users[1]->id),
            'local_temporary_enrolments',
            [$this->course_contexts[2]->id]
        );
        $classname = $this->get_provider_classname('local_temporary_enrolments');
        $classname::_delete_data_for_user($approvedcontextlist);

        // ... and now User 1 has no data for Course 2.
        $this->assert_exported_state([
            1 => [1], // This one.
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);
    }

    /**
     * Test deleting all user data for a given context.
     */
    public function test_delete_context_data_for_all_users() {
        // Everyone has regular data...
        $this->assert_exported_state([
            1 => [1, 2],
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);

        // Delete data for all users for Course 2 only.
        $classname = $this->get_provider_classname('local_temporary_enrolments');
        $classname::_delete_data_for_all_users_in_context($this->course_contexts[2]);

        // ...and now all Course 2 data should be gone.
        $this->assert_exported_state([
            1 => [1],
            2 => [1],
            3 => [1],
            4 => [],
        ]);
    }

    /**
     * Test deleting data for context, limited to certain users.
     */
    public function test_delete_context_data_for_some_users() {
        // Everyone has regular data...
        $this->assert_exported_state([
            1 => [1, 2],
            2 => [1, 2],
            3 => [1],
            4 => [],
        ]);

        // Delete data for Course 1 for Users 2 and 3 only.
        $userids = [$this->users[2]->id, $this->users[3]->id];
        $userlist = new \core_privacy\local\request\approved_userlist(
            $this->course_contexts[1],
            'local_temporary_enrolments',
            $userids
        );
        $classname = $this->get_provider_classname('local_temporary_enrolments');
        $classname::delete_data_for_users($userlist);

        // ...and now Course 1 data should be gone for Users 2 and 3.
        $this->assert_exported_state([
            1 => [1, 2],
            2 => [2],
            3 => [],
            4 => [],
        ]);
    }
}