<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy implementation for tool_coursedaes.
 *
 * @package   local_temporary_enrolments
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_temporary_enrolments\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

/**
 * Privacy provider implementation for local_temporary_enrolments.
 *
 * @package   local_temporary_enrolments
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider {

    use \core_privacy\local\legacy_polyfill;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection A reference to the collection to use to store the metadata.
     *
     * @return collection The updated collection of metadata items.
     */
    public static function _get_metadata(collection $collection) {
        $fields = [
            'roleassignid' => 'privacy:metadata:roleassignid',
            'roleid'       => 'privacy:metadata:roleid',
            'timestart'    => 'privacy:metadata:timestart',
            'timeend'      => 'privacy:metadata:timeend',
        ];

        $collection->add_database_table(
            'local_temporary_enrolments',
            $fields,
            'privacy:metadata:table'
        );

        return $collection;
    }

    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     *
     * @return void
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // If no contexts, bail out.
        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Get contexts in which the user is temporarily enrolled.
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT ctx.id AS contextid,
                       r.shortname AS rolename,
                       lte.*
                  FROM {local_temporary_enrolments} lte
             LEFT JOIN {role_assignments} ra ON ra.id = lte.roleassignid
             LEFT JOIN {role} r ON r.id = ra.roleid
             LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE (ctx.id {$contextsql})
                   AND ra.userid = :userid
        ";

        $params = [
            'userid' => $userid,
        ];
        $params += $contextparams;

        // Export temporary enrolment data.
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $context = \context::instance_by_id($record->contextid);

            $tempenrolment = (object) [
                'roleassignid' => $record->roleassignid,
                'roleid'       => $record->roleid,
                'rolename'     => $record->rolename,
                'timestart'    => transform::datetime($record->timestart),
                'timeend'      => transform::datetime($record->timeend),
            ];

            $data = (object) [
                'temporary_enrolment' => $tempenrolment,
            ];

            $writer = writer::with_context($context);
            $writer->export_data(
                [get_string('pluginname', 'local_temporary_enrolments')],
                $data
            );
            $writer->export_metadata(
                [get_string('pluginname', 'local_temporary_enrolments')],
                'temporary_enrolment',
                "Description:",
                get_string('privacy:metadata:export_description', 'local_temporary_enrolments')
            );
        }
        $records->close();
    }

    /**
     * Find all courses which have temporary enrolments.
     *
     * @return void
     */
    public static function _get_contexts_for_userid($userid) {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {role_assignments} ra ON ra.contextid = c.id
            RIGHT JOIN {local_temporary_enrolments} te ON te.roleassignid = ra.id
                 WHERE ra.userid = :userid
        ";

        $params = [
            'userid' => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     *
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        // If not in course context, bail out.
        if (! $context instanceof \context_course) {
            return;
        }

        $ctxid = $context->id;

        // Find users who have data in this course context.
        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
            RIGHT JOIN {local_temporary_enrolments} lte ON lte.roleassignid = ra.id
                 WHERE ra.contextid = :contextid
        ";

        $params = [
            'contextid' => $ctxid,
        ];

        $results = $DB->get_records_sql_menu($sql, $params);

        $userlist->add_users(array_keys($results));

        return $userlist;
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     *
     * @return void
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        // If no contexts, bail out.
        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        static::delete_for_users_in_contexts($contextlist->get_contextids(), [$userid]);
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     *
     * @return void
     */
    public static function _delete_data_for_all_users_in_context(\context $context) {
        $userlist = new userlist($context, 'local_temporary_enrolments');
        static::get_users_in_context($userlist);

        $userids = $userlist->get_userids();

        static::delete_for_users_in_contexts([$context->id], $userids);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $userids = $userlist->get_userids();

        // If no user ids, bail out.
        if (empty($userids)) {
            return;
        } else {
            static::delete_for_users_in_contexts([$userlist->get_context()->id], $userids);
        }
    }

    /**
     * Delete data for specified users in specified contexts
     *
     * @param context $context The context in which to delete.
     * @param array   $userids List of user ids for which data will be deleted.
     */
    private static function delete_for_users_in_contexts($contextids, $userids) {
        global $DB;

        $ctxsql     = implode(',', $contextids);
        $useridssql = implode(',', $userids);

        $sql = "SELECT lte.id
                  FROM {local_temporary_enrolments} lte
                  JOIN {role_assignments} ra ON ra.id = lte.roleassignid
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE (ra.userid) IN ($useridssql)
                   AND (ctx.id) IN ($ctxsql)
        ";

        // Get all entry ids.
        $ids = $DB->get_records_sql_menu($sql);
        $idssql = implode(',', array_keys($ids));

        $select = "(id) IN ($idssql)";

        $DB->delete_records_select('local_temporary_enrolments', $select);
    }
}