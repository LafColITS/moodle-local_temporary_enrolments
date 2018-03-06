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

    // Create role if needed.
    $onoff = $DB->get_record('config', array('name' => 'local_temporary_enrolments_onoff'));
    $usebuiltinrole = $DB->get_record('config', array('name' => 'local_temporary_enrolments_usebuiltinrole'));
    if ($onoff && $usebuiltinrole) {
        if ($onoff->value && $usebuiltinrole->value) {
            if (!builtin_role_exists()) {
                create_builtin_role();
            }
        }
    }

    // Force role to be builtin role if that option is enabled.
    if ($usebuiltinrole) {
      if ($usebuiltinrole->value) {
        $record = $DB->get_record('config', array('name' => 'local_temporary_enrolments_roleid'));
        $update = new stdClass();
        if ($record) {
          $update->id = $record->id;
          $update->value = LOCAL_TEMPORARY_ENROLMENTS_BUILTIN_SHORTNAME;
          $DB->update_record('config', $update);
        }
      }
    }

    // Update reminder email frequency in DB if needed.
    $remindfreq = $DB->get_record('config', array('name' => 'local_temporary_enrolments_remind_freq'));
    if ($remindfreq) { // In case it hasn't been set yet.
        $task = $DB->get_record('task_scheduled', array('classname' => '\local_temporary_enrolments\task\remind_task'));
        if (explode('/', $task->day)[1] != $remindfreq->value) {
            update_remind_freq($task, $remindfreq);
        }
    }

    // Update length of temporary enrolment if needed.
    $lengthcfg = $DB->get_record('config', array('name' => 'local_temporary_enrolments_length'));
    $expire = $DB->get_record('local_temporary_enrolments', array(), '*', IGNORE_MULTIPLE);
    if ($lengthcfg && $expire) {
        $lengthactual = $expire->timeend - $expire->timestart;
        if ($lengthcfg->value != $lengthactual) {
            update_length($lengthcfg->value);
        }
    }

    // Begin the actual settings.
    $settings = new admin_settingpage('local_temporary_enrolments', get_string('pluginname', 'local_temporary_enrolments'));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_onoff',
        get_string('onoff_desc', 'local_temporary_enrolments'),
        get_string('onoff_subdesc', 'local_temporary_enrolments'),
        0));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_usebuiltinrole',
        get_string('usebuiltinrole_desc', 'local_temporary_enrolments'),
        get_string('usebuiltinrole_subdesc', 'local_temporary_enrolments'),
        1));

    $roles = $DB->get_records('role');
    $options = array();
    foreach ($roles as $role) {
      $assignments = $DB->count_records('role_assignments', array('roleid' => $role->id));
      echo "<pre>".$role->shortname.$assignments."</pre>";
      if ($assignments == 0) {
        $options[$role->id] = $role->shortname;
      }
    }

    $settings->add(new admin_setting_configselect('local_temporary_enrolments_roleid',
        get_string('roleid_desc', 'local_temporary_enrolments'),
        get_string('roleid_subdesc', 'local_temporary_enrolments'),
        0,
        $options));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_existingassignments',
        "Treat existing role assignments as temporary?",
        "Whether or not to treat pre-existing assignments of the current Temporary role as actually temporary and bring them under plugin management, or leave them be.",
        0));

    $settings->add(new admin_setting_configselect('local_temporary_enrolments_existingassignments_start',
        "For pre-existing assignments, start temporary duration from assignments start or from current time?",
        "Whether to count pre-existing assignments of the current Temporary role as having started at their initiation times, or as having started now, for expiration and reminder purposes.",
        1,
        array(
          0 => 'From assignment start time',
          1 => 'From right now',
        )));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_existingassignments_email',
        "Send out emails on existing role assignments?",
        "Whether or not to send out initialization emails for role assignments which match the temporary marker role, but existed before the plugin was enabled or before the role settings was changed.",
        0));

    $settings->add(new admin_setting_configduration('local_temporary_enrolments_length',
        get_string('length_desc', 'local_temporary_enrolments'),
        get_string('length_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = 1209600,
        $defaultunit = 604800));

    $settings->add(new admin_setting_configtext('local_temporary_enrolments_remind_freq',
        get_string('remind_freq_desc', 'local_temporary_enrolments'),
        get_string('remind_freq_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = '2',
        $paramtype = "/^0*[1-9]{1,2}$/",
        $size = 1));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_studentinit_onoff',
        get_string('studentinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('studentinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $settings->add(new admin_setting_configtextarea('local_temporary_enrolments_studentinit_content',
        get_string('studentinit_content_desc', 'local_temporary_enrolments'),
        get_string('studentinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('studentinit_content_default', 'local_temporary_enrolments')));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_teacherinit_onoff',
        get_string('teacherinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $settings->add(new admin_setting_configtextarea('local_temporary_enrolments_teacherinit_content',
        get_string('teacherinit_content_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_default', 'local_temporary_enrolments')));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_remind_onoff',
        get_string('remind_onoff_desc', 'local_temporary_enrolments'),
        get_string('remind_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $settings->add(new admin_setting_configtextarea('local_temporary_enrolments_remind_content',
        get_string('remind_content_desc', 'local_temporary_enrolments'),
        get_string('remind_content_subdesc', 'local_temporary_enrolments'),
        get_string('remind_content_default', 'local_temporary_enrolments')));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_expire_onoff',
        get_string('expire_onoff_desc', 'local_temporary_enrolments'),
        get_string('expire_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $settings->add(new admin_setting_configtextarea('local_temporary_enrolments_expire_content',
        get_string('expire_content_desc', 'local_temporary_enrolments'),
        get_string('expire_content_subdesc', 'local_temporary_enrolments'),
        get_string('expire_content_default', 'local_temporary_enrolments')));

    $settings->add(new admin_setting_configcheckbox('local_temporary_enrolments_upgrade_onoff',
        get_string('upgrade_onoff_desc', 'local_temporary_enrolments'),
        get_string('upgrade_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $settings->add(new admin_setting_configtextarea('local_temporary_enrolments_upgrade_content',
        get_string('upgrade_content_desc', 'local_temporary_enrolments'),
        get_string('upgrade_content_subdesc', 'local_temporary_enrolments'),
        get_string('upgrade_content_default', 'local_temporary_enrolments')));

    $ADMIN->add('localplugins', $settings);
}
