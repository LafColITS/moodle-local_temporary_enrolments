@local @local_temporary_enrolments
Feature: Temporary Enrolments
    In order to test temporary enrolment
    As an admin
    I need to make a test course

    @javascript
    Scenario: Testing existing role assignment start time: now
    Given the following "courses" exist:
      | fullname    | shortname   | numsections |
      | Test Course | testcourse  | 44          |
    Given the following "users" exist:
      | username  | firstname | lastname |
      | userone   | One       | User     |
      | usertwo   | Two       | User     |
      | userthree | Three     | User     |
      | userfour  | Four      | User     |
    Given the following "roles" exist:
      | name      | shortname |
      | Role One  | roleone   |
      | Role Two  | roletwo   |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "roleone" from the "s__local_temporary_enrolments_roleid" singleselect
    And I set the field "s__local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s__local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role One"
    And I enrol "usertwo" user as "Role One"
    And I enrol "userthree" user as "Role Two"
    And I enrol "userfour" user as "Role Two"
    And I wait "10" seconds
    And I trigger cron
    And I wait "60" seconds
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "One User" in the "#participantsform" "css_element"
    Then I should not see "Two User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I select "roletwo" from the "s__local_temporary_enrolments_roleid" singleselect
    And I click on "a.nav-link[href='#local_temporary_enrolments_existingassignments']" "css_element"
    And I select "From right now" from the "s__local_temporary_enrolments_existingassignments_start" singleselect
    And I press "Save changes"
    And I trigger cron
    And I wait "60" seconds
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I wait "10" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Three User" in the "#participantsform" "css_element"
    Then I should not see "Four User" in the "#participantsform" "css_element"

    @javascript
    Scenario: Testing existing role assignment start time: at creation
    Given the following "courses" exist:
      | fullname    | shortname   | numsections |
      | Test Course | testcourse  | 44          |
    Given the following "users" exist:
      | username  | firstname | lastname |
      | userone   | One       | User     |
      | usertwo   | Two       | User     |
      | userthree | Three     | User     |
      | userfour  | Four      | User     |
    Given the following "roles" exist:
      | name      | shortname |
      | Role One  | roleone   |
      | Role Two  | roletwo   |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "roleone" from the "s__local_temporary_enrolments_roleid" singleselect
    And I set the field "s__local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s__local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role One"
    And I enrol "usertwo" user as "Role One"
    And I enrol "userthree" user as "Role Two"
    And I enrol "userfour" user as "Role Two"
    And I wait "10" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "One User" in the "#participantsform" "css_element"
    Then I should not see "Two User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I select "roletwo" from the "s__local_temporary_enrolments_roleid" singleselect
    And I click on "a.nav-link[href='#local_temporary_enrolments_existingassignments']" "css_element"
    And I select "From assignment start time" from the "s__local_temporary_enrolments_existingassignments_start" singleselect
    And I press "Save changes"
    And I wait "60" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Three User" in the "#participantsform" "css_element"
    Then I should not see "Four User" in the "#participantsform" "css_element"

    @javascript
    Scenario: Testing existing role assignment base behavior
    Given the following "courses" exist:
        | fullname    | shortname   | numsections |
        | Test Course | testcourse  | 44          |
    Given the following "users" exist:
        | username  | firstname | lastname |
        | userone   | One       | User     |
        | usertwo   | Two       | User     |
        | userthree | Three     | User     |
        | userfour  | Four      | User     |
    Given the following "roles" exist:
        | name      | shortname |
        | Role One  | roleone   |
        | Role Two  | roletwo   |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "roleone" from the "s__local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role One"
    And I enrol "usertwo" user as "Role One"
    And I enrol "userthree" user as "Role Two"
    And I enrol "userfour" user as "Role Two"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I select "roletwo" from the "s__local_temporary_enrolments_roleid" singleselect
    And I set the field "s__local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s__local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I wait "10" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should see "One User" in the "#participantsform" "css_element"
    Then I should see "Two User" in the "#participantsform" "css_element"
    Then I should not see "Three User" in the "#participantsform" "css_element"
    Then I should not see "Four User" in the "#participantsform" "css_element"

    @javascript
    Scenario: Testing temp enrolment length updating
    Given the following "courses" exist:
        | fullname    | shortname   | numsections |
        | Test Course | testcourse  | 44          |
    Given the following "users" exist:
        | username  | firstname | lastname |
        | testuser  | Test      | User     |
    Given the following "roles" exist:
        | name      | shortname |
        | Test Role | test      |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s__local_temporary_enrolments_roleid" singleselect
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
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I set the field "s__local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s__local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I wait "10" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "Test User" in the "#participantsform" "css_element"

    @javascript
    Scenario: Testing automatic unenrolment after time
    Given the following "courses" exist:
        | fullname    | shortname   | numsections |
        | Test Course | testcourse  | 44          |
    Given the following "users" exist:
        | username  | firstname | lastname |
        | testuser  | Test      | User     |
    Given the following "roles" exist:
        | name      | shortname |
        | Test Role | test      |
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    And I click on "s__local_temporary_enrolments_onoff" "checkbox"
    And I select "test" from the "s__local_temporary_enrolments_roleid" singleselect
    And I set the field "s__local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s__local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "testuser" user as "Test Role"
    When I wait "10" seconds
    And I trigger cron
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Test User" in the "#participantsform" "css_element"

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
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
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
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Then the following fields match these values:
       | s__local_temporary_enrolments_onoff                      | 0     |
       | s__local_temporary_enrolments_length[v]                  | 2     |
       | s__local_temporary_enrolments_length[u]                  | weeks |
    And "weeks" "option" should be visible
    Given I click on ".nav-link[href='#local_temporary_enrolments_existingassignments']" "css_element"
    Then the following fields match these values:
       | s__local_temporary_enrolments_existingassignments        | 1     |
       | s__local_temporary_enrolments_existingassignments_start  | 1     |
       | s__local_temporary_enrolments_existingassignments_email  | 1     |
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
       | s__local_temporary_enrolments_remind_freq                | 2     |
       | s__local_temporary_enrolments_studentinit_onoff          | 1     |
       | s__local_temporary_enrolments_teacherinit_onoff          | 1     |
       | s__local_temporary_enrolments_remind_onoff               | 1     |
       | s__local_temporary_enrolments_expire_onoff               | 1     |
       | s__local_temporary_enrolments_upgrade_onoff              | 1     |
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_studentinit_content" "css_element"
    And I should see "Dear {TEACHER}" in the "#id_s__local_temporary_enrolments_teacherinit_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_remind_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_expire_content" "css_element"
    And I should see "Dear {STUDENTFIRST}" in the "#id_s__local_temporary_enrolments_upgrade_content" "css_element"

    @javascript
    Scenario: Testing config validation
    And I log in as "admin"
    When I am on site homepage
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    And I set the field "s__local_temporary_enrolments_remind_freq" to "0"
    And I press "Save changes"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "2.1"
    And I press "Save changes"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "-1"
    And I press "Save changes"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "123"
    And I press "Save changes"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
    Then the field "s__local_temporary_enrolments_remind_freq" matches value "2"
    When I set the field "s__local_temporary_enrolments_remind_freq" to "3"
    And I press "Save changes"
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
    Given I click on ".nav-link[href='#local_temporary_enrolments_email']" "css_element"
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
    And I navigate to "Temporary enrolments" node in "Site administration>Plugins>Local plugins"
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
