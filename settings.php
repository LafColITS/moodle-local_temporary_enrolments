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
 * Settings
 *
 * @package    local_temporary_enrolments
 * @copyright  2018 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/local/temporary_enrolments/lib.php');

if ($hassiteconfig) {
    global $DB;

    // Begin the actual settings.
    $pluginname = get_string('pluginname', 'local_temporary_enrolments');
    $settings = new theme_boost_admin_settingspage_tabs('local_temporary_enrolments', $pluginname);

    // Main Settings.
    $page = new admin_settingpage('local_temporary_enrolments_main', 'Main');

    // On/off.
    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/onoff',
        get_string('onoff_desc', 'local_temporary_enrolments'),
        get_string('onoff_subdesc', 'local_temporary_enrolments'),
        0));

    // Temporary marker role.
    $options = $DB->get_records_menu('role', null, '', 'id,shortname');
    $options = array_filter($options, function($v, $k) {
        global $DB;
        $contextlevels = $DB->get_records_menu('role_context_levels', array('roleid' => $k), '', 'id,contextlevel');
        return in_array(CONTEXT_COURSE, array_values($contextlevels));
    }, ARRAY_FILTER_USE_BOTH);
    $temp = new admin_setting_configselect('local_temporary_enrolments/roleid',
        get_string('roleid_desc', 'local_temporary_enrolments'),
        get_string('roleid_subdesc', 'local_temporary_enrolments'),
        0,
        $options);
    $temp->set_updatedcallback('handle_existing_assignments');
    $page->add($temp);

    // Duration.
    $temp = new admin_setting_configduration('local_temporary_enrolments/length',
        get_string('length_desc', 'local_temporary_enrolments'),
        get_string('length_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = 1209600,
        $defaultunit = 604800);
    $temp->set_updatedcallback('handle_update_length');
    $page->add($temp);

    $settings->add($page);

    // Email settings.
    $page = new admin_settingpage('local_temporary_enrolments_email', 'Email');

    // Reminder email frequency.
    $temp = new admin_setting_configtext('local_temporary_enrolments/remind_freq',
        get_string('remind_freq_desc', 'local_temporary_enrolments'),
        get_string('remind_freq_subdesc', 'local_temporary_enrolments'),
        $defaultsetting = '2',
        $paramtype = "/^0*[1-9]{1,2}$/",
        $size = 1);
    $temp->set_updatedcallback('handle_update_reminder_freq');
    $page->add($temp);

    // Emails on/off.
    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/studentinit_onoff',
        get_string('studentinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('studentinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/teacherinit_onoff',
        get_string('teacherinit_onoff_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/remind_onoff',
        get_string('remind_onoff_desc', 'local_temporary_enrolments'),
        get_string('remind_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/expire_onoff',
        get_string('expire_onoff_desc', 'local_temporary_enrolments'),
        get_string('expire_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/upgrade_onoff',
        get_string('upgrade_onoff_desc', 'local_temporary_enrolments'),
        get_string('upgrade_onoff_subdesc', 'local_temporary_enrolments'),
        1));

    // Email content.
    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/studentinit_content',
        get_string('studentinit_content_desc', 'local_temporary_enrolments'),
        get_string('studentinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('studentinit_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/teacherinit_content',
        get_string('teacherinit_content_desc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_subdesc', 'local_temporary_enrolments'),
        get_string('teacherinit_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/remind_content',
        get_string('remind_content_desc', 'local_temporary_enrolments'),
        get_string('remind_content_subdesc', 'local_temporary_enrolments'),
        get_string('remind_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/expire_content',
        get_string('expire_content_desc', 'local_temporary_enrolments'),
        get_string('expire_content_subdesc', 'local_temporary_enrolments'),
        get_string('expire_content_default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/upgrade_content',
        get_string('upgrade_content_desc', 'local_temporary_enrolments'),
        get_string('upgrade_content_subdesc', 'local_temporary_enrolments'),
        get_string('upgrade_content_default', 'local_temporary_enrolments')));

    $settings->add($page);

    // Existing role assignment settings.
    $page = new admin_settingpage('local_temporary_enrolments_existingassignments', 'Existing Role Assignments');

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/existingassignments',
        get_string('existingassignments_desc', 'local_temporary_enrolments'),
        get_string('existingassignments_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/existingassignments_email',
        get_string('existingassignments_email_desc', 'local_temporary_enrolments'),
        get_string('existingassignments_email_subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configselect('local_temporary_enrolments/existingassignments_start',
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
