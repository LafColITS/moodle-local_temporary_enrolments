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
        get_string('settings:onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:onoff:subdesc', 'local_temporary_enrolments'),
        0));

    // Temporary marker role.
    $options = $DB->get_records_menu('role', null, '', 'id,shortname');
    $options = array_filter($options, function($v, $k) {
        global $DB;
        $contextlevels = $DB->get_records_menu('role_context_levels', array('roleid' => $k), '', 'id,contextlevel');
        return in_array(CONTEXT_COURSE, array_values($contextlevels));
    }, ARRAY_FILTER_USE_BOTH);
    $temp = new admin_setting_configselect('local_temporary_enrolments/roleid',
        get_string('settings:roleid:desc', 'local_temporary_enrolments'),
        get_string('settings:roleid:subdesc', 'local_temporary_enrolments'),
        0,
        $options);
    $temp->set_updatedcallback('handle_existing_assignments');
    $page->add($temp);

    // Duration.
    $temp = new admin_setting_configduration('local_temporary_enrolments/length',
        get_string('settings:length:desc', 'local_temporary_enrolments'),
        get_string('settings:length:subdesc', 'local_temporary_enrolments'),
        $defaultsetting = 1209600,
        $defaultunit = 604800);
    $temp->set_updatedcallback('handle_update_length');
    $page->add($temp);

    $settings->add($page);

    // Email settings.
    $page = new admin_settingpage('local_temporary_enrolments_email', 'Email');

    // Reminder email frequency.
    $temp = new admin_setting_configtext('local_temporary_enrolments/remind_freq',
        get_string('settings:remind_freq:desc', 'local_temporary_enrolments'),
        get_string('settings:remind_freq:subdesc', 'local_temporary_enrolments'),
        $defaultsetting = '2',
        $paramtype = "/^0*[1-9]{1,2}$/",
        $size = 1);
    $temp->set_updatedcallback('handle_update_reminder_freq');
    $page->add($temp);

    // Emails on/off.
    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/studentinit_onoff',
        get_string('settings:studentinit_onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:studentinit_onoff:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/teacherinit_onoff',
        get_string('settings:teacherinit_onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:teacherinit_onoff:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/remind_onoff',
        get_string('settings:remind_onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:remind_onoff:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/expire_onoff',
        get_string('settings:expire_onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:expire_onoff:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/upgrade_onoff',
        get_string('settings:upgrade_onoff:desc', 'local_temporary_enrolments'),
        get_string('settings:upgrade_onoff:subdesc', 'local_temporary_enrolments'),
        1));

    // Email content.
    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/studentinit_content',
        get_string('settings:studentinit_content:desc', 'local_temporary_enrolments'),
        get_string('settings:studentinit_content:subdesc', 'local_temporary_enrolments'),
        get_string('settings:studentinit_content:default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/teacherinit_content',
        get_string('settings:teacherinit_content:desc', 'local_temporary_enrolments'),
        get_string('settings:teacherinit_content:subdesc', 'local_temporary_enrolments'),
        get_string('settings:teacherinit_content:default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/remind_content',
        get_string('settings:remind_content:desc', 'local_temporary_enrolments'),
        get_string('settings:remind_content:subdesc', 'local_temporary_enrolments'),
        get_string('settings:remind_content:default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/expire_content',
        get_string('settings:expire_content:desc', 'local_temporary_enrolments'),
        get_string('settings:expire_content:subdesc', 'local_temporary_enrolments'),
        get_string('settings:expire_content:default', 'local_temporary_enrolments')));

    $page->add(new admin_setting_configtextarea('local_temporary_enrolments/upgrade_content',
        get_string('settings:upgrade_content:desc', 'local_temporary_enrolments'),
        get_string('settings:upgrade_content:subdesc', 'local_temporary_enrolments'),
        get_string('settings:upgrade_content:default', 'local_temporary_enrolments')));

    $settings->add($page);

    // Existing role assignment settings.
    $page = new admin_settingpage('local_temporary_enrolments_existing_assignments', 'Existing Role Assignments');

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/existing_assignments',
        get_string('settings:existing_assignments:desc', 'local_temporary_enrolments'),
        get_string('settings:existing_assignments:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configcheckbox('local_temporary_enrolments/existing_assignments_email',
        get_string('settings:existing_assignments_email:desc', 'local_temporary_enrolments'),
        get_string('settings:existing_assignments_email:subdesc', 'local_temporary_enrolments'),
        1));

    $page->add(new admin_setting_configselect('local_temporary_enrolments/existing_assignments_start',
        get_string('settings:existing_assignments_start:desc', 'local_temporary_enrolments'),
        get_string('settings:existing_assignments_start:subdesc', 'local_temporary_enrolments'),
        1,
        array(
          0 => get_string('settings:existing_assignments_start:from_start', 'local_temporary_enrolments'),
          1 => get_string('settings:existing_assignments_start:from_now', 'local_temporary_enrolments')
        )));

    $settings->add($page);

    $ADMIN->add('localplugins', $settings);
}
