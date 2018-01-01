# Provisional Enrolments

A Moodle module to manage provisional/temporary course site access for waitlisted or unregistered students.

This plugin creates a new role "Provisionally Enrolled".

# This role:
    - automatically expires (unenrolling the student) after 2 weeks (configurable)
    - is auto-removed upon permanent enrollment (i.e., when another role is applied)
    - sends out an explanatory email to both teacher and student on enrollment
    - sends out a reminder email every two days (to student only)
    - sends out an email when the role expires (student only)
    - sends out an email if the student is fully enrolled before the temporary role expires ("upgraded", student only)

# What you can change in the settings:
    - On/Off: The first time you turn the plugin on, it will create the Waitlisted role. While off, no emails are sent, and no automatic expirations take place (although the role is left intact)
    - Duration: How long the temporary role lasts before it expires
    - Reminder email frequency: How often reminder emails are sent (in days)
    - Email content: You can edit the content of all the aforementioned emails. Special tags like {STUDENTFIRST} or {TEACHER} are used to generate personalized email content

# What you shouldn't change:
    - Do not change the shortname/identifier of the "provisionally_enrolled" role
