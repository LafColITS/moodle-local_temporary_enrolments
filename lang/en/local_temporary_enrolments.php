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

$string['existingassignments_desc'] = 'Manage existing role assignments?';
$string['existingassignments_subdesc'] = 'If the role selected above is already assigned to some users: Do you want those role assignments to become temporary, and be brought under the management of this plugin?';
$string['existingassignments_email_desc'] = 'Send emails for pre-existing assignments?';
$string['existingassignments_email_subdesc'] = 'If the role selected above is already assigned to some users: Do you want the plugin to send initial explanatory emails for those pre-existing role assignments? Only applies of you checked yes for the option to manage existing role assignments.';
$string['existingassignments_start_desc'] = 'Existing role assignment start time';
$string['existingassignments_start_subdesc'] = 'If the role selected above is already assigned to some users: Do you want the duration of the temporary enrolment for those pre-existing role assignments to start from the creation of the role assignment, or from right now? Only applies of you checked yes for the option to manage existing role assignments.';
$string['expire_content_default'] = '{SUBJECT: Temporary enrolment for {COURSE} expired}

Dear {STUDENTFIRST},

Your temporary access to {COURSE} has expired or been revoked. You will no longer be able to access this course. If you wish to participate in this course, please register for it through Central Records.';
$string['expire_content_desc'] = 'Expiration email';
$string['expire_content_subdesc'] = 'Emailed to student upon expiration of their temporary enrolment (if they have not been fully enrolled)';
$string['expire_onoff_desc'] = 'Turn expiration email on/off';
$string['expire_onoff_subdesc'] = 'Check the box to turn expiration emails on/off. This does not affect automatic unenrolment.';
$string['expire_task'] = 'Check for expired temporary roles and remove them';
$string['length_desc'] = 'Duration of temporary enrolment';
$string['length_subdesc'] = 'How long temporary enrolment of a student will last before expiring';
$string['onoff_desc'] = 'Turn temporary enrolments on';
$string['onoff_subdesc'] = 'Check the box to turn on temporary enrolments (will create Temporary Enrolment role if it does not already exist). WARNING: If this plugin has previously been turned on and you turn it off, the Temporary Enrolment role will still be available. You must remove it manually if you want to completely stop using temporary enrolment.';
$string['pluginname'] = 'Temporary Enrolments';
$string['remind_content_default'] = '{SUBJECT: Temporary enrolment reminder for {COURSE}}

Dear {STUDENTFIRST},

Please be advised that your temporary enrolment in {COURSE} will expire in {TIMELEFT} days. If you wish to continue participating in this course you MUST register for it through TheHub or Central Records.';
$string['remind_content_desc'] = 'Reminder email';
$string['remind_content_subdesc'] = 'Emailed to student every x days to remind them that their enrolment is only temporary';
$string['remind_freq_desc'] = 'Reminder email frequency';
$string['remind_freq_subdesc'] = 'Reminder emails are sent every ____ days';
$string['remind_onoff_desc'] = 'Turn reminder email on/off';
$string['remind_onoff_subdesc'] = 'Check the box to turn reminder emails on or off.';
$string['remind_task'] = 'Send out temporary enrolment reminder emails';
$string['roleid_desc'] = 'Temporary enrolment role';
$string['roleid_subdesc'] = 'The role which indicates that an enrolment is temporary. Only applies if you turn off the option above.';
$string['studentinit_content_default'] = '{SUBJECT: Temporary enrolment granted for {COURSE}}

Dear {STUDENTFIRST},

You have been granted temporary access to the Moodle site for {COURSE}. After you are officially registered for the course, you will receive student access for the remainder of the semester. Temporary access will expire after 14 days. Though faculty can add you to Moodle, they CANNOT register you for the course.

Notes:

Register on TheHub until the add deadline.

    To register for the course with instructor approval between the add deadline and the drop deadline at the beginning of the semester, submit a signed course enrolment form to Central Records. The form is located on the register and drop sections page on your student menu on TheHub and in Central Records.

    Continue to check your schedule on TheHub until all courses you are attending are listed.

    You can refer to the Academic Calendar for registration dates and deadlines.

Five College students taking Hampshire courses should follow the instructions at their home institutions to complete registration.';
$string['studentinit_content_desc'] = 'Student initial email';
$string['studentinit_content_subdesc'] = 'Emailed to student upon being temporarily enrolled';
$string['studentinit_onoff_desc'] = 'Turn student initial email on/off';
$string['studentinit_onoff_subdesc'] = 'Check the box to turn student initial email on/off. This does not affect actual enrolment.';
$string['teacherinit_content_default'] = '{SUBJECT: Temporary enrolment granted to {STUDENTFULL} for {COURSE}}

Dear {TEACHER},

You have granted {STUDENTFULL} temporary access to {COURSE}. Temporary enrolment will expire after 14 days. Though you can add students to Moodle, you CANNOT register them for the course. They may register through TheHub until the add deadline. To register for the course with instructor approval between the add deadline and the drop deadline, students must submit a signed course enrolment form to Central Records if they wish to be enrolled.';
$string['teacherinit_content_desc'] = 'Teacher initial email';
$string['teacherinit_content_subdesc'] = 'Emailed to the teacher who enrolled a student temporarily';
$string['teacherinit_onoff_desc'] = 'Turn teacher initial email on/off';
$string['teacherinit_onoff_subdesc'] = 'Check the box to turn teacher initial email on/off. This does not affect actual enrolment.';
$string['upgrade_content_default'] = '{SUBJECT: Temporary enrolment for {COURSE} upgraded!}

Dear {STUDENTFIRST},

Your temporary access to {COURSE} has been upgraded to full enrolment! You are now registered for this course and have permanent access to the Moodle site.';
$string['upgrade_content_desc'] = 'Upgrade email';
$string['upgrade_content_subdesc'] = 'Emailed to student if they are enrolled fully (upgrading their enrolment to permanent status).';
$string['upgrade_onoff_desc'] = 'Turn upgrade email on/off';
$string['upgrade_onoff_subdesc'] = 'Check the box to turn upgrade email on/off. Does not affect actual enrolment.';
