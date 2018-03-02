@hampshire @local @local_temporary_enrolments
Feature: Temporary Enrolments
  In order to test temporary enrolment
  As an admin
  I need to make a test course

  @javascript
  Scenario: Check that default settings are displayed
    Given the following config values are set as admin:
      | theme | hampshire |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the following fields match these values:
      | s__local_temporary_enrolments_length[v]   | 2     |
      | s__local_temporary_enrolments_length[u]   | weeks |
      | s__local_temporary_enrolments_remind_freq | 2     |
    And "weeks" "option" should be visible
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_studentinit_content" "css_element"
    And I should see "Dear {TEACHER}" in the "#id_s__local_temporary_enrolments_teacherinit_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_remind_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_expire_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_upgrade_content" "css_element"

  @javascript
  Scenario: Testing temporary enrolments plugin upgrade functionality
    Given the following config values are set as admin:
      | theme | hampshire |
    And the following "courses" exist:
      | fullname     | shortname   | format | numsections |
      | Upgrade Test | upgradetest | class  | 44          |
    And the following "users" exist:
      | username    | firstname | lastname |
      | upgradeuser | Upgrade   | User     |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I press "Save changes"
    And I follow "Courses"
    And I follow "Upgrade Test"
    And I navigate to "Enrolled users" node in "Course administration>Users"
    And I enrol "upgradeuser" user as "Temporary enrolment"
    And I reload the page
    And I click on ".assignrolelink" "css_element"
    And I press "Student"
    And I reload the page
    Then I should not see "Temporary enrolment" in the "table.userenrolment" "css_element"
    And I should see "Manual" in the "table.userenrolment" "css_element"

  #  @javascript
  #  Scenario: Testing temporary enrolments plugin automatic role expiration
  #    Given the following config values are set as admin:
  #      | theme         | hampshire        |
  #      | timezone      | America/New_York |
  #      | forcetimezone | America/New_York |
  #    And the following "courses" exist:
  #      | fullname    | shortname  | format | numsections |
  #      | Expire Test | expiretest | class  | 15          |
  #    And the following "users" exist:
  #      | username   | firstname | lastname |
  #      | expireuser | Expire    | User     |
  #    And I log in as "admin"
  #    And I am on site homepage
  #    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
  #    And I set the field "s__local_temporary_enrolments_length[v]" to "3"
  #    And I click on "seconds" "option"
  #    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
  #    And I press "Save changes"
  #    And I follow "Courses"
  #    And I follow "Expire Test"
  #    And I navigate to "Enrolled users" node in "Course administration>Users"
  #    And I enrol "expireuser" user as "Temporary enrolment"
  #    And I reload the page
  #    And I wait "60" seconds
  #    And I am on site homepage
  #    # And I run the scheduled task "core\task\events_cron_task"
  #    # And I run the scheduled task "core\task\events_cron_task"
  #    And I log out
  #    And I log in as "admin"
  #    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
  #    # And I trigger cron
  #    When I am on site homepage
  #    And I follow "Courses"
  #    And I follow "Expire Test"
  #    And I navigate to "Enrolled users" node in "Course administration>Users"
  #    And I reload the page
  #    Then I should not see "Expire User"
  #    Given I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
  #    And I click on "weeks" "option"
  #    And I press "Save changes"
  #    And I follow "Courses"
  #    And I follow "Expire Test"
  #    And I navigate to "Enrolled users" node in "Course administration>Users"
  #    And I enrol "expireuser" user as "Temporary enrolment"
  #    And I wait "5" seconds
  #    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
  #    When I am on site homepage
  #    And I follow "Courses"
  #    And I follow "Expire Test"
  #    When I navigate to "Enrolled users" node in "Course administration>Users"
  #    Then I should see "Expire User"

  @javascript
  Scenario: Testing config validation
    Given the following config values are set as admin:
      | theme | hampshire |
    And I log in as "admin"
    When I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I set the field "s__local_temporary_enrolments_remind_freq" to "0"
    And I press "Save changes"
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "2.1"
    And I press "Save changes"
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "-1"
    And I press "Save changes"
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "123"
    And I press "Save changes"
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "3"
    And I press "Save changes"
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "3"

  @javascript
  Scenario: Testing automatic removal of temporary enrolment if there is already a role
    Given the following config values are set as admin:
      | theme | hampshire |
    And the following "courses" exist:
      | fullname    | shortname  | format | numsections |
      | Auto Test   | autotest   | class  | 15          |
    And the following "users" exist:
      | username   | firstname | lastname |
      | autouser   | Otto      | User     |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I press "Save changes"
    When I am on site homepage
    And I follow "Courses"
    And I follow "Auto Test"
    And I wait until the page is ready
    And I navigate to "Enrolled users" node in "Course administration>Users"
    And I enrol "autouser" user as "Student"
    And I click on ".assignrolelink" "css_element"
    And I press "Temporary enrolment"
    And I reload the page
    Then I should not see "Temporary enrolment" in the ".userenrolment" "css_element"
