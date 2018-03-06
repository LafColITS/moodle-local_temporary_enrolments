@hampshire @local @local_temporary_enrolments
Feature: Temporary Enrolments
  In order to test temporary enrolment
  As an admin
  I need to make a test course

  @javascript
  Scenario: Check that default settings are displayed
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the following fields match these values:
     | s__local_temporary_enrolments_onoff           | 0     |
     | s__local_temporary_enrolments_usebuiltinrole  | 1     |
     | s__local_temporary_enrolments_length[v]       | 2     |
     | s__local_temporary_enrolments_length[u]       | weeks |
     | s__local_temporary_enrolments_remind_freq     | 2     |
    And "weeks" "option" should be visible
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_studentinit_content" "css_element"
    And I should see "Dear {TEACHER}" in the "#id_s__local_temporary_enrolments_teacherinit_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_remind_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_expire_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_upgrade_content" "css_element"

  @javascript
  Scenario: Check that the built in role is created if the plugin is on and the usebuiltinrole option is on
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And the following fields match these values:
      | s__local_temporary_enrolments_onoff           | 0   |
      | s__local_temporary_enrolments_usebuiltinrole  | 1   |
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I press "Save changes"
    Then the following fields match these values:
      | s__local_temporary_enrolments_onoff           | 1   |
      | s__local_temporary_enrolments_usebuiltinrole  | 1   |
    When I navigate to "Define roles" node in "Site administration>Users>Permissions"
    Then I should see "Temporarily Enrolled"

  @javascript
  Scenario: Check that the built in role is not created if the usebuiltinrole option is off
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And the following fields match these values:
      | s__local_temporary_enrolments_onoff           | 0   |
      | s__local_temporary_enrolments_usebuiltinrole  | 1   |
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I click on "s__local_temporary_enrolments_usebuiltinrole" "checkbox"
    And I press "Save changes"
    Then the following fields match these values:
      | s__local_temporary_enrolments_onoff           | 1   |
      | s__local_temporary_enrolments_usebuiltinrole  | 0   |
    When I navigate to "Define roles" node in "Site administration>Users>Permissions"
    Then I should not see "Temporarily Enrolled"

  @javascript
  Scenario: Check that the built in role is not created if the plugin is not turned on
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And the following fields match these values:
      | s__local_temporary_enrolments_onoff           | 0   |
      | s__local_temporary_enrolments_usebuiltinrole  | 1   |
    And I press "Save changes"
    When I navigate to "Define roles" node in "Site administration>Users>Permissions"
    Then I should not see "Temporarily Enrolled"

  @javascript
  Scenario: Testing temporary enrolments plugin upgrade functionality
    And the following "courses" exist:
      | fullname     | shortname   | numsections |
      | Upgrade Test | upgradetest | 44          |
    And the following "users" exist:
      | username    | firstname | lastname |
      | upgradeuser | Upgrade   | User     |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I press "Save changes"
    And I follow "Site home"
    And I follow "Upgrade Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "upgradeuser" user as "Temporarily Enrolled"
    And I reload the page
    And I wait until the page is ready
    And I click on "a[title=\"Upgrade User's role assignments\"]" "css_element"
    And I set the field with xpath "//form[@id='participantsform']//input[starts-with(@id, 'form_autocomplete_')]" to "Student"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Student')]" "xpath_element"
    And I click on "//span[@data-editlabel=\"Upgrade User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Temporarily Enrolled" in the "a[title=\"Upgrade User's role assignments\"]" "css_element"

  @javascript
  Scenario: Testing config validation
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
    And the following "courses" exist:
      | fullname    | shortname  | numsections |
      | Auto Test   | autotest   | 15          |
    And the following "users" exist:
      | username   | firstname | lastname |
      | autouser   | Otto      | User     |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I press "Save changes"
    When I am on site homepage
    And I follow "Site home"
    And I follow "Auto Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "autouser" user as "Student"
    And I reload the page
    And I click on "a[title=\"Otto User's role assignments\"]" "css_element"
    And I set the field with xpath "//form[@id='participantsform']//input[starts-with(@id, 'form_autocomplete_')]" to "Temporarily Enrolled"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Temporarily Enrolled')]" "xpath_element"
    And I click on "//span[@data-editlabel=\"Otto User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Temporarily Enrolled" in the "a[title=\"Otto User's role assignments\"]" "css_element"
