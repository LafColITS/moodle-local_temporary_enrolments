@hampshire @local @local_temporary_enrolments
Feature: Temporary Enrolments
  In order to test temporary enrolment
  As an admin
  I need to make a test course

  @javascript
  Scenario: Testing temporary enrolments plugin upgrade functionality
    And the following "courses" exist:
      | fullname     | shortname   | numsections |
      | Upgrade Test | upgradetest | 44          |
    And the following "users" exist:
      | username    | firstname | lastname |
      | upgradeuser | Upgrade   | User     |
    And the following "roles" exist:
      | name      | shortname |
      | Test Role | test      |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s__local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Upgrade Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "upgradeuser" user as "Test Role"
    And I reload the page
    And I wait until the page is ready
    And I click on "a[title=\"Upgrade User's role assignments\"]" "css_element"
    And I set the field with xpath "//form[@id='participantsform']//input[starts-with(@id, 'form_autocomplete_')]" to "Student"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Student')]" "xpath_element"
    And I click on "//span[@data-editlabel=\"Upgrade User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Test Role" in the "a[title=\"Upgrade User's role assignments\"]" "css_element"

  @javascript
  Scenario: Check that default settings are displayed
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    Then the following fields match these values:
     | s__local_temporary_enrolments_onoff                      | 0     |
     | s__local_temporary_enrolments_existingassignments        | 1     |
     | s__local_temporary_enrolments_existingassignments_start  | 1     |
     | s__local_temporary_enrolments_existingassignments_email  | 1     |
     | s__local_temporary_enrolments_length[v]                  | 2     |
     | s__local_temporary_enrolments_length[u]                  | weeks |
     | s__local_temporary_enrolments_remind_freq                | 2     |
    And "weeks" "option" should be visible
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_studentinit_content" "css_element"
    And I should see "Dear {TEACHER}" in the "#id_s__local_temporary_enrolments_teacherinit_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_remind_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_expire_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_upgrade_content" "css_element"

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
    And the following "roles" exist:
      | name      | shortname |
      | Test Role | test      |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary Enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s__local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    When I am on site homepage
    And I follow "Auto Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "autouser" user as "Student"
    And I reload the page
    And I click on "a[title=\"Otto User's role assignments\"]" "css_element"
    And I set the field with xpath "//form[@id='participantsform']//input[starts-with(@id, 'form_autocomplete_')]" to "Test Role"
    And I click on "//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Test Role')]" "xpath_element"
    And I click on "//span[@data-editlabel=\"Otto User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Test Role" in the "a[title=\"Otto User's role assignments\"]" "css_element"
