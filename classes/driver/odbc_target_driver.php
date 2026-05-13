<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

defined('MOODLE_INTERNAL') || die();

/**
 * ODBC target driver for SQL Server.
 *
 * Uses php-odbc + Microsoft ODBC Driver 18.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class odbc_target_driver implements driver_interface {

    /** @var resource|null */
    private $conn = null;

    /**
     * Connect.
     *
     * @param \stdClass $config
     */
    public function connect(\stdClass $config): void {

        $host = $config->host ?? 'localhost';
        $port = (int)($config->port ?? 1433);
        $dbname = $config->name ?? '';
        $user = $config->user ?? '';
        $pass = $config->pass ?? '';

        $dsn = "Driver={ODBC Driver 18 for SQL Server};" .
            "Server={$host},{$port};" .
            "Database={$dbname};" .
            "Encrypt=no;" .
            "TrustServerCertificate=yes;";

        $conn = @odbc_connect($dsn, $user, $pass);

        if (!$conn) {
            throw new \moodle_exception(
                'Could not connect using ODBC: ' . odbc_errormsg()
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
     * @param string $sql
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
     * Requires procedure to return:
     * SELECT @STATUS AS status
     *
     * @param string $procname
     * @param array $inparams
     * @return string
     */
    public function call_procedure(string $procname, array $inparams): string {

        $this->require_connection();

        $escaped = [];

        foreach ($inparams as $value) {

            if ($value === null) {
                $escaped[] = 'NULL';
                continue;
            }

            $value = str_replace("'", "''", (string)$value);
            $escaped[] = "'" . $value . "'";
        }

        $sql = "EXEC {$procname} " . implode(', ', $escaped);

        $result = @odbc_exec($this->conn, $sql);

        if (!$result) {
            throw new \moodle_exception(
                'ODBC procedure execution failed: ' . odbc_errormsg($this->conn)
            );
        }

        $status = '';

        while ($row = odbc_fetch_array($result)) {

            if (isset($row['status'])) {
                $status = (string)$row['status'];
                break;
            }

            if (isset($row['STATUS'])) {
                $status = (string)$row['STATUS'];
                break;
            }
        }

        odbc_free_result($result);

        return trim($status);
    }

    /**
     * Not used on target driver.
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
     * Ensure connection exists.
     */
    private function require_connection(): void {
        if (!$this->conn) {
            throw new \coding_exception('ODBC connection not established');
        }
    }
}
