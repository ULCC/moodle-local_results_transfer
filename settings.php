<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin settings for local_results_transfer.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_results_transfer', get_string('pluginname', 'local_results_transfer'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_results_transfer/source',
        get_string('settings_heading_source', 'local_results_transfer'), ''));

    $settings->add(new admin_setting_configselect('local_results_transfer/source_db_type',
        get_string('source_db_type', 'local_results_transfer'), '', 'mysqli', ['mysqli' => 'mysqli']));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_db_host',
        get_string('source_db_host', 'local_results_transfer'), '', 'localhost', PARAM_HOST));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_db_port',
        get_string('source_db_port', 'local_results_transfer'), '', '3306', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_db_name',
        get_string('source_db_name', 'local_results_transfer'), '', 'mis', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_db_user',
        get_string('source_db_user', 'local_results_transfer'), '', 'mis-ro', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('local_results_transfer/source_db_pass',
        get_string('source_db_pass', 'local_results_transfer'), '', ''));
    $settings->add(new admin_setting_configtextarea('local_results_transfer/source_db_setupsql',
        get_string('source_db_setupsql', 'local_results_transfer'), '', '', PARAM_RAW));

    $settings->add(new admin_setting_heading('local_results_transfer/fields',
        get_string('settings_heading_fields', 'local_results_transfer'), ''));

    $settings->add(new admin_setting_configtext('local_results_transfer/source_to_read',
        get_string('source_to_read', 'local_results_transfer'), '',
        'mis.published_TestComponentAssociationStudentResults', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_to_update',
        get_string('source_to_update', 'local_results_transfer'), '', 'mis.exported_grades', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_field_id',
        get_string('source_field_id', 'local_results_transfer'), '', 'id', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_field_transferred',
        get_string('source_field_transferred', 'local_results_transfer'), '', 'grade_transferred', PARAM_TEXT));
    $settings->add(new admin_setting_configselect('local_results_transfer/source_transfer_field_type',
        get_string('source_transfer_field_type', 'local_results_transfer'),
        get_string('source_transfer_field_type_desc', 'local_results_transfer'), 'datetime',
        ['numeric' => get_string('transfer_field_type_numeric', 'local_results_transfer'),
         'datetime' => get_string('transfer_field_type_datetime', 'local_results_transfer')]));
    $settings->add(new admin_setting_configtext('local_results_transfer/source_log_field',
        get_string('source_log_field', 'local_results_transfer'),
        get_string('source_log_field_desc', 'local_results_transfer'), 'associationId', PARAM_TEXT));

    $defaultparams = implode("\n", [
        'associationId',
        'role',
        'state',
        'attempt',
        'testComponentOfferingId',
        'personId',
        'resultState',
        'resultPass',
        'resultScore',
        'resultDateTime',
        'otherCodesSPR',
        'otherCodesSubmissionState',
    ]);
    $settings->add(new admin_setting_configtextarea('local_results_transfer/procedure_parameter_fields',
        get_string('procedure_parameter_fields', 'local_results_transfer'),
        get_string('procedure_parameter_fields_desc', 'local_results_transfer'), $defaultparams, PARAM_RAW));

    $settings->add(new admin_setting_heading('local_results_transfer/target',
        get_string('settings_heading_target', 'local_results_transfer'), ''));

    $settings->add(new admin_setting_configselect('local_results_transfer/transfer_method',
        get_string('transfer_method', 'local_results_transfer'), '', 'remote_procedure', ['remote_procedure' => 'remote_procedure']));
    $settings->add(new admin_setting_configselect('local_results_transfer/remote_procedure_db_type',
        get_string('remote_procedure_db_type', 'local_results_transfer'), '', 'sqlsrv', ['sqlsrv' => 'sqlsrv', 'mysqli' => 'mysqli']));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure_db_host',
        get_string('remote_procedure_db_host', 'local_results_transfer'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure_db_port',
        get_string('remote_procedure_db_port', 'local_results_transfer'), '', '1433', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure_db_name',
        get_string('remote_procedure_db_name', 'local_results_transfer'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure_db_user',
        get_string('remote_procedure_db_user', 'local_results_transfer'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('local_results_transfer/remote_procedure_db_pass',
        get_string('remote_procedure_db_pass', 'local_results_transfer'), '', ''));
    $settings->add(new admin_setting_configtextarea('local_results_transfer/remote_procedure_db_setupsql',
        get_string('remote_procedure_db_setupsql', 'local_results_transfer'), '', '', PARAM_RAW));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure',
        get_string('remote_procedure', 'local_results_transfer'), '', 'insertTestComponentOfferingAssociationStudentResult', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_results_transfer/remote_procedure_success_value',
        get_string('remote_procedure_success_value', 'local_results_transfer'),
        get_string('remote_procedure_success_value_desc', 'local_results_transfer'), 'SUCCESS', PARAM_TEXT));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_results_transfer_mapping',
        get_string('mappingpage', 'local_results_transfer'),
        new moodle_url('/local/results_transfer/mapping.php'),
        'moodle/site:config'
    ));
}
