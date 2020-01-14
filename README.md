# Temporary Enrolments
[![Build Status](https://travis-ci.org/LafColITS/moodle-local_temporary_enrolments.svg?branch=master)](https://travis-ci.org/LafColITS/moodle-local_temporary_enrolments)

## Introduction

[RETIRED] This plugin is no longer maintained and there will be no further releases.

**A Moodle plugin to manage temporary/provisional course site access for wait-listed or unregistered students.**

Often a student or other user needs to be given access to a course site -- in order to read the syllabus, complete homework, etc. -- but is not officially enrolled in the course (i.e., through the registrar.) They may be waitlisted, not have turned in their registration form, or be waiting for their registration to process. A potential and easy solution is to allow teachers/professors to enrol users in their Moodle courses. When a student requires access but has not been granted it through the usual process, due to not being registered, they can ask the teacher and the teacher can give them access to the course website by enrolling them in Moodle.

However, this is not the ideal solution. It is inelegant in that it draws no separation in the Moodle context between a registered course member and someone who is merely being granted course website access. The connection between Moodle 'enrolment' and actual, registrar-verified course enrolment becomes tenuous and messy. Even more importantly, this confusion can spread to the site user experience. Students may mistakenly believe that they are offically enrolled in a course because they have Moodle access, when in fact their enrolment has not been processed by the registrar. This can lead to serious problems if the student does not officially register by the add/drop deadline.

This plugin is intended as a solution to the above problem. It provides a way for teachers to enrol students on an enforced temporary basis; i.e., the enrolment is automatically terminated after a period of time. While Moodle's built-in manual enrolment method *does* provide the ability to limit the length of those manual enrolments, it does *not* provide a way to make that length different per user -- meaning that *all* manual enrolments would become temporary, which is not the desired behavior. In addition, this plugin provides automatic informational emails to temporarily enrolled users.

Rather than creating another enrolment method, this plugin utilizes a more lightweight solution -- it provides all of its functionality by keying off of a specific role. For more information on the functionality of the plugin, read on.

### The temporary enrolment marker role:
- tells the plugin that this role assignment is temporary
- can be any role that exists on your site
- is specified in the plugin settings

### Enrolments marked as temporary:
- automatically expire after 2 weeks (configurable)
- are auto-removed upon permanent enrolment (when a non-temporary role is added to the user)

### The following emails are sent by the plugin (configurable):
- explanatory email to both teacher and student (assigner and assignee) on enrolment
- reminder email every two days (configurable frequency, to student only)
- explanatory email when the role expires (student only)
- explanatory email if the student is fully enrolled before the temporary role expires ("upgraded", student only)

### What you can change in the settings:
- **On/Off**: While off, no emails are sent, and no automatic expirations take place.
- **Temporary marker role**: The role which will mark enrolments as temporary. See [Setup](#setup) below for more information.
- **Duration**: How long the temporary role lasts before it expires.
- **Reminder email frequency**: How often reminder emails are sent (in days).
- **Email content**: You can edit the content of all the aforementioned emails. Special tags like `{STUDENTFIRST}` or `{TEACHER}` are used to generate personalized email content. Default content includes all available tags.
- **Existing role assignment behavior**: In the case that the temporary marker role you choose is already assigned to some users, these settings allow you to customize how the plugin handles those existing role assignments. See the [Setup > If you already have a role](#existingroleassignments) section below for more information.

## Basic Setup: <a name="setup"></a>

1. Create a role to mark temporary enrolments. For example, you might create a role with shortname `temporary_enrolment` and fullname "Temporarily Enrolled".
2. Give the role whatever permissions you want, and configure it in any other way you please -- as long as it is assignable in a course context.
3. Install this plugin.
4. On the plugin settings page, select the role you created in Step 1 under `Temporary enrolment marker role` in the `Main` tab.
5. Configure any other settings you wish to change; for example, the duration of temporary enrolments, or the content of emails (look under the `Email` tab).
6. Press `Save changes`.

### If you already have a role: <a name="existingroleassignments"></a>

If you already have a role that marks wait-listed, unregistered, or other students with provisional course access, and you want to use that role as the temporary enrolment marker for the Temporary Enrolments Plugin:

1. Select your role in the settings page under `Temporary enrolment marker role` in the `Main` tab after install.
2. In the `Existing Role Assignments` tab, choose your options for the behavior of existing role assignments being brought under management of the Temporary Enrolments Plugin. For example, let's say you have an existing role called "waitlisted" that you select as the temporary enrolment marker role:
    1. Do you want existing role assignments of "waitlisted" to become temporary and under the management of this plugin, or do you want those pre-existing assignments to remain as they were? (New assignments of "waitlisted" _will_ still be managed by the plugin.)
    2. Do you want initial explanatory emails sent out to users with pre-existing "waitlisted" assignments?
    3. Do you want the remaining duration of the temporary enrolments for existing assignments of "waitlisted" to start from the time that those pre-existing role assignments were created, or to start from now?
3. Press `Save changes`.

## Versions

This plugin is maintained with separate releases and branches for each major Moodle version. Currently there are versions available for 3.3, 3.4, and 3.5. This is the version for __Moodle 3.4__.

Releases are tagged based on the following pattern:
[major].[minor].[patch]-[Moodle major version].[Moodle version specific patch]

For example, v1.5.7-35.1 would be plugin vresion 1.5.7 for Moodle 35, and includes one Moodle 35 specific patch for this plugin version. It would have corresponding versions v1.5.7-34.X and v1.5.7-33.X.

## Acknowledgements

Huge credit to Hampshire College, the Web Services Office there, and specifically Kevin Williarty and Sarah Ryder for their help in the formation of this plugin.

Additional credit to Lafayette College for allowing me to continue working on the plugin, and Charles Fulton for his help in getting this plugin ready to publish.

## Directory Overview

### /

#### `settings.php`

Defines the admin settings page. [Useful doc page on theme settings pages (similar to plugin settings pages).](https://docs.moodle.org/dev/Creating_a_theme_settings_page) Settings page uses tabs -- `theme_boost_admin_settingspage_tabs` is mentioned in the link; for detailed implementation, find a settings page that already uses `theme_boost_admin_settingspage_tabs`. Settings page also uses `$setting->set_updatedcallback` to react to config changes; this method has little documentation but does exactly what it sounds like (sets a function to call when the setting is updated).

#### `lib.php`

Various functions required by the plugin.

#### `version.php`

[See here if you don't know what `version.php` is for.](https://docs.moodle.org/dev/version.php)

### classes

#### tasks
[Moodle Scheduled Tasks API](https://docs.moodle.org/34/en/Scheduled_tasks)

- `existing_assignments_task.php`: an [Adhoc Task](https://docs.moodle.org/dev/Task_API#Adhoc_task_usage) that handles bring existing role assignments under management of this plugin.
- `expire_task.php`: the scheduled task that deletes expired `temporary_enrolment`s.
- `remind_task.php`: the scheduled task that sends reminder emails.

#### observers.php

Functions that respond to role assignments and unassignments, and perform plugin functions as necessary. [Moodle Events API](https://docs.moodle.org/dev/Event_2)

### db

#### `events.php`

Maps out events to 'listen' for and the corresponding callbacks (callback functions are in `classes/observers.php`). [Moodle Events API](https://docs.moodle.org/dev/Event_2)

#### `tasks.php`

Details plugin scheduled tasks to run, and how often. Tasks are contained in `classes/task`. [Moodle Scheduled Tasks API](https://docs.moodle.org/34/en/Scheduled_tasks)

#### `install.xml`

Database schema for setting up tables when installed.

### lang

#### en/`local_temporary_enrolments.php`

All the lang strings for the plugin. [Moodle String API](https://docs.moodle.org/dev/String_API "Moodle String API")

### tests

#### `temporary_enrolments_test.php`

Tests to run with Moodle's built in [PHPUnit](https://docs.moodle.org/dev/PHPUnit "PHPUnit") testing engine.

#### behat/

Contains [Behat](http://behat.org/en/latest/ "Behat") feature files for unit testing.
