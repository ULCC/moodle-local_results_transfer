<?php
// This file is part of Moodle - http://moodle.org/

namespace local_results_transfer\driver;

/**
 * Database driver interface for results transfer.
 *
 * @package    local_results_transfer
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface driver_interface {
    /**
     * Connect to the configured database.
     *
     * @param \stdClass $config Connection configuration.
     */
    public function connect(\stdClass $config): void;

    /**
     * Close the connection.
     */
    public function disconnect(): void;

    /**
     * Execute optional setup SQL.
     *
     * @param string $sql SQL to execute.
     */
    public function execute_setup_sql(string $sql): void;

    /**
     * Fetch rows waiting for transfer.
     *
     * @param string $table Table or view name.
     * @param string $idfield ID field name.
     * @param string $transferredfield Transferred/status field name.
     * @param string $transfertype Transfer field type: numeric or datetime.
     * @return \Generator
     */
    public function fetch_untransferred(string $table, string $idfield, string $transferredfield, string $transfertype = 'numeric'): \Generator;

    /**
     * Call stored procedure and return output status.
     *
     * @param string $procname Procedure name.
     * @param array $inparams Ordered input parameters.
     * @return string
     */
    public function call_procedure(string $procname, array $inparams): string;

    /**
     * Mark a row as transferred.
     *
     * @param string $table Table name.
     * @param string $idfield ID field name.
     * @param mixed $idvalue ID value.
     * @param string $transferredfield Transferred/status field name.
     * @param string $transfertype Transfer field type: numeric or datetime.
     */
    public function mark_transferred(string $table, string $idfield, $idvalue, string $transferredfield, string $transfertype = 'numeric'): void;
}
