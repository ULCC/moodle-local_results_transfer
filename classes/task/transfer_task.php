<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\task;

use local_results_transfer\driver\driver_factory;

/**
 * Scheduled task that transfers prepared results to the target SRS procedure.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transfer_task extends \core\task\scheduled_task {
    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_transfer', 'local_results_transfer');
    }

    /**
     * Execute task.
     */
    public function execute(): void {
        $cfg = get_config('local_results_transfer');
        $this->validate_config($cfg);

        mtrace('Results transfer started: ' . userdate(time(), '%Y-%m-%d %H:%M:%S'));

        $source = null;
        $target = null;
        $success = 0;
        $failed = 0;
        $mapping = $this->configured_parameter_mapping($cfg);
        $outparam = $this->configured_output_parameter($mapping);
        $successvalue = trim((string)($cfg->remote_procedure_success_value ?? 'SUCCESS'));

        try {
            $source = driver_factory::create((string)$cfg->source_db_type);
            $target = driver_factory::create((string)$cfg->remote_procedure_db_type);

            try {
                $source->connect($this->source_config($cfg));
                mtrace('Connected to source DB ' . $cfg->source_db_host . '/' . $cfg->source_db_name);
                if (!empty($cfg->source_db_setupsql)) {
                    $source->execute_setup_sql((string)$cfg->source_db_setupsql);
                    mtrace('Source setup SQL executed');
                }
            } catch (\Throwable $e) {
                mtrace('Source connection failed: ' . $e->getMessage());
                return;
            }

            try {
                $target->connect($this->target_config($cfg));
                mtrace('Connected to target DB ' . $cfg->remote_procedure_db_host . '/' . $cfg->remote_procedure_db_name);
                if (!empty($cfg->remote_procedure_db_setupsql)) {
                    $target->execute_setup_sql((string)$cfg->remote_procedure_db_setupsql);
                    mtrace('Target setup SQL executed');
                }
            } catch (\Throwable $e) {
                mtrace('Target connection failed: ' . $e->getMessage());
                return;
            }

            try {
                $rows = iterator_to_array($source->fetch_untransferred(
                    (string)$cfg->source_to_read,
                    (string)$cfg->source_field_id,
                    (string)$cfg->source_field_transferred,
                    (string)($cfg->source_transfer_field_type ?? 'numeric')
                ));
            } catch (\Throwable $e) {
                mtrace('Select from source failed: ' . $e->getMessage());
                return;
            }

            mtrace('Selected ' . count($rows) . ' rows for transfer');

            foreach ($rows as $row) {
                $id = $this->row_value($row, (string)$cfg->source_field_id);
                $logref = '';

                try {
                    $logref = $this->row_log_reference($row, $cfg);
                    $params = $this->build_in_params($row, $mapping);

                    if ((string)$cfg->remote_procedure_db_type === 'odbc') {
                        $status = $target->call_procedure((string)$cfg->remote_procedure, $params, $outparam);
                    } else {
                        $status = $target->call_procedure((string)$cfg->remote_procedure, $params);
                    }

                    if (trim((string)$status) === $successvalue) {
                        $source->mark_transferred(
                            (string)$cfg->source_to_update,
                            (string)$cfg->source_field_id,
                            $id,
                            (string)$cfg->source_field_transferred,
                            (string)($cfg->source_transfer_field_type ?? 'numeric')
                        );
                        $success++;
                        mtrace('OK row id=' . $id . $logref . ' status=' . $status);
                    } else {
                        $failed++;
                        mtrace('FAIL row id=' . $id . $logref . ' status=' . $status);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    mtrace('FAIL row id=' . $id . $logref . ' exception=' . $e->getMessage());
                }
            }

        } finally {
            if ($target !== null) {
                try {
                    $target->disconnect();
                } catch (\Throwable $e) {
                    mtrace('Target disconnect warning: ' . $e->getMessage());
                }
            }

            if ($source !== null) {
                try {
                    $source->disconnect();
                } catch (\Throwable $e) {
                    mtrace('Source disconnect warning: ' . $e->getMessage());
                }
            }

            mtrace('Run complete: success=' . $success . ' failed=' . $failed);
        }
    }

    /**
     * Validate required config.
     *
     * @param \stdClass $cfg Config.
     */
    private function validate_config(\stdClass $cfg): void {
        $required = [
            'source_db_type', 'source_db_host', 'source_db_name', 'source_db_user',
            'source_to_read', 'source_to_update', 'source_field_id', 'source_field_transferred',
            'transfer_method', 'remote_procedure_db_type', 'remote_procedure_db_host',
            'remote_procedure_db_name', 'remote_procedure_db_user', 'remote_procedure',
            'source_transfer_field_type', 'remote_procedure_success_value',
        ];

        foreach ($required as $name) {
            if (!isset($cfg->{$name}) || trim((string)$cfg->{$name}) === '') {
                throw new \moodle_exception('Missing required local_results_transfer setting: ' . $name);
            }
        }

        if ((string)$cfg->transfer_method !== 'remote_procedure') {
            throw new \moodle_exception('Unsupported transfer method: ' . s($cfg->transfer_method));
        }
    }

    /**
     * Source connection config.
     *
     * @param \stdClass $cfg Plugin config.
     * @return \stdClass
     */
    private function source_config(\stdClass $cfg): \stdClass {
        return (object)[
            'host' => $cfg->source_db_host ?? '',
            'port' => $cfg->source_db_port ?? '3306',
            'name' => $cfg->source_db_name ?? '',
            'user' => $cfg->source_db_user ?? '',
            'pass' => $cfg->source_db_pass ?? '',
        ];
    }

    /**
     * Target connection config.
     *
     * @param \stdClass $cfg Plugin config.
     * @return \stdClass
     */
    private function target_config(\stdClass $cfg): \stdClass {
        return (object)[
            'host' => $cfg->remote_procedure_db_host ?? '',
            'port' => $cfg->remote_procedure_db_port ?? '1433',
            'name' => $cfg->remote_procedure_db_name ?? '',
            'user' => $cfg->remote_procedure_db_user ?? '',
            'pass' => $cfg->remote_procedure_db_pass ?? '',
            'dsn' => $cfg->remote_procedure_db_dsn ?? '',
        ];
    }

    /**
     * Build ordered SP input parameters from row using the configured mapping.
     *
     * @param \stdClass $row Source row.
     * @param array $mapping Mapping rows.
     * @return array
     */
    private function build_in_params(\stdClass $row, array $mapping): array {
        $params = [];

        foreach ($mapping as $map) {
            if (($map['direction'] ?? 'in') !== 'in') {
                continue;
            }

            $paramname = trim((string)($map['param_name'] ?? ''));
            if ($paramname === '') {
                throw new \moodle_exception('Input parameter has no stored procedure parameter name configured.');
            }

            $sourcecolumn = trim((string)($map['source_column'] ?? ''));
            if ($sourcecolumn === '') {
                throw new \moodle_exception('Input parameter has no source column configured: ' . s($paramname));
            }

            $params[$paramname] = $this->row_value($row, $sourcecolumn);
        }

        if (empty($params)) {
            throw new \moodle_exception('No input parameters configured for procedure call.');
        }

        return $params;
    }

    /**
     * Return configured output parameter from mapping rows.
     *
     * @param array $mapping Mapping rows.
     * @return array
     */
    private function configured_output_parameter(array $mapping): array {
        foreach ($mapping as $map) {
            if (($map['direction'] ?? 'in') === 'out') {
                $paramname = trim((string)($map['param_name'] ?? ''));

                if ($paramname === '') {
                    throw new \moodle_exception('Output parameter has no stored procedure parameter name configured.');
                }

                return [
                    'param_name' => $paramname,
                    'data_type' => trim((string)($map['data_type'] ?? 'nvarchar')),
                ];
            }
        }

        throw new \moodle_exception('No output parameter configured for procedure call.');
    }

    /**
     * Read mapping rows from JSON config, falling back to legacy textarea.
     *
     * @param \stdClass $cfg Plugin config.
     * @return array
     */
    private function configured_parameter_mapping(\stdClass $cfg): array {
        $json = trim((string)($cfg->procedure_parameters_json ?? ''));

        if ($json !== '') {
            $rows = json_decode($json, true);
            if (!is_array($rows)) {
                throw new \moodle_exception('Invalid procedure parameter mapping JSON.');
            }

            usort($rows, static function(array $a, array $b): int {
                return (($a['sortorder'] ?? 0) <=> ($b['sortorder'] ?? 0));
            });

            return $rows;
        }

        // Legacy fallback for existing installs using the old ordered textarea.
        $fields = $this->configured_parameter_fields((string)($cfg->procedure_parameter_fields ?? ''));
        $rows = [];
        foreach ($fields as $index => $field) {
            $rows[] = [
                'sortorder' => $index + 1,
                'direction' => 'in',
                'param_name' => $field,
                'source_column' => $field,
                'data_type' => 'nvarchar',
            ];
        }
        $rows[] = [
            'sortorder' => count($rows) + 1,
            'direction' => 'out',
            'param_name' => 'STATUS',
            'source_column' => '',
            'data_type' => 'nvarchar',
        ];

        return $rows;
    }

    /**
     * Parse the legacy ordered parameter field setting.
     *
     * @param string $setting Raw setting value.
     * @return array
     */
    private function configured_parameter_fields(string $setting): array {
        $parts = preg_split('/[\r\n,]+/', $setting);
        $fields = [];
        foreach ($parts as $part) {
            $field = trim($part);
            if ($field !== '') {
                $fields[] = $field;
            }
        }

        if (empty($fields)) {
            throw new \moodle_exception('No procedure parameter fields configured.');
        }

        return $fields;
    }

    /**
     * Return optional row reference for concise logs.
     *
     * @param \stdClass $row Source row.
     * @param \stdClass $cfg Plugin config.
     * @return string
     */
    private function row_log_reference(\stdClass $row, \stdClass $cfg): string {
        $field = trim((string)($cfg->source_log_field ?? ''));
        if ($field === '' || !property_exists($row, $field)) {
            return '';
        }

        return ' ' . $field . '=' . $row->{$field};
    }

    /**
     * Read a configured field from a row.
     *
     * @param \stdClass $row Row.
     * @param string $field Field name.
     * @return mixed
     */
    private function row_value(\stdClass $row, string $field) {
        if (!property_exists($row, $field)) {
            throw new \moodle_exception('Missing source field in row: ' . s($field));
        }
        return $row->{$field};
    }
}
