# Temporary Enrolments

## Introduction

A Moodle module to manage temporary/provisional course site access for waitlisted or unregistered students.

This plugin creates a new role "Temporarily Enrolled".

### This role:
- automatically expires (unenrolling the student) after 2 weeks (configurable)
- is auto-removed upon permanent enrolment (i.e., when another role is applied)
- sends out an explanatory email to both teacher and student on enrolment
- sends out a reminder email every two days (to student only)
- sends out an email when the role expires (student only)
- sends out an email if the student is fully enrolled before the temporary role expires ("upgraded", student only)

### What you can change in the settings:
- **On/Off**: The first time you turn the plugin on, it will create the Temporarily Enrolled role. While off, no emails are sent, and no automatic expirations take place (although the role is left intact)
- **Duration**: How long the temporary role lasts before it expires
- **Reminder email frequency**: How often reminder emails are sent (in days)
- **Email content**: You can edit the content of all the aforementioned emails. Special tags like `{STUDENTFIRST}` or `{TEACHER}` are used to generate personalized email content

### What you shouldn't change:
- Do not change the shortname/identifier of the `temporary_enrolment` role

## Directory Overview

### classes

#### tasks

- `expire_task.php`: the cron task that deletes expired  `temporary_enrolment`s
- `remind_task.php`: the cron task that sends reminder emails.

#### observers.php

Functions that respond to role assignments and unassignments, and perform module functions as necessary.

### db

#### events.php

Maps out events to 'listen' for and the corresponding callbacks (callback functions are in `classes/observers.php`).

#### tasks.php

Details module cron tasks to run, and how often. Tasks are contained in `classes/task`.

#### install.xml

Database schema for setting up tables when installed.

### lang/en

#### local_temporary_enrolments.php

All the lang strings for the module. [Moodle String API](https://docs.moodle.org/dev/String_API "Moodle String API")

### tests

#### temporary_enrolments_test.php

Tests to run with Moodle's built in. [PHPUnit](https://docs.moodle.org/dev/PHPUnit "PHPUnit") testing engine.

#### behat

- `temporary_enrolments.feature` : a behat feature test. [Behat](http://behat.org/en/latest/ "Behat") is a third-party PHP testing framework
- `behat_local_temporary_enrolments.php` : custom behat step for taking screenshots.
