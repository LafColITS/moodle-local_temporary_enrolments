# Temporary Enrolments

## Introduction

A Moodle plugin to manage temporary/provisional course site access for wait-listed or unregistered students.

### Enrolments marked as temporary:
- automatically expire after 2 weeks (configurable)
- are auto-removed upon permanent enrolment

### The following emails are sent by the plugin (configurable):
- explanatory email to both teacher and student on enrolment
- reminder email every two days (configurable, to student only)
- explanatory email when the role expires (student only)
- explanatory email if the student is fully enrolled before the temporary role expires ("upgraded", student only)

### What you can change in the settings:
- **On/Off**: While off, no emails are sent, and no automatic expirations take place.
- **Duration**: How long the temporary role lasts before it expires.
- **Reminder email frequency**: How often reminder emails are sent (in days).
- **Email content**: You can edit the content of all the aforementioned emails. Special tags like `{STUDENTFIRST}` or `{TEACHER}` are used to generate personalized email content.

## Setup:

1. Create a role to mark temporary enrolments. For example, you might create a role with shortname "temporary_enrolment" and fullname "Temporarily Enrolled".
2. Give the role whatever permissions you want, and configure it in any other way you please.
3. Install this plugin.
4. On the settings page, select the role you created in step 1 under "Temporary enrolment role".
5. Press `Save changes`.

### If you already have a role:

If you already have a role that marks wait-listed, unregistered, or other students with provisional course access, and you want to use that role as the temporary enrolment marker for the Temporary Enrolments Plugin:

1. Select that role in the settings page under "Temporary enrolment role" after install.
2. Choose your options for the behavior of existing role assignments being brought under management of the Temporary Enrolments Plugin:
    1. Do you __want__ existing role assignments of the chosen, pre-existing temporary marker role to become temporary and under the management of this plugin, or do you want those pre-existing assignments to remain as they were? (New assignments of the temporary role __will__ still be managed by the plugin.)
    2. Do you want initial emails sent out to the users to whom the role was previously assigned?
    3. Do you want the duration of the temporary enrolment to start from the time that pre-existing role assignments were created, or start from now?
3. Press `Save changes`.

## Directory Overview

### classes

#### tasks

- `expire_task.php`: the cron task that deletes expired  `temporary_enrolment`s
- `remind_task.php`: the cron task that sends reminder emails.

#### observers.php

Functions that respond to role assignments and unassignments, and perform plugin functions as necessary.

### db

#### events.php

Maps out events to 'listen' for and the corresponding callbacks (callback functions are in `classes/observers.php`).

#### tasks.php

Details plugin cron tasks to run, and how often. Tasks are contained in `classes/task`.

#### install.xml

Database schema for setting up tables when installed.

### lang/en

#### local_temporary_enrolments.php

All the lang strings for the plugin. [Moodle String API](https://docs.moodle.org/dev/String_API "Moodle String API")

### tests

#### temporary_enrolments_test.php

Tests to run with Moodle's built in. [PHPUnit](https://docs.moodle.org/dev/PHPUnit "PHPUnit") testing engine.

#### behat

- `temporary_enrolments.feature` : a behat feature test. [Behat](http://behat.org/en/latest/ "Behat") is a third-party PHP testing framework
