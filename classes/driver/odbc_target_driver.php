<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

defined('MOODLE_INTERNAL') || die();

/**
 * ODBC target driver for SQL Server.
 *
 * Uses php-odbc + Microsoft ODBC Driver 18.
 *
 * The procedure is called using named assignments:
 *
 * EXEC dbo.procname
 *     @param1 = ?,
 *     @param2 = ?,
 *     ...
 *
 * The procedure OUTPUT parameter is handled using a local SQL variable:
 *
 * DECLARE @status NVARCHAR(255);
 * EXEC dbo.procname
 *     @param1 = ?,
 *     @STATUS = @status OUTPUT;
 * SELECT @status AS status;
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class odbc_target_driver implements driver_interface {

    /** @var resource|null */
    private $conn = null;

    /**
     * Connect to target SQL Server through ODBC.
     *
     * @param \stdClass $config Connection config.
     */
    public function connect(\stdClass $config): void {
        $host = $config->host ?? 'localhost';
        $port = (int)($config->port ?? 1433);
        $dbname = $config->name ?? '';
        $user = $config->user ?? '';
        $pass = $config->pass ?? '';

        // Optional full DSN override. Useful if sysadmin provides a DSN name/string.
        $dsn = trim((string)($config->dsn ?? ''));

        if ($dsn === '') {
            $dsn = "Driver={ODBC Driver 18 for SQL Server};" .
                "Server={$host},{$port};" .
                "Database={$dbname};" .
                "Encrypt=no;" .
                "TrustServerCertificate=yes;";
        }

        $conn = @odbc_connect($dsn, $user, $pass);

        if (!$conn) {
            throw new \moodle_exception(
                'ODBC connection failed: ' . odbc_errormsg()
            );
        }

        $this->conn = $conn;
    }

    /**
     * Disconnect.
     */
    public function disconnect(): void {
        if ($this->conn) {
            odbc_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * Execute setup SQL.
     *
     * @param string $sql SQL to execute.
     */
    public function execute_setup_sql(string $sql): void {
        $this->require_connection();

        if (trim($sql) === '') {
            return;
        }

        if (!@odbc_exec($this->conn, $sql)) {
            throw new \moodle_exception(
                'ODBC setup SQL failed: ' . odbc_errormsg($this->conn)
            );
        }
    }

    /**
     * Call stored procedure.
     *
     * IMPORTANT:
     * $inparams should preferably be an associative array:
     *
     * [
     *     'associationId' => '...',
     *     'role' => '...',
     *     ...
     * ]
     *
     * This driver calls SQL Server using:
     *
     * DECLARE @status NVARCHAR(255);
     * EXEC procname
     *     @associationId = ?,
     *     @role = ?,
     *     ...
     *     @STATUS = @status OUTPUT;
     * SELECT @status AS status;
     *
     * If a numeric array is passed, it falls back to positional placeholders,
     * but still appends @STATUS = @status OUTPUT.
     *
     * @param string $procname Procedure name.
     * @param array $inparams Input params.
     * @return string Returned status.
     */
    public function call_procedure(string $procname, array $inparams): string {
        $this->require_connection();

        if (empty($inparams)) {
            throw new \moodle_exception('ODBC procedure call has no input parameters.');
        }

        $isassoc = $this->is_assoc($inparams);
        $values = [];

        $sql = 'DECLARE @status NVARCHAR(255); ';
        $sql .= 'EXEC ' . $procname . ' ';

        if ($isassoc) {
            $assignments = [];

            foreach ($inparams as $paramname => $value) {
                $paramname = $this->clean_param_name((string)$paramname);

                if ($paramname === '') {
                    throw new \moodle_exception('ODBC procedure call contains an empty parameter name.');
                }

                $assignments[] = '@' . $paramname . ' = ?';
                $values[] = $value;
            }

            // Required SQL Server output parameter.
            $assignments[] = '@STATUS = @status OUTPUT';

            $sql .= implode(', ', $assignments);
        } else {
            $parts = [];

            foreach ($inparams as $value) {
                $parts[] = '?';
                $values[] = $value;
            }

            // Required SQL Server output parameter.
            $parts[] = '@STATUS = @status OUTPUT';

            $sql .= implode(', ', $parts);
        }

        // Return OUTPUT value as a result set so PHP ODBC can read it reliably.
        $sql .= '; SELECT @status AS status';

        $stmt = @odbc_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new \moodle_exception(
                'ODBC prepare failed: ' . odbc_errormsg($this->conn) . ' SQL=' . $sql
            );
        }

        $execparams = [];
        foreach ($values as $value) {
            if ($value === null) {
                $execparams[] = null;
            } else {
                $execparams[] = (string)$value;
            }
        }

        if (!@odbc_execute($stmt, $execparams)) {
            throw new \moodle_exception(
                'ODBC procedure execution failed: ' . odbc_errormsg($this->conn) . ' SQL=' . $sql
            );
        }

        $status = $this->fetch_status_from_result($stmt);

        @odbc_free_result($stmt);

        return trim((string)$status);
    }

    /**
     * Not used on target driver.
     *
     * @param string $table Table.
     * @param string $idfield ID field.
     * @param string $transferredfield Transferred field.
     * @param string $transfertype Transfer field type.
     * @return \Generator
     */
    public function fetch_untransferred(
        string $table,
        string $idfield,
        string $transferredfield,
        string $transfertype = 'numeric'
    ): \Generator {
        throw new \coding_exception('Not implemented for ODBC target driver');
    }

    /**
     * Not used on target driver.
     *
     * @param string $table Table.
     * @param string $idfield ID field.
     * @param mixed $idvalue ID value.
     * @param string $transferredfield Transferred field.
     * @param string $transfertype Transfer field type.
     */
    public function mark_transferred(
        string $table,
        string $idfield,
        $idvalue,
        string $transferredfield,
        string $transfertype = 'numeric'
    ): void {
        throw new \coding_exception('Not implemented for ODBC target driver');
    }

    /**
     * Fetch status from returned result set.
     *
     * Expected result:
     *
     * SELECT @status AS status;
     *
     * @param resource $stmt ODBC statement.
     * @return string
     */
    private function fetch_status_from_result($stmt): string {
        $status = '';

        do {
            while ($row = @odbc_fetch_array($stmt)) {
                foreach ($row as $key => $value) {
                    if (strtolower((string)$key) === 'status') {
                        return (string)$value;
                    }
                }

                // Fallback: if only one column is returned, treat it as status.
                if (count($row) === 1) {
                    return (string)reset($row);
                }
            }
        } while (@odbc_next_result($stmt));

        return $status;
    }

    /**
     * Clean SQL Server parameter name.
     *
     * @param string $name Parameter name.
     * @return string
     */
    private function clean_param_name(string $name): string {
        $name = trim($name);
        $name = ltrim($name, '@');

        // Keep only normal SQL parameter-name characters.
        return preg_replace('/[^A-Za-z0-9_]/', '', $name);
    }

    /**
     * Is associative array.
     *
     * @param array $array Array.
     * @return bool
     */
    private function is_assoc(array $array): bool {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Ensure connection exists.
     */
    private function require_connection(): void {
        if (!$this->conn) {
            throw new \coding_exception('ODBC connection not established');
        }
    }
}
