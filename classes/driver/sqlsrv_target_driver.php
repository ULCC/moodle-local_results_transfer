<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

/**
 * SQL Server target driver using the sqlsrv PHP extension.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqlsrv_target_driver implements driver_interface {
    /** @var resource|null */
    private $conn = null;

    /**
     * Connect to SQL Server.
     *
     * @param \stdClass $config Connection config.
     */
    public function connect(\stdClass $config): void {
        if (!function_exists('sqlsrv_connect')) {
            throw new \moodle_exception('The sqlsrv PHP extension is not installed/enabled.');
        }

        $host = $config->host ?? '';
        $port = trim((string)($config->port ?? '1433'));
        if ($port !== '' && strpos($host, '\\') === false && strpos($host, ',') === false) {
            $host .= ',' . $port;
        }

        $options = [
            'Database' => $config->name ?? '',
            'UID' => $config->user ?? '',
            'PWD' => $config->pass ?? '',
            'CharacterSet' => 'UTF-8',
            'LoginTimeout' => 30,
        ];

        $this->conn = sqlsrv_connect($host, $options);
        if ($this->conn === false) {
            throw new \moodle_exception('SQL Server connection failed: ' . self::format_errors());
        }
    }

    /**
     * Disconnect.
     */
    public function disconnect(): void {
        if ($this->conn) {
            sqlsrv_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * Execute setup SQL.
     *
     * @param string $sql SQL.
     */
    public function execute_setup_sql(string $sql): void {
        $this->require_connection();
        if (trim($sql) === '') {
            return;
        }
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            throw new \moodle_exception('SQL Server setup SQL failed: ' . self::format_errors());
        }
        while (sqlsrv_next_result($stmt)) {
            // Drain result sets.
        }
        sqlsrv_free_stmt($stmt);
    }

    /**
     * Not implemented for target driver.
     *
     * @param string $table Table.
     * @param string $idfield ID field.
     * @param string $transferredfield Transferred field.
     * @return \Generator
     */
    public function fetch_untransferred(string $table, string $idfield, string $transferredfield): \Generator {
        throw new \moodle_exception('fetch_untransferred is not implemented for sqlsrv target driver.');
        yield;
    }

    /**
     * Call SQL Server stored procedure.
     *
     * @param string $procname Procedure name.
     * @param array $inparams Ordered input params.
     * @return string
     */
    public function call_procedure(string $procname, array $inparams): string {
        $this->require_connection();
        $procname = self::validate_identifier_path($procname);
        $sql = '{CALL ' . $procname . ' (' . implode(',', array_fill(0, count($inparams) + 1, '?')) . ')}';
        $out = '';

        $params = [];
        foreach ($inparams as $param) {
            $params[] = [$param, SQLSRV_PARAM_IN];
        }
        $params[] = [
            &$out,
            SQLSRV_PARAM_OUT,
            SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR),
            SQLSRV_SQLTYPE_VARCHAR(255),
        ];

        $stmt = sqlsrv_prepare($this->conn, $sql, $params);
        if ($stmt === false) {
            throw new \moodle_exception('SQL Server procedure prepare failed: ' . self::format_errors());
        }
        if (sqlsrv_execute($stmt) === false) {
            $error = self::format_errors();
            sqlsrv_free_stmt($stmt);
            throw new \moodle_exception('SQL Server procedure execute failed: ' . $error);
        }
        while (sqlsrv_next_result($stmt)) {
            // Drain result sets so OUT param is populated.
        }
        sqlsrv_free_stmt($stmt);

        return (string)$out;
    }

    /**
     * Not implemented for target driver.
     *
     * @param string $table Table.
     * @param string $idfield ID field.
     * @param mixed $idvalue ID value.
     * @param string $transferredfield Transferred field.
     */
    public function mark_transferred(string $table, string $idfield, $idvalue, string $transferredfield): void {
        throw new \moodle_exception('mark_transferred is not implemented for sqlsrv target driver.');
    }

    /**
     * Ensure connection exists.
     */
    private function require_connection(): void {
        if (!$this->conn) {
            throw new \moodle_exception('SQL Server connection is not open.');
        }
    }

    /**
     * Validate schema-qualified procedure name.
     *
     * @param string $path Procedure path.
     * @return string
     */
    private static function validate_identifier_path(string $path): string {
        $parts = explode('.', $path);
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $part)) {
                throw new \moodle_exception('Invalid procedure name: ' . s($path));
            }
        }
        return $path;
    }

    /**
     * Format sqlsrv errors.
     *
     * @return string
     */
    private static function format_errors(): string {
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if (empty($errors)) {
            return 'Unknown sqlsrv error';
        }
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = '[' . ($error['SQLSTATE'] ?? '') . '/' . ($error['code'] ?? '') . '] ' . ($error['message'] ?? '');
        }
        return implode('; ', $messages);
    }
}
