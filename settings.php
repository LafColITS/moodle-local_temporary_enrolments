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

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');

if ($hassiteconfig) {
    global $DB, $CFG;

    // Handle existing role assignments.
    if (get_temp_role()) {
      // Has the temp marker role been changed since last custom table update?
      $current_custom_table_entries =$DB->get_records('local_temporary_enrolments');
      $roleid = get_temp_role()->id;
      if (count($current_custom_table_entries) == 0 || $current_custom_table_entries[array_keys($current_custom_table_entries)[0]]->roleid != $roleid) {
        // Wipe any outdated entries in the custom table.
        $DB->delete_records('local_temporary_enrolments');
        // Add role existingassignments_subdesc
        $add_existing_assignments = $DB->get_record('config', array('name' => 'local_temporary_enrolments_existingassignments'));
        if (gettype($add_existing_assignments) == 'object' && $add_existing_assignments->value) {
          $role_assignments_to_add = $DB->get_records('role_assignments', array('roleid' => $roleid));
          $now = time();
          foreach ($role_assignments_to_add as $assignment) {
            $start = $DB->get_record('config', array('name' => 'local_temporary_enrolments_existingassignments_start'));
            $starttime = $assignment->timemodified; // Default
            if (gettype($start) == 'object' && $start->value) {
              $starttime = $now;
            }
            add_to_custom_table($assignment->id, $assignment->roleid, $starttime);
            $send_email = $DB->get_record('config', array('name' => 'local_temporary_enrolments_existingassignments_email'));
            if (gettype($send_email) == 'object' && $send_email->value) {
              $assignerid = 1;
              $assigneeid = $assignment->userid;
              $context = $DB->get_record('context', array('id' => $assignment->contextid));
              $courseid = $context->instanceid;
              $ra_id = $assignment->id;
              $which = 'studentinit';
              send_temporary_enrolments_email($assignerid, $assigneeid, $courseid, $ra_id, $which);
            }
          }
        }
      }
    }

    // Begin the actual settings.
    $settings = new theme_boost_admin_settingspage_tabs('local_temporary_enrolments', get_string('pluginname', 'local_temporary_enrolments'));

    // Main Settings
    $page = new admin_settingpage('local_temporary_enrolments_main', 'Main');

    // On/off
    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_onoff',
        get_string('onoff_desc', 'local_temporary_enrolments'),
        get_string('onoff_subdesc', 'local_temporary_enrolments'),
        0));

    // Temporary marker role
    $options = $DB->get_records_menu('role', null, '', 'id,shortname');
    $options = array_filter($options, function($v, $k) {
      global $DB;
      $contextlevels = $DB->get_records_menu('role_context_levels', array('roleid' => $k), '', 'id,contextlevel');
      return in_array(CONTEXT_COURSE, array_values($contextlevels));
    }, ARRAY_FILTER_USE_BOTH);
    $page->add(new admin_setting_configselect('local_temporary_enrolments_roleid',
        get_string('roleid_desc', 'local_temporary_enrolments'),
        get_string('roleid_subdesc', 'local_temporary_enrolments'),
        0,
        $options));

    // Duration
    $temp = new admin_setting_configduration('local_temporary_enrolments_length',
        get_string('length_desc', 'local_temporary_enrolments'),
        get_string('length_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = 1209600,
        $defaultunit = 604800);
    $temp->set_updatedcallback(function(){
      global $DB;
      update_length($DB->get_record('config', array('name' => 'local_temporary_enrolments_length'))->value);
    });
    $page->add($temp);

    $settings->add($page);

    // Email settings
    $page = new admin_settingpage('local_temporary_enrolments_email', 'Email');

    // Reminder email frequency
    $temp = new admin_setting_configtext('local_temporary_enrolments_remind_freq',
        get_string('remind_freq_desc', 'local_temporary_enrolments'),
        get_string('remind_freq_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = '2',
        $paramtype = "/^0*[1-9]{1,2}$/",
        $size = 1);
    $temp->set_updatedcallback(function() {
      global $DB;
      $remindfreq = $DB->get_record('config', array('name' => 'local_temporary_enrolments_remind_freq'));
      $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
      update_remind_freq($task, $remindfreq);
    });
    $page->add($temp);

    // Emails on/off
    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_studentinit_onoff',
        get_string('studentinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('studentinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_teacherinit_onoff',
        get_string('teacherinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_remind_onoff',
        get_string('remind_onoff_desc', 'local_temporary_enrolments'),
        get_string('remind_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_expire_onoff',
        get_string('expire_onoff_desc', 'local_temporary_enrolments'),
        get_string('expire_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_upgrade_onoff',
        get_string('upgrade_onoff_desc', 'local_temporary_enrolments'),
        get_string('upgrade_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    // Email content
    $page->add(new admin_setting_configtextarea('local_temporary_enrolments_studentinit_content',
        get_string('studentinit_content_desc', 'local_temporary_enrolments'),
        get_string('studentinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('studentinit_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments_teacherinit_content',
        get_string('teacherinit_content_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments_remind_content',
        get_string('remind_content_desc', 'local_temporary_enrolments'),
        get_string('remind_content_subdesc', 'local_temporary_enrolments'),
        get_string('remind_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments_expire_content',
        get_string('expire_content_desc', 'local_temporary_enrolments'),
        get_string('expire_content_subdesc', 'local_temporary_enrolments'),
        get_string('expire_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments_upgrade_content',
        get_string('upgrade_content_desc', 'local_temporary_enrolments'),
        get_string('upgrade_content_subdesc', 'local_temporary_enrolments'),
        get_string('upgrade_content_default', 'local_temporary_enrolments')));

    $settings->add($page);

    // Existing role assignment settings
    $page = new admin_settingpage('local_temporary_enrolments_existingassignments', 'Existing Role Assignments');

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_existingassignments',
        get_string('existingassignments_desc', 'local_temporary_enrolments'),
        get_string('existingassignments_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments_existingassignments_email',
        get_string('existingassignments_email_desc', 'local_temporary_enrolments'),
        get_string('existingassignments_email_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configselect('local_temporary_enrolments_existingassignments_start',
        get_string('existingassignments_start_desc', 'local_temporary_enrolments'),
        get_string('existingassignments_start_subdesc', 'local_temporary_enrolments'),
        1,
        array(
          0 => 'From assignment start time',
          1 => 'From right now',
        )));

    $settings->add($page);

    $ADMIN->add('localplugins', $settings);
}
