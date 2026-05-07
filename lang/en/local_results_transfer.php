<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Language strings for local_results_transfer.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Results Transfer';
$string['privacy:metadata'] = 'The Results Transfer plugin does not store personal data in Moodle.';
$string['task_transfer'] = 'Transfer prepared results to SRS';

$string['settings_heading_source'] = 'Source database';
$string['settings_heading_fields'] = 'Source tables, status field and procedure parameters';
$string['settings_heading_target'] = 'Target remote procedure';

$string['source_db_type'] = 'Source DB type';
$string['source_db_host'] = 'Source DB host';
$string['source_db_port'] = 'Source DB port';
$string['source_db_name'] = 'Source DB name';
$string['source_db_user'] = 'Source DB user';
$string['source_db_pass'] = 'Source DB password';
$string['source_db_setupsql'] = 'Source DB setup SQL';
$string['source_to_read'] = 'Source table/view to read';
$string['source_to_update'] = 'Source table to update';
$string['source_field_id'] = 'ID field';
$string['source_field_transferred'] = 'Transferred/status field';
$string['source_log_field'] = 'Log reference field';
$string['source_log_field_desc'] = 'Optional source column to include in per-row logs, for example associationId or srs_assessment_element_id. Leave blank to log only the ID.';
$string['procedure_parameter_fields'] = 'Ordered procedure parameter fields';
$string['procedure_parameter_fields_desc'] = 'One source column per line, or comma-separated. The plugin reads these columns from the source row and passes their values to the stored procedure in exactly this order. Do not include the OUT status parameter here.';

$string['transfer_method'] = 'Transfer method';
$string['remote_procedure_db_type'] = 'Remote procedure DB type';
$string['remote_procedure_db_host'] = 'Remote procedure DB host';
$string['remote_procedure_db_port'] = 'Remote procedure DB port';
$string['remote_procedure_db_name'] = 'Remote procedure DB name';
$string['remote_procedure_db_user'] = 'Remote procedure DB user';
$string['remote_procedure_db_pass'] = 'Remote procedure DB password';
$string['remote_procedure_db_setupsql'] = 'Remote procedure DB setup SQL';
$string['remote_procedure'] = 'Remote procedure name';
