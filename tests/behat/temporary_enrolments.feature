@local @local_temporary_enrolments
Feature: Temporary Enrolments
  In order to test temporary enrolment
  As an admin
  I need to make a test course

  Background:
    Given the following "courses" exist:
      | fullname    | shortname    |
      | Test Course | testcourse   |
      | Upgrade Test | upgradetest |
      | Auto Test   | autotest     |
    Given the following "users" exist:
      | username    | firstname | lastname |
      | testuser    | Test      | User     |
      | upgradeuser | Upgrade   | User     |
      | autouser    | Otto      | User     |
    Given the following "roles" exist:
      | name      | shortname |
      | Test Role | test      |

  @javascript
  Scenario: Testing temp enrolment length updating
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "testuser" user as "Test Role"
    And I reload the page
    And I wait until the page is ready
    Then I should see "Test User" in the "#participantsform" "css_element"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "Test User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing automatic unenrolment after time
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "testuser" user as "Test Role"
    When I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Test User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing temporary enrolments plugin upgrade functionality
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Upgrade Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "upgradeuser" user as "Test Role"
    And I reload the page
    And I wait until the page is ready
    And I click on "a[title=\"Upgrade User's role assignments\"]" "css_element"
    And I click on "#participantsform .form-autocomplete-downarrow" "css_element"
    And I click on "//form[@id='participantsform']//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Student')]" "xpath_element"
    And I click on "//form[@id='participantsform']//span[@data-editlabel=\"Upgrade User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Test Role" in the "a[title=\"Upgrade User's role assignments\"]" "css_element"

  @javascript
  Scenario: Check that default settings are displayed
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Then the following fields match these values:
      | s_local_temporary_enrolments_onoff                      | 0     |
      | s_local_temporary_enrolments_length[v]                  | 2     |
      | s_local_temporary_enrolments_length[u]                  | weeks |
    And "weeks" "option" should be visible
    Given I click on ".nav-link[href='#local_temporary_enrolments_existing_assignments']" "css_element"
    Then the following fields match these values:
      | s_local_temporary_enrolments_existing_assignments        | 1     |
      | s_local_temporary_enrolments_existing_assignments_start  | 1     |
      | s_local_temporary_enrolments_existing_assignments_email  | 1     |
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
      | s_local_temporary_enrolments_remind_freq                | 2     |
      | s_local_temporary_enrolments_studentinit_onoff          | 1     |
      | s_local_temporary_enrolments_teacherinit_onoff          | 1     |
      | s_local_temporary_enrolments_remind_onoff               | 1     |
      | s_local_temporary_enrolments_expire_onoff               | 1     |
      | s_local_temporary_enrolments_upgrade_onoff              | 1     |
    And I should see "Dear {STUDENTFIRST}" in the "#id_s_local_temporary_enrolments_studentinit_content" "css_element"
    And I should see "Dear {TEACHER}" in the "#id_s_local_temporary_enrolments_teacherinit_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s_local_temporary_enrolments_remind_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s_local_temporary_enrolments_expire_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s_local_temporary_enrolments_upgrade_content" "css_element"

  @javascript
  Scenario: Testing config validation
    And I log in as "admin"
    When I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    And I set the field "s_local_temporary_enrolments_remind_freq" to "0"
    And I press "Save changes"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s_local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s_local_temporary_enrolments_remind_freq" to "2.1"
    And I press "Save changes"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s_local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s_local_temporary_enrolments_remind_freq" to "-1"
    And I press "Save changes"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s_local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s_local_temporary_enrolments_remind_freq" to "123"
    And I press "Save changes"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s_local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s_local_temporary_enrolments_remind_freq" to "3"
    And I press "Save changes"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s_local_temporary_enrolments_remind_freq" matches value "3"

  @javascript
  Scenario: Testing automatic removal of temporary enrolment if there is already a role
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    When I am on site homepage
    And I follow "Auto Test"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "autouser" user as "Student"
    And I reload the page
    And I click on "a[title=\"Otto User's role assignments\"]" "css_element"
    And I click on "#participantsform .form-autocomplete-downarrow" "css_element"
    And I click on "//form[@id='participantsform']//ul[@class='form-autocomplete-suggestions']//li[contains(., 'Test Role')]" "xpath_element"
    And I click on "//form[@id='participantsform']//span[@data-editlabel=\"Otto User's role assignments\"]//a[contains(., i[title='Save changes'])]" "xpath_element"
    And I reload the page
    Then I should not see "Test Role" in the "a[title=\"Otto User's role assignments\"]" "css_element"
