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
$string['settings_heading_fields'] = 'Source tables, status field and legacy procedure fields';
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
$string['source_transfer_field_type'] = 'Transferred/status field type';
$string['source_transfer_field_type_desc'] = 'Controls how untransferred rows are detected and how successful rows are stamped. Use datetime for Plymouth grade_transferred fields where 1970-01-01 01:00:00 means not transferred.';
$string['transfer_field_type_numeric'] = 'Numeric / Unix timestamp';
$string['transfer_field_type_datetime'] = 'Datetime / NOW()';
$string['source_log_field'] = 'Log reference field';
$string['source_log_field_desc'] = 'Optional source column to include in per-row logs, for example associationId or srs_assessment_element_id. Leave blank to log only the ID.';
$string['procedure_parameter_fields'] = 'Legacy ordered procedure parameter fields';
$string['procedure_parameter_fields_desc'] = 'Legacy fallback only. Prefer the separate Results Transfer field mapping page. One source column per line, or comma-separated. Do not include the OUT status parameter here.';

$string['transfer_method'] = 'Transfer method';
$string['remote_procedure_db_type'] = 'Remote procedure DB type';
$string['remote_procedure_db_host'] = 'Remote procedure DB host';
$string['remote_procedure_db_port'] = 'Remote procedure DB port';
$string['remote_procedure_db_name'] = 'Remote procedure DB name';
$string['remote_procedure_db_user'] = 'Remote procedure DB user';
$string['remote_procedure_db_pass'] = 'Remote procedure DB password';
$string['remote_procedure_db_setupsql'] = 'Remote procedure DB setup SQL';
$string['remote_procedure'] = 'Remote procedure name';
$string['remote_procedure_success_value'] = 'Remote procedure success value';
$string['remote_procedure_success_value_desc'] = 'The exact value returned by the OUT status parameter that means success, for example SUCCESS. Comparison is case-insensitive.';

$string['mappingpage'] = 'Results Transfer field mapping';
$string['mappingpage_desc'] = 'Configure stored procedure parameters dynamically. The order column controls the order in which values are passed to the stored procedure.';
$string['mappinginstructions'] = 'Add one row per stored procedure parameter. For input parameters, enter the source table/view column name. For output parameters, leave source column blank. The order column controls the stored procedure parameter order.';
$string['mappingsaved'] = 'Field mapping saved.';
$string['sortorder'] = 'Order';
$string['direction'] = 'Direction';
$string['inputparam'] = 'Input';
$string['outputparam'] = 'Output';
$string['parametername'] = 'Stored procedure parameter name';
$string['sourcecolumn'] = 'Source column';
$string['datatype'] = 'Data type';
$string['mappingrownote'] = 'Input rows require a source column. Output rows do not.';
$string['addmoreparameters'] = 'Add more parameters';
$string['sourcecolumnrequired'] = 'Source column is required for input parameters.';
$string['atleastoneinputrequired'] = 'At least one input parameter is required.';
$string['atleastoneoutputrequired'] = 'At least one output parameter is required.';
$string['sortorderrequired'] = 'Order must be greater than zero.';
$string['sortorderduplicate'] = 'Each parameter row must have a unique order.';
