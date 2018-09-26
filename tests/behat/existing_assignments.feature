@local @local_temporary_enrolments @local_temporary_enrolments_existing_assignments
Feature: Temporary Enrolments Existing Assignments Handling
  In order to test the existing assignments functionality
  As an admin
  I need to make a test course

  Background:
    Given the following "courses" exist:
      | fullname    | shortname   | numsections |
      | Test Course | testcourse  | 44          |
    Given the following "users" exist:
      | username  | firstname | lastname |
      | userone   | One       | User     |
      | usertwo   | Two       | User     |
      | userthree | Three     | User     |
      | userfour  | Four      | User     |
      | userfive  | Five      | User     |
    Given the following "roles" exist:
      | name   | shortname |
      | Role A | rolea     |
      | Role B | roleb     |
      | Role C | rolec     |

  @javascript
  Scenario: Testing existing role assignments: pre-existing task
    # If we got through a few different role ids before running the task, does it
    # correctly update to the latest one only? That is, do the new adhoc tasks
    # correctly overwrite the old one?
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role A"
    And I enrol "usertwo" user as "Role A"
    And I enrol "userthree" user as "Role B"
    And I enrol "userfour" user as "Role B"
    And I enrol "userfive" user as "Role C"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I select "roleb" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I select "rolec" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "Five User" in the "#participantsform" "css_element"
    Then I should see "One User" in the "#participantsform" "css_element"
    Then I should see "Two User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing existing role assignments: circle back to same id
    # There's no reason this should work differently, but it was a thing that used to be a thing,
    # so we're testing it.
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role A"
    And I enrol "usertwo" user as "Role A"
    And I enrol "userthree" user as "Role B"
    And I enrol "userfour" user as "Role B"
    And I enrol "userfive" user as "Role C"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I select "roleb" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "One User" in the "#participantsform" "css_element"
    Then I should not see "Two User" in the "#participantsform" "css_element"
    Then I should see "Five User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing existing role assignments: on/off
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I click on "a.nav-link[href='#local_temporary_enrolments_existing_assignments']" "css_element"
    And I click on "s_local_temporary_enrolments_existing_assignments" "checkbox"
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role A"
    And I enrol "usertwo" user as "Role A"
    And I enrol "userthree" user as "Role B"
    And I enrol "userfour" user as "Role B"
    And I enrol "userfive" user as "Role C"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I select "roleb" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should see "One User" in the "#participantsform" "css_element"
    Then I should see "Two User" in the "#participantsform" "css_element"
    Then I should see "Five User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "a.nav-link[href='#local_temporary_enrolments_existing_assignments']" "css_element"
    And I click on "s_local_temporary_enrolments_existing_assignments" "checkbox"
    And I press "Save changes"
    And I click on "a.nav-link[href='#local_temporary_enrolments_main']" "css_element"
    And I select "rolec" from the "s_local_temporary_enrolments_roleid" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should see "One User" in the "#participantsform" "css_element"
    Then I should see "Two User" in the "#participantsform" "css_element"
    Then I should not see "Five User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing existing role assignment start time: now
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role A"
    And I enrol "usertwo" user as "Role A"
    And I enrol "userthree" user as "Role B"
    And I enrol "userfour" user as "Role B"
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "One User" in the "#participantsform" "css_element"
    Then I should not see "Two User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I select "roleb" from the "s_local_temporary_enrolments_roleid" singleselect
    And I click on "a.nav-link[href='#local_temporary_enrolments_existing_assignments']" "css_element"
    And I select "From right now" from the "s_local_temporary_enrolments_existing_assignments_start" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Three User" in the "#participantsform" "css_element"
    Then I should not see "Four User" in the "#participantsform" "css_element"

  @javascript
  Scenario: Testing existing role assignment start time: at creation
    When I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I click on "s_local_temporary_enrolments_onoff" "checkbox"
    And I select "rolea" from the "s_local_temporary_enrolments_roleid" singleselect
    And I set the field "s_local_temporary_enrolments_length[v]" to "10"
    And I select "seconds" from the "s_local_temporary_enrolments_length[u]" singleselect
    And I press "Save changes"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    And I enrol "userone" user as "Role A"
    And I enrol "usertwo" user as "Role A"
    And I enrol "userthree" user as "Role B"
    And I enrol "userfour" user as "Role B"
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    When I follow "Participants"
    Then I should not see "One User" in the "#participantsform" "css_element"
    Then I should not see "Two User" in the "#participantsform" "css_element"
    Then I should see "Three User" in the "#participantsform" "css_element"
    Then I should see "Four User" in the "#participantsform" "css_element"
    And I navigate to "Plugins > Local plugins > Temporary enrolments" in site administration
    And I select "roleb" from the "s_local_temporary_enrolments_roleid" singleselect
    And I click on "a.nav-link[href='#local_temporary_enrolments_existing_assignments']" "css_element"
    And I select "From assignment start time" from the "s_local_temporary_enrolments_existing_assignments_start" singleselect
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "10" seconds
    And I run the scheduled task "\local_temporary_enrolments\task\expire_task"
    And I am on site homepage
    And I follow "Test Course"
    And I wait until the page is ready
    And I follow "Participants"
    Then I should not see "Three User" in the "#participantsform" "css_element"
    Then I should not see "Four User" in the "#participantsform" "css_element"